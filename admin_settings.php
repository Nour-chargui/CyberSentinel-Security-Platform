<?php
require 'config/db.php';
require 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') { header("Location: index.php"); exit; }

$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_pass = $_POST['old_password'];
    $new_pass = $_POST['new_password'];

    // Vérification de l'ancien mot de passe
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (password_verify($old_pass, $user['password_hash'])) {
        $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$new_hash, $_SESSION['user_id']]);
        $message = "✅ Clé d'accès mise à jour avec succès.";
    } else {
        $message = "❌ L'ancienne clé est incorrecte.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-[#020617] text-white p-8">
<div class="flex items-center gap-3">
    <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-10 h-10 object-contain" alt="Logo">
    <span class="text-2xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
</div>
<div class="max-w-md mx-auto bg-[#0f172a] p-8 rounded-3xl border border-slate-800">
    <h2 class="text-2xl font-black mb-6">Sécurité de l'accès Admin</h2>
    <?php if($message): ?>
        <div class="p-4 mb-4 rounded-xl bg-blue-500/10 text-blue-400 border border-blue-500/20"><?= $message ?></div>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
        <input type="password" name="old_password" placeholder="Ancienne Clé" class="w-full bg-slate-900 p-4 rounded-xl border border-slate-800" required>
        <input type="password" name="new_password" placeholder="Nouvelle Clé" class="w-full bg-slate-900 p-4 rounded-xl border border-slate-800" required>
        <button type="submit" class="w-full bg-blue-600 p-4 rounded-xl font-bold">Mettre à jour l'accès</button>
    </form>
    <a href="admin.php" class="block text-center mt-6 text-slate-500 text-sm">Retour à la War Room</a>
</div>
</body>
</html>