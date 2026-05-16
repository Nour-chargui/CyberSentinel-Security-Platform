<?php
require 'config/db.php';

try {
    // 1. Supprimer l'ancien admin s'il existe pour éviter les doublons
    $pdo->prepare("DELETE FROM users WHERE email = 'admin@platform.com' OR username = 'admin'")->execute();

    // 2. Créer le hash propre pour 'Admin@123'
    $new_hash = password_hash('Admin@123', PASSWORD_BCRYPT);

    // 3. Insérer l'admin proprement avec le rôle 'admin'
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES ('admin', 'admin@platform.com', ?, 'admin')");
    $stmt->execute([$new_hash]);

    echo "<h2 style='color:green'>✅ RECOUVREMENT RÉUSSI !</h2>";
    echo "<p>L'utilisateur <strong>admin@platform.com</strong> a été réinitialisé avec le mot de passe <strong>Admin@123</strong></p>";
    echo "<a href='index.php'>Aller à la page de connexion</a>";
} catch (Exception $e) {
    die("Erreur : " . $e->getMessage());
}