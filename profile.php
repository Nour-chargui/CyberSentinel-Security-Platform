<?php
require 'config/db.php';
require 'includes/functions.php';

if (!isset($_SESSION['user_id'])) header("Location: index.php");

$user_id = $_SESSION['user_id'];
$u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$u->execute([$user_id]);
$user = $u->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Logique de mise à jour (Email, Tel, Password)
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    if(!empty($_POST['new_password'])) {
        $pass = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET email=?, phone=?, password_hash=? WHERE id=?");
        $stmt->execute([$email, $phone, $pass, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET email=?, phone=? WHERE id=?");
        $stmt->execute([$email, $phone, $user_id]);
    }
    header("Location: profile.php?success=1");
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50">
<div class="flex items-center gap-3">
    <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-10 h-10 object-contain" alt="Logo">
    <span class="text-2xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
</div>
<div class="max-w-4xl mx-auto py-12 px-4">
    <div class="bg-white rounded-3xl shadow-xl overflow-hidden">
        <div class="bg-blue-600 h-32"></div>
        <div class="px-8 pb-8">
            <div class="relative -top-12 flex items-end justify-between">
                <img src="https://ui-avatars.com/api/?name=<?= $user['username'] ?>&background=random" class="w-32 h-32 rounded-3xl border-4 border-white shadow-lg">
                <a href="dashboard.php" class="bg-slate-100 px-6 py-2 rounded-xl font-bold text-slate-600 mb-2">Retour Dashboard</a>
            </div>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nom d'utilisateur</label>
                    <input type="text" value="<?= $user['username'] ?>" disabled class="w-full p-4 bg-slate-50 border rounded-2xl text-slate-400">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Email Professionnel</label>
                    <input type="email" name="email" value="<?= $user['email'] ?>" class="w-full p-4 border rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Téléphone</label>
                    <input type="text" name="phone" value="<?= $user['phone'] ?>" class="w-full p-4 border rounded-2xl" placeholder="+216 ...">
                </div>
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Nouveau Mot de Passe (Optionnel)</label>
                    <input type="password" name="new_password" class="w-full p-4 border rounded-2xl" placeholder="••••••••">
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="w-full bg-slate-900 text-white p-4 rounded-2xl font-bold hover:bg-blue-600 transition">Enregistrer les modifications</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>