<?php
require 'config/db.php';
require 'includes/tracker.php';

if(isset($_SESSION['user_id'])) {
    trackActivity($pdo, $_SESSION['user_id'], "Déconnexion sécurisée");
    session_destroy();
}
header("Location: index.php");
exit;