<?php
require_once 'config/db.php';
require_once 'includes/functions.php';
require_once 'includes/tracker.php';

if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit; }
$user_id = $_SESSION['user_id'];

// 1. RÉCUPÉRATION DU RISQUE (Source de vérité)
$stmt_risk = $pdo->prepare("SELECT MAX(risk_score) FROM alerts WHERE user_id = ?");
$stmt_risk->execute([$user_id]);
$risk = (int)$stmt_risk->fetchColumn() ?: 0;

// 2. BLOCAGE CRITIQUE : Si Mariem a 100%, elle est arrêtée ici avec un bouton retour
if ($risk >= 80) {
    trackActivity($pdo, $user_id, "TENTATIVE D'ACCÈS CHECKOUT BLOQUÉE (Risque: $risk%)");
    die("
    <!DOCTYPE html>
    <html lang='fr'>
    <head><script src='https://cdn.tailwindcss.com'></script></head>
    <body class='bg-slate-900 flex items-center justify-center min-h-screen p-6'>
        <div class='bg-white p-12 rounded-[3rem] shadow-2xl max-w-md w-full text-center'>
            <div style='font-size: 70px; margin-bottom: 20px;'>🚫</div>
            <h1 style='font-family: sans-serif; font-weight: 900; color: #1e293b; font-size: 22px; margin-bottom: 15px; text-transform: uppercase;'>Accès Financier Interdit</h1>
            <p style='color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.6;'>
                Votre score de menace est de <strong>$risk%</strong>.<br>
                Les transactions sont suspendues pour protéger l'intégrité de vos fonds.
            </p>
            <a href='dashboard.php' style='display: block; background: #2563eb; color: white; text-decoration: none; padding: 18px; border-radius: 15px; font-weight: 800; font-family: sans-serif; box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); text-transform: uppercase;'>
                ⬅️ Retourner à mon Dashboard
            </a>
        </div>
    </body>
    </html>
    ");
}

// 3. RÉCUPÉRER LE PANIER
$stmt = $pdo->prepare("SELECT c.*, p.name, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?");
$stmt->execute([$user_id]);
$items = $stmt->fetchAll();

$total = 0;
foreach($items as $i) { $total += (float)$i['price'] * (int)$i['quantity']; }

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($items)) {
    $method = $_POST['payment_method'];

    // Sécurité : Bloquer la carte si risque > 50 (mais < 80)
    if ($method == 'card' && $risk > 50) {
        $error = "🚫 Paiement par carte refusé (Risque trop élevé). Veuillez choisir la livraison.";
    } else {
        try {
            $pdo->beginTransaction();
            foreach($items as $i) {
                $pdo->prepare("INSERT INTO orders (user_id, product_id, payment_method, order_date) VALUES (?, ?, ?, NOW())")
                        ->execute([$user_id, $i['product_id'], $method]);
            }
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);
            $pdo->commit();
            header("Location: dashboard.php?purchase=success"); exit;
        } catch (Exception $e) { $pdo->rollBack(); $error = "Erreur système."; }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Paiement Sécurisé | CyberSentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="bg-slate-50 min-h-screen p-4 md:p-8">

<div class="max-w-2xl mx-auto">
    <div class="flex justify-between items-center mb-8">
        <div class="flex items-center gap-3">
            <img src="https://cdn-icons-png.flaticon.com/512/1055/1055644.png" class="w-10 h-10">
            <span class="text-2xl font-black text-blue-600 tracking-tighter">CyberSentinel</span>
        </div>
        <a href="shop.php" class="text-slate-400 hover:text-blue-600 font-bold text-sm">✕ Annuler</a>
    </div>

    <div class="bg-white p-8 md:p-12 rounded-[2.5rem] shadow-2xl border border-slate-100">
        <h2 class="text-3xl font-black mb-8">Paiement</h2>

        <?php if(empty($items)): ?>
            <div class="text-center py-10">
                <p class="text-slate-500 mb-8">Votre panier est vide.</p>
                <a href="shop.php" class="bg-blue-600 text-white px-8 py-3 rounded-xl font-bold">Retour Boutique</a>
            </div>
        <?php else: ?>
            <!-- RÉCAPITULATIF -->
            <div class="mb-8 bg-slate-50 p-6 rounded-3xl border border-slate-100">
                <?php foreach($items as $i): ?>
                    <div class="flex justify-between text-sm py-1">
                        <span class="text-slate-600"><?= h($i['name']) ?> (x<?= $i['quantity'] ?>)</span>
                        <span class="font-bold text-slate-800"><?= number_format($i['price'] * $i['quantity'], 2) ?> €</span>
                    </div>
                <?php endforeach; ?>
                <div class="border-t border-slate-200 mt-4 pt-4 flex justify-between items-center">
                    <span class="text-xl font-black">Total :</span>
                    <span class="text-2xl font-black text-blue-600"><?= number_format($total, 2) ?> €</span>
                </div>
            </div>

            <?php if($error): ?>
                <div class="bg-red-50 border border-red-100 p-4 rounded-xl mb-6">
                    <p class="text-red-600 text-sm font-bold"><?= $error ?></p>
                    <a href="dashboard.php" class="text-xs text-red-700 underline font-black uppercase mt-2 inline-block">Retour au Dashboard</a>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="card" class="hidden peer" <?= ($risk > 50) ? 'disabled' : 'checked' ?>>
                        <div class="p-6 border-2 rounded-2xl text-center peer-checked:border-blue-600 peer-checked:bg-blue-50 <?= ($risk > 50) ? 'opacity-40 grayscale' : '' ?>">
                            <div class="text-3xl">💳</div>
                            <span class="font-bold text-slate-700">Carte</span>
                            <?php if($risk > 50): ?><p class="text-[9px] text-red-500 font-bold uppercase">Risqué</p><?php endif; ?>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="payment_method" value="delivery" class="hidden peer" <?= ($risk > 50) ? 'checked' : '' ?>>
                        <div class="p-6 border-2 rounded-2xl text-center peer-checked:border-emerald-600 peer-checked:bg-emerald-50">
                            <div class="text-3xl">🚚</div>
                            <span class="font-bold text-slate-700">Livraison</span>
                        </div>
                    </label>
                </div>

                <button type="submit" class="w-full bg-slate-900 text-white p-5 rounded-2xl font-bold text-lg hover:bg-black transition shadow-xl active:scale-95">
                    Payer <?= number_format($total, 2) ?> €
                </button>

                <!-- BOUTON RETOUR GARANTI -->
                <a href="dashboard.php" class="block text-center text-slate-400 font-bold text-xs uppercase tracking-widest mt-4">
                    Retourner à mon profil
                </a>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>