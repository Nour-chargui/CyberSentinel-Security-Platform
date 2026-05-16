<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if ($_SESSION['role'] !== 'admin') exit;

$uid = (int)$_GET['id'];

// 1. Infos Utilisateur
$u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$u->execute([$uid]);
$user = $u->fetch();

// 2. RÉCUPÉRATION DU SCORE HISTORIQUE (Le plus récent ou le plus élevé)
// Au lieu de recalculer, on prend ce que le système a détecté
$stmt_risk = $pdo->prepare("SELECT MAX(risk_score) FROM alerts WHERE user_id = ?");
$stmt_risk->execute([$uid]);
$risk = $stmt_risk->fetchColumn() ?: 0;

// 3. COMPTAGE DES ÉCHECS POUR LA PREUVE (Forensics)
$stmt_fail = $pdo->prepare("SELECT COUNT(*) FROM logins WHERE user_id = ? AND status = 'failed'");
$stmt_fail->execute([$uid]);
$failed_total = $stmt_fail->fetchColumn();

// 4. RÉCUPÉRATION DES ACTIVITÉS (Pour le Live Stream)
$stmt_act = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10");
$stmt_act->execute([$uid]);
$logs = $stmt_act->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr" class="bg-slate-950 text-slate-200">
<head>
    <title>Forensic Live Stream | CyberSentinel</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-10 font-sans">
<div class="max-w-6xl mx-auto">
    <!-- HEADER -->
    <header class="flex justify-between items-end mb-12 border-b border-slate-800 pb-8">
        <div>
            <h1 class="text-4xl font-black text-white uppercase tracking-tighter">
                LIVE AUDIT : <span class="text-blue-500"><?= h($user['username']) ?></span>
            </h1>
            <div class="flex items-center gap-2 mt-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-ping"></span>
                <p class="text-slate-500 font-mono text-[10px] uppercase tracking-widest">Surveillance Comportementale en Temps Réel</p>
            </div>
        </div>
        <a href="admin.php" class="bg-slate-800 px-6 py-3 rounded-xl font-bold hover:bg-slate-700 transition text-sm">Quitter l'audit</a>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Widgets Latéraux -->
        <div class="space-y-6">
            <div class="bg-slate-900 p-6 rounded-3xl border border-red-500/30 text-center">
                <p class="text-red-500 text-[10px] font-black uppercase mb-1">Risk Score</p>
                <p class="text-6xl font-black"><?= $risk ?>%</p>
            </div>
            <div class="bg-slate-900 p-8 rounded-3xl border border-slate-800">
                <h4 class="text-xs font-black text-slate-500 uppercase mb-4 tracking-widest">Identité Agent</h4>
                <div class="flex items-center gap-4">
                    <img src="<?= h($user['profile_photo']) ?>" class="w-12 h-12 rounded-full border border-slate-700">
                    <div>
                        <p class="font-bold text-white"><?= h($user['username']) ?></p>
                        <p class="text-[10px] text-slate-500"><?= h($user['email']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- JOURNAL DES LOGS TEMPS RÉEL -->
        <div class="lg:col-span-2 bg-slate-900 p-8 rounded-3xl border border-slate-800 relative overflow-hidden">
            <div class="flex justify-between items-center mb-8 border-b border-slate-800 pb-4">
                <h3 class="font-black text-xl text-white italic">EVENT_LOG_STREAM</h3>
                <span class="text-[10px] font-mono bg-blue-500/10 text-blue-400 px-2 py-1 rounded">POLLING_ACTIVE: 3000ms</span>
            </div>

            <!-- CONTENEUR DES LOGS (Sera rempli par JS) -->
            <div id="logContainer" class="space-y-3 font-mono text-[11px]">
                <div class="text-slate-600 animate-pulse text-center py-10">Initialisation du flux sécurisé...</div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT DE MISE À JOUR LIVE -->
<script>
    const userId = <?= $uid ?>;

    async function fetchLogs() {
        try {
            const response = await fetch(`api_get_logs.php?id=${userId}`);
            const logs = await response.json();

            const container = document.getElementById('logContainer');
            container.innerHTML = ''; // On vide pour rafraîchir

            logs.forEach(log => {
                const div = document.createElement('div');
                div.className = "flex items-center gap-4 p-3 bg-white/5 rounded-xl border border-white/5 hover:bg-white/10 transition animate__animated animate__fadeInIn";
                div.innerHTML = `
                        <span class="text-blue-500 font-black">[${log.timestamp.split(' ')[1]}]</span>
                        <span class="text-slate-300 font-bold tracking-tight">${log.action_performed.toUpperCase()}</span>
                        <span class="text-slate-600 ml-auto text-[10px] hidden md:block">${log.page_url}</span>
                        <span class="w-2 h-2 bg-blue-400 rounded-full shadow-[0_0_8px_#3b82f6]"></span>
                    `;
                container.appendChild(div);
            });
        } catch (error) {
            console.error("Erreur de flux :", error);
        }
    }

    // Rafraîchir toutes les 3 secondes
    setInterval(fetchLogs, 3000);
    // Premier chargement immédiat
    fetchLogs();
</script>
</body>
</html>