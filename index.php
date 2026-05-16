<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/tracker.php';

// Si déjà connecté, redirection
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// 1. DÉBUT DU BLOC POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Vérification blocage
        if ($user['is_blocked']) {
            $error = "🚫 Accès refusé. Compte verrouillé.";
            logLogin($pdo, $user['id'], 'failed');
        } else {
            // CONNEXION RÉUSSIE
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['username'] = $user['username'];

            logLogin($pdo, $user['id'], 'success');
            calculateRiskScore($pdo, $user['id']);

            header("Location: dashboard.php");
            exit;
        }
    } else {
        // ÉCHEC DE CONNEXION
        if ($user) {
            logLogin($pdo, $user['id'], 'failed');
            calculateRiskScore($pdo, $user['id']); // Mise à jour du score (20, 40, 60, 80...)
        }
        $error = "Identifiants invalides.";
    }
} // <--- C'était cette accolade qui manquait probablement !
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion | CyberSentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50 flex flex-col items-center justify-center min-h-screen p-6">

<!-- LOGO D'INGÉNIEUR SÉCURITÉ -->
<div class="flex items-center gap-3 mb-8">
    <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-12 h-12" alt="Logo">
    <span class="text-3xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
</div>

<div class="bg-white p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md border border-slate-100">
    <h2 class="text-2xl font-black text-center mb-8 text-slate-800">Authentification</h2>

    <?php if ($error): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 border border-red-100 text-center text-xs font-bold"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-5">
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Identifiant Email</label>
            <input type="email" name="email" class="w-full px-5 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all bg-slate-50" placeholder="nom@exemple.com" required>
        </div>

        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Mot de passe</label>
            <input type="password" name="password" id="passInput" class="w-full px-5 py-4 rounded-2xl border border-slate-200 outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all bg-slate-50" placeholder="••••••••" required>
        </div>

        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="showCheck" onclick="togglePassword()" class="w-4 h-4 cursor-pointer rounded border-slate-300">
                <label for="showCheck" class="text-xs text-slate-500 cursor-pointer">Afficher</label>
            </div>
            <a href="forgot_password.php" class="text-xs font-bold text-blue-600 hover:underline">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-bold text-lg hover:bg-blue-700 transition shadow-xl shadow-blue-100 active:scale-95">
            Se connecter
        </button>
    </form>

    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
        <p class="text-slate-500 text-sm mb-4">Pas encore de compte ?</p>
        <a href="register.php" class="inline-block w-full border-2 border-blue-600 text-blue-600 py-3 rounded-2xl font-black text-sm hover:bg-blue-50 transition uppercase tracking-widest text-center">
            Créer un profil
        </a>
    </div>
</div>

<script>
    function togglePassword() {
        const x = document.getElementById("passInput");
        x.type = (x.type === "password") ? "text" : "password";
    }
</script>
</body>
</html>