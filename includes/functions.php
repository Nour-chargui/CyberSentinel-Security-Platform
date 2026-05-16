<?php
// Protection contre la redéclaration des fonctions
if (!function_exists('h')) {
    function h($s) {
        return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('calculateRiskScore')) {
    function calculateRiskScore($pdo, $userId) {
        // 1. VÉRIFIER LE RÔLE (Immunité Admin)
        $stmt_role = $pdo->prepare("SELECT role FROM users WHERE id = ?");
        $stmt_role->execute([$userId]);
        $role = $stmt_role->fetchColumn();

        if ($role === 'admin') {
            return 0; // L'admin a toujours 0% de risque
        }

        // 2. LOGIQUE DE CALCUL POUR LES USERS
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM logins WHERE user_id = ? AND status = 'failed'");
        $stmt->execute([$userId]);
        $failedCount = $stmt->fetchColumn();

        $currentScore = min($failedCount * 20, 100);

        // 3. SAUVEGARDE (Seulement si c'est un user)
        $stmt_check = $pdo->prepare("SELECT id FROM alerts WHERE user_id = ?");
        $stmt_check->execute([$userId]);
        if ($stmt_check->fetch()) {
            $pdo->prepare("UPDATE alerts SET risk_score = ? WHERE user_id = ?")->execute([$currentScore, $userId]);
        } else {
            $pdo->prepare("INSERT INTO alerts (user_id, risk_score, alert_type) VALUES (?, ?, 'Analyse Active')")->execute([$userId, $currentScore]);
        }

        return $currentScore;
    }
}

if (!function_exists('getPasswordStrength')) {
    function getPasswordStrength($pwd) {
        $s = 0;
        if(strlen($pwd) > 7) $s += 25;
        if(preg_match('/[A-Z]/', $pwd)) $s += 25;
        if(preg_match('/[0-9]/', $pwd)) $s += 25;
        if(preg_match('/[^A-Za-z0-9]/', $pwd)) $s += 25;
        return $s;
    }
}