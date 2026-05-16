<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// Sécurité : Seul l'admin peut appeler cette API
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit(json_encode(['error' => 'Unauthorized']));
}

$uid = (int)$_GET['id'];

// Récupérer les 15 dernières activités
$stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY timestamp DESC LIMIT 15");
$stmt->execute([$uid]);
$logs = $stmt->fetchAll();

// On renvoie les logs au format JSON
header('Content-Type: application/json');
echo json_encode($logs);