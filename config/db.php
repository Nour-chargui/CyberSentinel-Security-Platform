<?php
$host = '127.0.0.1'; $db = 'intelligence_db'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) { die("Erreur Système."); }

session_start();

// SÉCURITÉ SENIOR : Vérifier si l'utilisateur en session existe encore vraiment
if (isset($_SESSION['user_id'])) {
    $checkUser = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $checkUser->execute([$_SESSION['user_id']]);
    if (!$checkUser->fetch()) {
        session_destroy();
        header("Location: index.php");
        exit;
    }
}