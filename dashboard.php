<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/tracker.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$msg = "";

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

if ($u['is_blocked']) {
    session_destroy();
    header("Location: index.php?error=account_blocked");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $photo = $_POST['photo'];
    $sql = "UPDATE users SET email = ?, phone = ?, profile_photo = ? WHERE id = ?";
    $params = [$email, $phone, $photo, $user_id];

    if (!empty($_POST['new_password'])) {
        $sql = "UPDATE users SET email = ?, phone = ?, profile_photo = ?, password_hash = ? WHERE id = ?";
        $params = [$email, $phone, $photo, password_hash($_POST['new_password'], PASSWORD_BCRYPT), $user_id];
    }

    if($pdo->prepare($sql)->execute($params)) {
        $msg = "✅ Profil mis à jour.";
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
    }
}

// LOGIQUE DE SCORE : Uniquement pour les utilisateurs standard
$risk = 0;
$display_trust = 100;
if ($user_role !== 'admin') {
    calculateRiskScore($pdo, $user_id);
    $stmt_risk = $pdo->prepare("SELECT MAX(risk_score) FROM alerts WHERE user_id = ?");
    $stmt_risk->execute([$user_id]);
    $risk = (int)$stmt_risk->fetchColumn();
    $display_trust = 100; // Confiance restaurée après login réussi
}

$stmt_orders = $pdo->prepare("SELECT o.order_date, p.name, p.price, p.image_url FROM orders o INNER JOIN products p ON o.product_id = p.id WHERE o.user_id = ? ORDER BY order_date DESC");
$stmt_orders->execute([$user_id]);
$my_orders = $stmt_orders->fetchAll();

