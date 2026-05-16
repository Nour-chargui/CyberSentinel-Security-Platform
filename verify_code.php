<?php
require 'config/db.php';
require 'includes/functions.php';

if (!isset($_SESSION['reset_user_id'])) { header("Location: forgot_password.php"); exit; }

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $code = $_POST['code'];
    $new_pass = $_POST['new_password'];
    $uid = $_SESSION['reset_user_id'];

    // Vérifier le code
    $stmt = $pdo->prepare("SELECT * FROM password_resets WHERE user_id = ? AND reset_code = ? AND expires_at > NOW()");
    $stmt->execute([$uid, $code]);

    if ($stmt->fetch()) {
        // Code valide -> Changer le mot de passe
        $hash = password_hash($new_pass, PASSWORD_BCRYPT);
        $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$uid]);

        unset($_SESSION['reset_user_id']);
        header("Location: index.php?msg=reset_success");
    } else {
        $error = "❌ Code invalide ou expiré.";
    }
}
?>
<!DOCTYPE html>
<html>
<head><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen">
<div class="bg-white p-10 rounded-3xl shadow-xl w-full max-w-md">
    <h2 class="text-2xl font-black text-center mb-8">Nouveau Mot de Passe</h2>
    <?php if($error): ?><div class="bg-red-50 text-red-600 p-3 rounded-xl mb-4 text-center font-bold"><?= $error ?></div><?php endif; ?>
    <form method="POST" class="space-y-4">
        <input type="text" name="code" placeholder="Code à 6 chiffres" class="w-full p-4 border rounded-2xl text-center text-2xl tracking-widest font-black" maxlength="6" required>
        <input type="password" name="new_password" placeholder="Nouveau mot de passe" class="w-full p-4 border rounded-2xl" required>
        <button type="submit" class="w-full bg-blue-600 text-white p-4 rounded-2xl font-bold">Réinitialiser</button>
    </form>
</div>
</body>
</html>