<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Hachage sécurisé BCRYPT
    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'user')");
        if ($stmt->execute([$username, $email, $hash])) {
            header("Location: index.php?msg=register_success");
            exit;
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $error = "❌ Cet email ou nom d'utilisateur est déjà utilisé.";
        } else {
            $error = "❌ Erreur système : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Créer un profil | CyberSentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50 flex flex-col items-center justify-center min-h-screen p-6 font-sans">

<!-- LOGO ET NOM DU PROJET -->
<div class="flex items-center gap-3 mb-8">
    <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-12 h-12 object-contain" alt="CyberSentinel Logo">
    <h1 class="text-3xl font-black text-blue-600 tracking-tighter">CyberSentinel</h1>
</div>

<div class="bg-white p-10 rounded-[2.5rem] shadow-2xl w-full max-w-md border border-slate-100">
    <h2 class="text-2xl font-black text-center mb-2 text-slate-800">Rejoindre la plateforme</h2>
    <p class="text-center text-slate-400 text-sm mb-8">Créez votre empreinte numérique sécurisée</p>

    <?php if($error): ?>
        <div class="bg-red-50 text-red-600 p-4 rounded-2xl mb-6 border border-red-100 text-center text-xs font-bold animate-shake"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST" class="space-y-4">
        <!-- NOM D'UTILISATEUR -->
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Identifiant Public</label>
            <input type="text" name="username" placeholder="ex: Marc_Analyst"
                   class="w-full p-4 border rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 transition-all" required>
        </div>

        <!-- EMAIL -->
        <div>
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Adresse Email</label>
            <input type="email" name="email" placeholder="nom@exemple.com"
                   class="w-full p-4 border rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 transition-all" required>
        </div>

        <!-- MOT DE PASSE AVEC STRENGTH METER -->
        <div class="space-y-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1 ml-1">Clé d'Accès (MDP)</label>
            <input type="password" name="password" id="pwdInput" placeholder="••••••••"
                   class="w-full p-4 border rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 bg-slate-50 transition-all"
                   onkeyup="checkStrength(this.value)" required>

            <!-- BARRE DE FORCE -->
            <div class="px-1">
                <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden">
                    <div id="bar" class="h-full bg-red-500 transition-all duration-500 w-0"></div>
                </div>
                <p id="msg" class="text-[10px] font-bold text-slate-400 mt-1 uppercase tracking-tighter italic">Recommandation : Mot de passe complexe requis</p>
            </div>
        </div>

        <!-- OPTIONS : AFFICHER MDP ET MOT DE PASSE OUBLIÉ -->
        <div class="flex items-center justify-between px-1">
            <div class="flex items-center gap-2">
                <input type="checkbox" id="showCheck" onclick="togglePassword()" class="w-4 h-4 cursor-pointer rounded border-slate-300">
                <label for="showCheck" class="text-xs text-slate-500 cursor-pointer hover:text-slate-800 transition">Afficher</label>
            </div>
            <!-- Lien Mot de passe oublié (Optionnel mais recommandé pour l'UX) -->
            <a href="forgot_password.php" class="text-xs font-bold text-blue-600 hover:underline">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="w-full bg-slate-900 text-white p-4 rounded-2xl font-bold text-lg hover:bg-black transition-all shadow-xl shadow-slate-200 mt-4 active:scale-95">
            Créer mon compte
        </button>
    </form>

    <div class="mt-8 text-center border-t border-slate-50 pt-6">
        <p class="text-sm text-slate-500">Déjà membre de CyberSentinel ?</p>
        <a href="index.php" class="text-blue-600 font-bold hover:underline">Connectez-vous ici</a>
    </div>
</div>

<script>
    // FONCTION AFFICHER / MASQUER LE MOT DE PASSE
    function togglePassword() {
        const pwd = document.getElementById("pwdInput");
        pwd.type = (pwd.type === "password") ? "text" : "password";
    }

    // FONCTION FORCE DU MOT DE PASSE
    function checkStrength(p) {
        let s = 0;
        if(p.length > 7) s += 25; // Longueur
        if(/[A-Z]/.test(p)) s += 25; // Majuscule
        if(/[0-9]/.test(p)) s += 25; // Chiffre
        if(/[^A-Za-z0-9]/.test(p)) s += 25; // Caractère spécial

        const bar = document.getElementById('bar');
        const msg = document.getElementById('msg');

        bar.style.width = s + '%';

        if(s < 50) {
            bar.className = "h-full transition-all bg-red-500";
            msg.innerText = "Niveau de sécurité : Faible";
            msg.style.color = "#ef4444";
        } else if(s < 100) {
            bar.className = "h-full transition-all bg-yellow-500";
            msg.innerText = "Niveau de sécurité : Moyen";
            msg.style.color = "#d97706";
        } else {
            bar.className = "h-full transition-all bg-emerald-500";
            msg.innerText = "Niveau de sécurité : Optimal";
            msg.style.color = "#10b981";
        }
    }
</script>
</body>
</html>