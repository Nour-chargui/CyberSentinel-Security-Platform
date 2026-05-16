<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

// 1. SÉCURITÉ : Vérification Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// 2. ACTIONS ADMIN (Blocage / Suppression)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $tid = (int)$_GET['id'];
    try {
        if ($_GET['action'] == 'block') {
            $pdo->prepare("UPDATE users SET is_blocked = NOT is_blocked WHERE id = ?")->execute([$tid]);
        }
        elseif ($_GET['action'] == 'delete') {
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$tid]);
        }
        header("Location: admin.php?success=1");
        exit;
    } catch (PDOException $e) {
        $error_msg = "Erreur : " . $e->getMessage();
    }
}

// 3. RÉCUPÉRATION DES UTILISATEURS
$users = $pdo->query("SELECT u.*, 
    (SELECT risk_score FROM alerts WHERE user_id = u.id ORDER BY id DESC LIMIT 1) as risk 
    FROM users u WHERE role = 'user' ORDER BY risk DESC")->fetchAll();

// 4. STATS POUR LES CARTES
$total_users = count($users);
$total_alerts = $pdo->query("SELECT COUNT(*) FROM alerts WHERE risk_score >= 80")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr" class="bg-slate-950">
<head>
    <meta charset="UTF-8">
    <title>War Room | CyberSentinel</title>
    <!-- CHARGEMENT DE TAILWIND ET CHART.JS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="p-4 md:p-10 text-slate-200 min-h-screen">

<div class="max-w-7xl mx-auto">
    <!-- HEADER -->
    <header class="flex justify-between items-center mb-10 border-b border-slate-800 pb-6">
        <div>
            <h1 class="text-4xl font-black text-white tracking-tighter">WAR ROOM <span class="text-blue-500">CONTROL</span></h1>
            <p class="text-slate-500 text-xs font-mono uppercase">Système de Surveillance en Temps Réel</p>
        </div>
        <div class="flex gap-4">
            <a href="admin_settings.php" class="bg-blue-600/10 text-blue-400 border border-blue-500/20 px-4 py-2 rounded-xl font-bold text-xs hover:bg-blue-600 hover:text-white transition">PARAMÈTRES</a>
            <a href="dashboard.php" class="bg-slate-800 px-6 py-2 rounded-xl font-bold text-xs hover:bg-slate-700 transition">RETOUR</a>
        </div>
    </header>

    <!-- CARTES DE STATISTIQUES -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-slate-900 p-6 rounded-3xl border border-slate-800">
            <p class="text-slate-500 text-[10px] font-black uppercase">Agents Actifs</p>
            <p class="text-4xl font-black"><?= $total_users ?></p>
        </div>
        <div class="bg-slate-900 p-6 rounded-3xl border border-red-900/30">
            <p class="text-red-500 text-[10px] font-black uppercase">Menaces Critiques</p>
            <p class="text-4xl font-black text-red-500"><?= $total_alerts ?></p>
        </div>
        <div class="bg-slate-900 p-6 rounded-3xl border border-blue-900/30">
            <p class="text-blue-500 text-[10px] font-black uppercase">Statut Système</p>
            <p class="text-4xl font-black text-blue-500">OPÉRATIONNEL</p>
        </div>
    </div>

    <!-- LE GRAPHIQUE (FIXED ID) -->
    <div class="bg-slate-900 p-8 rounded-3xl border border-slate-800 mb-8 shadow-2xl">
        <h3 class="font-bold text-blue-400 mb-6 flex items-center gap-2 text-sm uppercase tracking-widest">
            <span class="w-2 h-2 bg-blue-500 rounded-full animate-ping"></span> Flux de Sécurité (Live Monitoring)
        </h3>
        <div style="height: 200px; position: relative;">
            <canvas id="securityLiveChart"></canvas>
        </div>
    </div>

    <!-- TABLEAU DES UTILISATEURS -->
    <div class="bg-slate-900 rounded-3xl border border-slate-800 overflow-hidden shadow-2xl">
        <table class="w-full text-left">
            <thead class="bg-slate-800/50 text-[10px] font-black uppercase text-slate-500">
            <tr>
                <th class="p-6">Utilisateur</th>
                <th class="p-6">Indice de Risque</th>
                <th class="p-6">Statut</th>
                <th class="p-6 text-right">Actions</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
            <?php foreach($users as $u):
                $r = $u['risk'] ?? 0;
                $risk_color = ($r >= 80) ? 'text-red-500' : (($r >= 50) ? 'text-yellow-500' : 'text-emerald-500');
                ?>
                <tr class="hover:bg-white/5 transition">
                    <td class="p-6 flex items-center gap-4">
                        <img src="<?= h($u['profile_photo']) ?>" class="w-10 h-10 rounded-full border border-slate-700">
                        <div>
                            <p class="font-bold text-white text-sm"><?= h($u['username']) ?></p>
                            <p class="text-[10px] text-slate-500 font-mono"><?= h($u['email']) ?></p>
                        </div>
                    </td>
                    <td class="p-6">
                        <span class="font-black text-lg <?= $risk_color ?>"><?= $r ?>%</span>
                    </td>
                    <td class="p-6 text-xs font-bold">
                            <span class="px-3 py-1 rounded-full <?= $u['is_blocked'] ? 'bg-red-500/20 text-red-500' : 'bg-emerald-500/20 text-emerald-500' ?>">
                                <?= $u['is_blocked'] ? 'BLOQUÉ' : 'ACTIF' ?>
                            </span>
                    </td>
                    <td class="p-6 text-right space-x-3">
                        <a href="admin_audit.php?id=<?= $u['id'] ?>" class="text-blue-400 hover:text-white text-[10px] font-black uppercase border border-blue-400/30 px-3 py-1 rounded-lg">Audit</a>
                        <a href="admin.php?action=block&id=<?= $u['id'] ?>" class="text-yellow-500 hover:text-white text-[10px] font-black uppercase border border-yellow-500/30 px-3 py-1 rounded-lg">Bloquer</a>
                        <a href="admin.php?action=delete&id=<?= $u['id'] ?>" onclick="return confirm('Confirmer la suppression ?')" class="text-red-600 hover:text-white text-[10px] font-black uppercase border border-red-600/30 px-3 py-1 rounded-lg text-red-500">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JAVASCRIPT DU GRAPHIQUE (CORRIGÉ) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('securityLiveChart').getContext('2d');

        let liveData = [15, 25, 20, 35, 30, 45, 40, 55, 50, 65];

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', 'LIVE'],
                datasets: [{
                    label: 'Requêtes Entrantes',
                    data: liveData,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { display: false },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: 'rgba(255,255,255,0.05)' },
                        ticks: { color: '#475569', font: { size: 10 } }
                    }
                }
            }
        });

        // Simulation de mise à jour toutes les 3 secondes
        setInterval(() => {
            liveData.shift();
            liveData.push(Math.floor(Math.random() * 40) + 20);
            chart.update('none'); // Update fluide
        }, 3000);
    });
</script>
</body>
</html>