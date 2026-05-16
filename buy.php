<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/tracker.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. RÉCUPÉRER LE RISQUE RÉEL (On force la lecture en base)
$stmt = $pdo->prepare("SELECT MAX(risk_score) FROM alerts WHERE user_id = ?");
$stmt->execute([$user_id]);
$risk = (int)$stmt->fetchColumn();

// 2. LOGIQUE DE BLOCAGE CRITIQUE
if ($risk >= 80) {
    trackActivity($pdo, $user_id, "BLOCAGE TRANSACTION (Risque: $risk%)");

    // ON ARRÊTE LE SCRIPT ET ON FORCE L'AFFICHAGE DU BOUTON EN HTML PUR
    die("
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <title>SÉCURITÉ ACTIVÉE</title>
        <script src='https://cdn.tailwindcss.com'></script>
    </head>
    <body class='bg-slate-900 flex items-center justify-center min-h-screen'>
        <div class='bg-white p-12 rounded-[3rem] shadow-2xl max-w-md w-full text-center'>
            <div style='font-size: 80px; margin-bottom: 20px;'>🚫</div>
            <h1 style='font-family: sans-serif; font-weight: 900; color: #1e293b; font-size: 24px; margin-bottom: 15px;'>
                TRANSACTION BLOQUÉE
            </h1>
            <p style='color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.6;'>
                Votre score de risque est de <strong>$risk%</strong>.<br> 
                Par mesure de sécurité, ce compte ne peut plus effectuer d'achats.
            </p>

            <!-- LE BOUTON : STYLE INLINE POUR ÊTRE SÛR QU'IL S'AFFICHE -->
            <a href='dashboard.php' style='
                display: block; 
                background-color: #2563eb; 
                color: white; 
                text-decoration: none; 
                padding: 18px 25px; 
                border-radius: 15px; 
                font-weight: 800; 
                font-family: sans-serif;
                box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
                text-transform: uppercase;
                letter-spacing: 1px;
            '>
                ⬅️ Retour à mon profil
            </a>
            
            <p style='margin-top: 25px; font-size: 10px; color: #cbd5e1; font-family: monospace;'>
                PROTECTION CYBERSENTINEL ACTIVE
            </p>
        </div>
    </body>
    </html>
    ");
}

// 3. SI LE RISQUE EST INFÉRIEUR À 80 : ON ENREGISTRE L'ACHAT
if ($product_id > 0) {
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id, order_date) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $product_id]);

    trackActivity($pdo, $user_id, "Achat validé");

    header("Location: dashboard.php?purchase=success");
    exit;
} else {
    header("Location: shop.php");
    exit;
}