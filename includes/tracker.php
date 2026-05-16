<?php
function trackActivity($pdo, $userId, $action) {
    try {
        $stmt = $pdo->prepare("INSERT INTO activities (user_id, page_url, action_performed) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $_SERVER['REQUEST_URI'], $action]);
    } catch (Exception $e) {
        // En mode Senior, on ne fait pas planter l'utilisateur pour un log
        error_log("Erreur de tracking : " . $e->getMessage());
    }
}

function logLogin($pdo, $userId, $status) {
    try {
        $stmt = $pdo->prepare("INSERT INTO logins (user_id, ip_address, user_agent, status) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT'], $status]);
    } catch (Exception $e) {
        error_log("Erreur log login.");
    }
}