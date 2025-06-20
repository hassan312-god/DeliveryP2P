<?php
/**
 * Tableau de bord administrateur
 * Interface d'administration pour LivraisonP2P
 */

require_once 'config.php';
require_once 'supabase-api.php';

// Vérifier l'authentification (simplifié pour cet exemple)
session_start();

$api = new SupabaseAPI();

// Récupérer les statistiques
$stats = $api->getGeneralStats();
$healthCheck = $api->healthCheck();

// Récupérer les dernières activités
$recentDeliveries = $api->getDeliveries([], 'created_at.desc', 10);
$recentUsers = $api->getProfiles([], 'created_at.desc', 10);
$recentQRCodes = $api->getQRCodes([], 'created_at.desc', 10);

// Traitement des actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'cleanup_old_qr_codes':
            $result = $api->rpc('cleanup_old_qr_codes', ['p_days' => 90]);
            if ($result['success']) {
                $message = 'Nettoyage effectué : ' . $result['data'] . ' QR codes supprimés';
            } else {
                $error = 'Erreur lors du nettoyage : ' . $result['error'];
            }
            break;
            
        case 'export_data':
            $exportType = $_POST['export_type'] ?? '';
            switch ($exportType) {
                case 'users':
                    $data = $api->getProfiles();
                    break;
                case 'deliveries':
                    $data = $api->getDeliveries();
                    break;
                case 'qr_codes':
                    $data = $api->getQRCodes();
                    break;
                default:
                    $error = 'Type d\'export invalide';
                    break;
            }
            
            if (!empty($data) && $data['success']) {
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="export_' . $exportType . '_' . date('Y-m-d') . '.json"');
                echo json_encode($data['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                exit;
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-blue-600">
                        <i class="fas fa-crown mr-2"></i><?= APP_NAME ?> - Admin
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i><?= getSenegalTime() ?>
                    </span>
                    <span class="text-sm text-gray-600">
                        <i class="fas fa-server mr-1"></i><?= $healthCheck['api_status'] ?>
                    </span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <i class="fas fa-check-circle mr-2"></i><?= $message ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Statistiques générales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php if ($stats['success']): ?>
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Utilisateurs</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['data']['total_users'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-truck text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Livraisons</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['data']['total_deliveries'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-qrcode text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">QR Codes</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= $stats['data']['total_qr_codes'] ?? 0 ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-money-bill-wave text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Paiements</p>
                            <p class="text-2xl font-semibold text-gray-900"><?= formatCurrency($stats['data']['total_amount'] ?? 0) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Graphiques -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Graphique des utilisateurs par rôle -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Utilisateurs par rôle</h3>
                <canvas id="usersChart" width="400" height="200"></canvas>
            </div>

            <!-- Graphique des livraisons par statut -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Livraisons par statut</h3>
                <canvas id="deliveriesChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Actions d'administration -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions d'administration</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Nettoyage des QR codes -->
                <form method="POST" class="space-y-2">
                    <input type="hidden" name="action" value="cleanup_old_qr_codes">
                    <button type="submit" class="w-full bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                        <i class="fas fa-broom mr-2"></i>Nettoyer les anciens QR codes
                    </button>
                    <p class="text-xs text-gray-600">Supprime les QR codes de plus de 90 jours non favoris</p>
                </form>

                <!-- Export des données -->
                <div class="space-y-2">
                    <select id="exportType" class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Sélectionner un type</option>
                        <option value="users">Utilisateurs</option>
                        <option value="deliveries">Livraisons</option>
                        <option value="qr_codes">QR Codes</option>
                    </select>
                    <form method="POST" id="exportForm">
                        <input type="hidden" name="action" value="export_data">
                        <input type="hidden" name="export_type" id="exportTypeHidden">
                        <button type="submit" class="w-full bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                            <i class="fas fa-download mr-2"></i>Exporter les données
                        </button>
                    </form>
                </div>

                <!-- Vérification de santé -->
                <div class="space-y-2">
                    <div class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded">
                        <i class="fas fa-heartbeat mr-2"></i>Statut API: 
                        <span class="font-semibold <?= $healthCheck['api_status'] === 'healthy' ? 'text-green-600' : 'text-red-600' ?>">
                            <?= ucfirst($healthCheck['api_status']) ?>
                        </span>
                    </div>
                    <p class="text-xs text-gray-600">Dernière vérification: <?= $healthCheck['timestamp'] ?></p>
                </div>
            </div>
        </div>

        <!-- Activité récente -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Derniers utilisateurs -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Derniers utilisateurs</h3>
                <?php if ($recentUsers['success'] && !empty($recentUsers['data'])): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentUsers['data'], 0, 5) as $user): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user text-blue-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                    </p>
                                    <p class="text-xs text-gray-600"><?= htmlspecialchars($user['email']) ?></p>
                                </div>
                                <span class="text-xs text-gray-500"><?= formatDate($user['created_at'], 'd/m') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Aucun utilisateur récent</p>
                <?php endif; ?>
            </div>

            <!-- Dernières livraisons -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Dernières livraisons</h3>
                <?php if ($recentDeliveries['success'] && !empty($recentDeliveries['data'])): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentDeliveries['data'], 0, 5) as $delivery): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-truck text-green-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        Livraison #<?= substr($delivery['id'], 0, 8) ?>
                                    </p>
                                    <p class="text-xs text-gray-600"><?= htmlspecialchars($delivery['pickup_address']) ?></p>
                                </div>
                                <span class="text-xs px-2 py-1 rounded-full bg-<?= $DELIVERY_STATUSES[$delivery['status']]['color'] ?? 'gray' ?>-100 text-<?= $DELIVERY_STATUSES[$delivery['status']]['color'] ?? 'gray' ?>-800">
                                    <?= $DELIVERY_STATUSES[$delivery['status']]['label'] ?? $delivery['status'] ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Aucune livraison récente</p>
                <?php endif; ?>
            </div>

            <!-- Derniers QR codes -->
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Derniers QR codes</h3>
                <?php if ($recentQRCodes['success'] && !empty($recentQRCodes['data'])): ?>
                    <div class="space-y-3">
                        <?php foreach (array_slice($recentQRCodes['data'], 0, 5) as $qrCode): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-qrcode text-purple-600 text-sm"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($qrCode['title']) ?>
                                    </p>
                                    <p class="text-xs text-gray-600"><?= $QR_CODE_TYPES[$qrCode['type']]['label'] ?? $qrCode['type'] ?></p>
                                </div>
                                <span class="text-xs text-gray-500"><?= formatDate($qrCode['created_at'], 'd/m') ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">Aucun QR code récent</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Configuration des graphiques
        <?php if ($stats['success']): ?>
        // Graphique des utilisateurs par rôle
        const usersCtx = document.getElementById('usersChart').getContext('2d');
        new Chart(usersCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_keys($stats['data']['users_by_role'] ?? [])) ?>,
                datasets: [{
                    data: <?= json_encode(array_values($stats['data']['users_by_role'] ?? [])) ?>,
                    backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Graphique des livraisons par statut
        const deliveriesCtx = document.getElementById('deliveriesChart').getContext('2d');
        new Chart(deliveriesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($stats['data']['deliveries_by_status'] ?? [])) ?>,
                datasets: [{
                    label: 'Nombre de livraisons',
                    data: <?= json_encode(array_values($stats['data']['deliveries_by_status'] ?? [])) ?>,
                    backgroundColor: ['#F59E0B', '#3B82F6', '#8B5CF6', '#F97316', '#10B981', '#EF4444']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>

        // Gestion de l'export
        document.getElementById('exportType').addEventListener('change', function() {
            document.getElementById('exportTypeHidden').value = this.value;
        });

        document.getElementById('exportForm').addEventListener('submit', function(e) {
            const exportType = document.getElementById('exportType').value;
            if (!exportType) {
                e.preventDefault();
                alert('Veuillez sélectionner un type d\'export');
            }
        });
    </script>
</body>
</html> 