$avatars = ['https://cdn-icons-png.flaticon.com/512/6858/6858504.png','https://cdn-icons-png.flaticon.com/512/4140/4140037.png','https://cdn-icons-png.flaticon.com/512/6997/6997662.png','https://cdn-icons-png.flaticon.com/512/4140/4140047.png'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CyberSentinel Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50 min-h-screen font-sans text-slate-800">

<nav class="bg-white shadow-sm px-8 py-4 flex justify-between items-center sticky top-0 z-50">
    <div class="flex items-center gap-3">
        <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-8 h-8">
        <span class="text-xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
    </div>
    <div class="flex items-center gap-6">
        <a href="shop.php" class="font-bold text-slate-600 hover:text-blue-600 transition">Boutique</a>
        <?php if($user_role === 'admin'): ?>
            <a href="admin.php" class="text-red-600 font-bold uppercase text-xs border-2 border-red-100 px-3 py-1 rounded-xl hover:bg-red-50 transition shadow-sm">🛡️ War Room Admin</a>
        <?php endif; ?>
        <a href="logout.php" class="text-red-500 font-bold text-sm">Quitter</a>
    </div>
</nav>

<!-- ALERTE : Masquée pour l'admin -->
<?php if ($user_role !== 'admin' && $risk >= 80): ?>
    <div class="max-w-7xl mx-auto mt-6 px-8 animate-pulse">
        <div class="bg-red-600 text-white p-6 rounded-[2rem] shadow-2xl flex items-center justify-between border-4 border-red-400">
            <div class="flex items-center gap-4">
                <span class="text-4xl">⚠️</span>
                <div>
                    <h3 class="text-xl font-black uppercase tracking-tight text-white">Menace de Sécurité Critiquée</h3>
                    <p class="text-sm opacity-90 text-white">Score de risque : <?= $risk ?>%. Compte sous surveillance.</p>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<main class="max-w-7xl mx-auto p-8 grid grid-cols-1 lg:grid-cols-3 gap-8">

    <!-- COLONNE PROFIL -->
    <div class="lg:col-span-1 bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100 text-center">
        <img src="<?= h($u['profile_photo']) ?>" class="w-28 h-28 mx-auto rounded-full border-4 <?= ($user_role === 'admin') ? 'border-slate-800' : 'border-emerald-500' ?> mb-4 object-cover shadow-lg">
        <h2 class="text-2xl font-black text-slate-800"><?= h($u['username']) ?></h2>

        <!-- CONDITION : MASQUER LE SCORE POUR L'ADMIN -->
        <?php if($user_role !== 'admin'): ?>
            <div class="mt-2">
                <div class="text-4xl font-black text-emerald-600"><?= $display_trust ?>%</div>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Indice de Confiance</p>
                <?php if($risk > 0): ?>
                    <p class="text-[9px] text-red-500 font-bold mt-1">Risque détecté : <?= $risk ?>%</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="mt-2 py-2 px-4 bg-slate-100 rounded-full inline-block">
                <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest">Compte Administrateur</span>
            </div>
        <?php endif; ?>

        <?php if($msg): ?>
            <div class="mt-4 p-3 bg-emerald-50 text-emerald-600 rounded-xl text-xs font-bold border border-emerald-100"><?= $msg ?></div>
        <?php endif; ?>

        <form method="POST" class="text-left mt-8 space-y-4">
            <input type="hidden" name="update_profile" value="1">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Changer d'Avatar</label>
            <div class="grid grid-cols-4 gap-2 mb-4">
                <?php foreach($avatars as $av): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="photo" value="<?= $av ?>" class="hidden peer" <?= $u['profile_photo']==$av?'checked':'' ?>>
                        <img src="<?= $av ?>" class="w-10 h-10 rounded-full border-2 border-transparent peer-checked:border-blue-600 transition">
                    </label>
                <?php endforeach; ?>
            </div>
            <input type="email" name="email" value="<?= h($u['email']) ?>" class="w-full p-3 border rounded-xl text-sm bg-slate-50 outline-none">
            <input type="text" name="phone" value="<?= h($u['phone']) ?>" class="w-full p-3 border rounded-xl text-sm bg-slate-50 outline-none" placeholder="Téléphone">
            <input type="password" name="new_password" placeholder="Changer le mot de passe" class="w-full p-3 border rounded-xl text-sm bg-slate-50 outline-none">
            <button type="submit" class="w-full bg-slate-900 text-white p-4 rounded-2xl font-bold shadow-lg hover:bg-black transition">Enregistrer</button>
        </form>
    </div>

    <div class="lg:col-span-2 space-y-8">
        <!-- ANALYSE IA : Masquée pour l'admin, remplacée par un message de bienvenue -->
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
            <?php if($user_role !== 'admin'): ?>
                <h3 class="font-black text-slate-800 mb-6 flex items-center gap-2 text-sm uppercase tracking-wider">Analyse de Protection IA</h3>
                <div style="height: 220px;"><canvas id="userChart"></canvas></div>
            <?php else: ?>
                <h3 class="font-black text-slate-800 mb-4 uppercase tracking-wider">Console d'Administration</h3>
                <p class="text-slate-500 text-sm leading-relaxed">
                    Bienvenue dans votre centre de contrôle personnel. En tant qu'administrateur, vous disposez d'un accès illimité aux ressources système. Pour surveiller les utilisateurs et les menaces, veuillez utiliser la <strong>War Room</strong>.
                </p>
                <div class="mt-6 p-6 bg-blue-50 border border-blue-100 rounded-3xl">
                    <p class="text-blue-600 font-bold text-sm italic">"Un grand pouvoir implique une grande responsabilité sécuritaire."</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- ACHATS -->
        <div class="bg-white p-8 rounded-[2rem] shadow-sm border border-slate-100">
            <h3 class="font-black text-slate-800 mb-6 italic">Historique d'Achats</h3>
            <div class="space-y-3">
                <?php if(empty($my_orders)): ?>
                    <p class="text-center text-slate-400 py-10 text-sm">Aucun achat effectué.</p>
                <?php else: ?>
                    <?php foreach($my_orders as $o): ?>
                        <div class="flex justify-between items-center p-4 bg-slate-50/50 rounded-2xl border border-slate-100">
                            <div class="flex items-center gap-4">
                                <img src="<?= h($o['image_url']) ?>" class="w-12 h-12 rounded-xl object-cover shadow-sm">
                                <div><p class="font-bold text-sm text-slate-800"><?= h($o['name']) ?></p><p class="text-[10px] text-slate-400"><?= $o['order_date'] ?></p></div>
                            </div>
                            <span class="font-black text-blue-600 text-sm"><?= number_format($o['price'], 2) ?> €</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
    <?php if($user_role !== 'admin'): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('userChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['J-4', 'J-3', 'J-2', 'J-1', 'Connecté'],
                datasets: [{
                    label: 'Confiance',
                    data: [95, 92, 98, 90, <?= $display_trust ?>],
                    borderColor: '#10b981',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(16, 185, 129, 0.05)'
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false, min: 0, max: 110 }, x: { grid: { display: false } } } }
        });
    });
    <?php endif; ?>
</script>
</body>
</html>