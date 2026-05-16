<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/tracker.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }

$user_id = $_SESSION['user_id'];
trackActivity($pdo, $user_id, "Navigation Boutique");

// 1. RÉCUPÉRER LE RISQUE RÉEL (Pour bloquer les boutons)
$stmt_risk = $pdo->prepare("SELECT MAX(risk_score) FROM alerts WHERE user_id = ?");
$stmt_risk->execute([$user_id]);
$risk = (int)$stmt_risk->fetchColumn() ?: 0;

// 2. RÉCUPÉRER LE PANIER
$stmt_cart = $pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
$stmt_cart->execute([$user_id]);
$cart_count = $stmt_cart->fetchColumn() ?: 0;

// 3. RÉCUPÉRER LES PRODUITS
$products = $pdo->query("SELECT * FROM products")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CyberSentinel | Boutique</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .product-card:hover img { transform: scale(1.05); }
        .nav-glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- BARRE DE NAVIGATION INNOVANTE -->
<nav class="nav-glass sticky top-0 z-50 border-b border-slate-200 px-8 py-4 flex justify-between items-center shadow-sm">
    <div class="flex items-center gap-3">
        <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-10 h-10 object-contain" alt="Logo">
        <span class="text-2xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
    </div>

    <div class="flex items-center gap-6">
        <a href="dashboard.php" class="font-bold text-slate-600 hover:text-blue-600 transition text-sm uppercase tracking-wider">Tableau de bord</a>

        <!-- PANIER -->
        <a href="checkout.php" class="relative p-2 bg-slate-100 rounded-xl hover:bg-blue-600 hover:text-white transition group">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
            <?php if($cart_count > 0): ?>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-[10px] font-bold w-5 h-5 flex items-center justify-center rounded-full border-2 border-white animate-bounce"><?= $cart_count ?></span>
            <?php endif; ?>
        </a>

        <a href="logout.php" class="text-red-500 hover:underline font-bold text-sm">Quitter</a>
    </div>
</nav>

<main class="max-w-7xl mx-auto p-8">
    <div class="mb-12">
        <h2 class="text-4xl font-black text-slate-900">Catalogue Cyber-Tech</h2>
        <p class="text-slate-500 mt-2 italic">Protection comportementale active sur chaque transaction.</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
        <?php foreach($products as $p): ?>
            <div class="product-card bg-white rounded-[2rem] shadow-sm border border-slate-100 overflow-hidden flex flex-col group transition-all">
                <div class="h-48 overflow-hidden relative">
                    <img src="<?= h($p['image_url']) ?>"
                         alt="<?= h($p['name']) ?>"
                         loading="lazy"
                         class="w-full h-full object-cover transition duration-500">>
                    <div class="absolute top-4 right-4 bg-white/90 px-3 py-1 rounded-full text-xs font-black text-blue-600 shadow-sm">
                        <?= number_format($p['price'], 2) ?> €
                    </div>
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <h3 class="font-bold text-slate-800 mb-4"><?= h($p['name']) ?></h3>

                    <div class="mt-auto">
                        <?php if ($risk >= 80): ?>
                            <!-- BOUTON BLOQUÉ (SCÉNARIO MARIEM) -->
                            <div class="bg-red-50 text-red-600 p-3 rounded-xl text-[10px] font-black uppercase border border-red-100 text-center">
                                🔒 Achats Bloqués (Risque <?= $risk ?>%)
                            </div>
                        <?php else: ?>
                            <!-- BOUTON ACTIF -->
                            <a href="cart_process.php?action=add&id=<?= $p['id'] ?>" class="block w-full text-center bg-slate-900 text-white py-3 rounded-2xl font-bold hover:bg-blue-600 transition shadow-lg active:scale-95">
                                Ajouter au Panier 🛒
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</main>

</body>
</html>