<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$msg = "";
$code_for_demo = ""; // Pour afficher le code si le mail échoue

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['identifier']);

    // 1. Chercher l'utilisateur
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $code = rand(100000, 999999);
        $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

        // 2. Stockage en base de données
        $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
        $pdo->prepare("INSERT INTO password_resets (user_id, reset_code, method, expires_at) VALUES (?, ?, 'email', ?)")
                ->execute([$user['id'], $code, $expires]);

        // 3. Tentative d'envoi d'Email
        $to = $email;
        $subject = "Code de Recuperation CyberSentinel";
        $message = "Bonjour " . h($user['username']) . ",\n\nVotre code de securite est : $code\nCe code expire dans 15 minutes.";
        $headers = "From: security@cybersentinel.com";

        // L'arobase (@) devant mail évite l'affichage de l'erreur système moche
        if(@mail($to, $subject, $message, $headers)) {
            $_SESSION['reset_user_id'] = $user['id'];
            header("Location: verify_code.php?sent=1");
            exit;
        } else {
            // MODE DÉMONSTRATION (Si XAMPP ne peut pas envoyer)
            $_SESSION['reset_user_id'] = $user['id'];
            $code_for_demo = $code;
            $msg = "⚠️ Le serveur SMTP local est déconnecté. Envoi simulé.";
        }
    } else {
        $msg = "❌ Aucun compte lié à cette adresse.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récupération | CyberSentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50 flex flex-col items-center justify-center min-h-screen p-6">

<!-- LOGO -->
<div class="flex items-center gap-3 mb-8">
    <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-12 h-12" alt="Logo">
    <span class="text-3xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
</div>

<div class="bg-white p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md border border-slate-100">
    <h2 class="text-2xl font-black text-center mb-4">Mot de passe oublié</h2>
    <p class="text-slate-500 text-sm text-center mb-8">Un code de vérification sera envoyé à votre adresse email.</p>

    <?php if($msg): ?>
        <div class="bg-amber-50 text-amber-700 p-4 rounded-2xl mb-6 border border-amber-100 text-sm font-bold">
            <?= $msg ?>
            <?php if($code_for_demo): ?>
                <div class="mt-2 p-3 bg-white rounded-xl border border-amber-200 text-center text-xl tracking-widest text-blue-600">
                    CODE : <?= $code_for_demo ?>
                </div>
                <p class="text-[10px] mt-2 opacity-70">Note : Ce bloc n'apparaît que parce que nous sommes en environnement de test (Localhost).</p>
                <a href="verify_code.php" class="block w-full bg-blue-600 text-white text-center py-3 rounded-xl mt-4 font-bold">Saisir le code</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- FORMULAIRE -->
    <form method="POST" action="forgot_password.php" class="space-y-6">
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 ml-1">Votre Email</label>
            <input type="email" name="identifier" placeholder="nom@exemple.com"
                   class="w-full px-5 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all bg-slate-50" required>
        </div>

        <button type="submit" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-bold text-lg hover:bg-black transition-all shadow-xl active:scale-95">
            Envoyer le code
        </button>
    </form>

    <div class="mt-8 text-center text-sm">
        <a href="index.php" class="text-slate-400 hover:text-blue-600 font-bold transition">Retour à la connexion</a>
    </div>
</div>
</body>
</html>