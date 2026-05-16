<?php
require 'config/db.php';
if (!isset($_SESSION['user_id'])) exit;

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';
$product_id = (int)($_GET['id'] ?? 0);

if ($action == 'add') {
    $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
    $stmt->execute([$user_id, $product_id]);
} elseif ($action == 'remove') {
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
}

header("Location: shop.php?msg=cart_updated");