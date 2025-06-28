<?php
/**
 * Gestionnaire de sauvegarde pour LivraisonP2P
 * Sauvegarde et restauration des données Supabase
 */

require_once 'config.php';
require_once 'supabase-api.php';

class BackupManager {
    private $api;
    private $backupDir;
    
    public function __construct() {
        $this->api = new SupabaseAPI();
        $this->backupDir = 'backups/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }
    
    /**
     * Créer une sauvegarde complète
     */
    public function createFullBackup() {
        $backupId = 'backup_' . date('Y-m-d_H-i-s');
        $backupData = [
            'metadata' => [
                'backup_id' => $backupId,
                'created_at' => getSenegalTime(),
                'version' => APP_VERSION,
                'tables' => []
            ],
            'data' => []
        ];
        
        try {
            // Sauvegarder les profils
            $profiles = $this->api->getProfiles();
            if ($profiles['success']) {
                $backupData['data']['profiles'] = $profiles['data'];
                $backupData['metadata']['tables'][] = 'profiles';
            }
            
            // Sauvegarder les livraisons
            $deliveries = $this->api->getDeliveries();
            if ($deliveries['success']) {
                $backupData['data']['deliveries'] = $deliveries['data'];
                $backupData['metadata']['tables'][] = 'deliveries';
            }
            
            // Sauvegarder les QR codes
            $qrCodes = $this->api->getQRCodes();
            if ($qrCodes['success']) {
                $backupData['data']['qr_codes'] = $qrCodes['data'];
                $backupData['metadata']['tables'][] = 'qr_codes';
            }
            
            // Sauvegarder les paiements
            $payments = $this->api->getPayments();
            if ($payments['success']) {
                $backupData['data']['payments'] = $payments['data'];
                $backupData['metadata']['tables'][] = 'payments';
            }
            
            // Sauvegarder les notifications
            $notifications = $this->api->getNotifications();
            if ($notifications['success']) {
                $backupData['data']['notifications'] = $notifications['data'];
                $backupData['metadata']['tables'][] = 'notifications';
            }
            
            // Sauvegarder les messages
            $messages = $this->api->select('messages');
            if ($messages['success']) {
                $backupData['data']['messages'] = $messages['data'];
                $backupData['metadata']['tables'][] = 'messages';
            }
            
            // Sauvegarder les avis
            $reviews = $this->api->select('reviews');
            if ($reviews['success']) {
                $backupData['data']['reviews'] = $reviews['data'];
                $backupData['metadata']['tables'][] = 'reviews';
            }
            
            // Sauvegarder les localisations utilisateurs
            $userLocations = $this->api->select('user_locations');
            if ($userLocations['success']) {
                $backupData['data']['user_locations'] = $userLocations['data'];
                $backupData['metadata']['tables'][] = 'user_locations';
            }
            
            // Sauvegarder les paramètres de prix
            $pricingSettings = $this->api->select('pricing_settings');
            if ($pricingSettings['success']) {
                $backupData['data']['pricing_settings'] = $pricingSettings['data'];
                $backupData['metadata']['tables'][] = 'pricing_settings';
            }
            
            // Sauvegarder les zones de couverture
            $coverageZones = $this->api->select('coverage_zones');
            if ($coverageZones['success']) {
                $backupData['data']['coverage_zones'] = $coverageZones['data'];
                $backupData['metadata']['tables'][] = 'coverage_zones';
            }
            
            // Calculer les statistiques
            $backupData['metadata']['statistics'] = [
                'total_users' => count($backupData['data']['profiles'] ?? []),
                'total_deliveries' => count($backupData['data']['deliveries'] ?? []),
                'total_qr_codes' => count($backupData['data']['qr_codes'] ?? []),
                'total_payments' => count($backupData['data']['payments'] ?? []),
                'total_notifications' => count($backupData['data']['notifications'] ?? []),
                'total_messages' => count($backupData['data']['messages'] ?? []),
                'total_reviews' => count($backupData['data']['reviews'] ?? [])
            ];
            
            // Sauvegarder le fichier
            $filename = $this->backupDir . $backupId . '.json';
            $jsonData = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (file_put_contents($filename, $jsonData)) {
                logMessage('INFO', 'Sauvegarde créée avec succès', [
                    'backup_id' => $backupId,
                    'filename' => $filename,
                    'size' => filesize($filename)
                ]);
                
                return [
                    'success' => true,
                    'backup_id' => $backupId,
                    'filename' => $filename,
                    'statistics' => $backupData['metadata']['statistics']
                ];
            } else {
                throw new Exception('Erreur lors de l\'écriture du fichier de sauvegarde');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erreur lors de la création de la sauvegarde', [
                'error' => $e->getMessage(),
                'backup_id' => $backupId
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Créer une sauvegarde d'une table spécifique
     */
    public function createTableBackup($tableName) {
        $backupId = 'backup_' . $tableName . '_' . date('Y-m-d_H-i-s');
        
        try {
            $data = $this->api->select($tableName);
            
            if (!$data['success']) {
                throw new Exception('Erreur lors de la récupération des données de la table ' . $tableName);
            }
            
            $backupData = [
                'metadata' => [
                    'backup_id' => $backupId,
                    'table_name' => $tableName,
                    'created_at' => getSenegalTime(),
                    'version' => APP_VERSION,
                    'record_count' => count($data['data'])
                ],
                'data' => $data['data']
            ];
            
            $filename = $this->backupDir . $backupId . '.json';
            $jsonData = json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            if (file_put_contents($filename, $jsonData)) {
                logMessage('INFO', 'Sauvegarde de table créée', [
                    'table' => $tableName,
                    'backup_id' => $backupId,
                    'filename' => $filename,
                    'record_count' => count($data['data'])
                ]);
                
                return [
                    'success' => true,
                    'backup_id' => $backupId,
                    'filename' => $filename,
                    'record_count' => count($data['data'])
                ];
            } else {
                throw new Exception('Erreur lors de l\'écriture du fichier de sauvegarde');
            }
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erreur lors de la sauvegarde de table', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Restaurer une sauvegarde
     */
    public function restoreBackup($filename) {
        try {
            if (!file_exists($filename)) {
                throw new Exception('Fichier de sauvegarde introuvable');
            }
            
            $backupData = json_decode(file_get_contents($filename), true);
            
            if (!$backupData) {
                throw new Exception('Fichier de sauvegarde corrompu');
            }
            
            $restoreId = 'restore_' . date('Y-m-d_H-i-s');
            $restoredTables = [];
            $errors = [];
            
            // Restaurer chaque table
            foreach ($backupData['data'] as $tableName => $tableData) {
                try {
                    // Vider la table existante (optionnel)
                    // $this->api->delete($tableName);
                    
                    // Insérer les données
                    foreach ($tableData as $record) {
                        $result = $this->api->insert($tableName, $record);
                        if (!$result['success']) {
                            $errors[] = "Erreur lors de la restauration de $tableName: " . $result['error'];
                        }
                    }
                    
                    $restoredTables[] = $tableName;
                    
                } catch (Exception $e) {
                    $errors[] = "Erreur lors de la restauration de $tableName: " . $e->getMessage();
                }
            }
            
            logMessage('INFO', 'Restauration terminée', [
                'restore_id' => $restoreId,
                'filename' => $filename,
                'restored_tables' => $restoredTables,
                'errors' => $errors
            ]);
            
            return [
                'success' => empty($errors),
                'restore_id' => $restoreId,
                'restored_tables' => $restoredTables,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            logMessage('ERROR', 'Erreur lors de la restauration', [
                'filename' => $filename,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Lister les sauvegardes disponibles
     */
    public function listBackups() {
        $backups = [];
        $files = glob($this->backupDir . '*.json');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $fileSize = filesize($file);
            $fileTime = filemtime($file);
            
            try {
                $backupData = json_decode(file_get_contents($file), true);
                $metadata = $backupData['metadata'] ?? [];
                
                $backups[] = [
                    'filename' => $filename,
                    'backup_id' => $metadata['backup_id'] ?? 'unknown',
                    'created_at' => $metadata['created_at'] ?? date('Y-m-d H:i:s', $fileTime),
                    'size' => $fileSize,
                    'size_formatted' => $this->formatFileSize($fileSize),
                    'tables' => $metadata['tables'] ?? [],
                    'statistics' => $metadata['statistics'] ?? []
                ];
                
            } catch (Exception $e) {
                $backups[] = [
                    'filename' => $filename,
                    'backup_id' => 'corrupted',
                    'created_at' => date('Y-m-d H:i:s', $fileTime),
                    'size' => $fileSize,
                    'size_formatted' => $this->formatFileSize($fileSize),
                    'error' => 'Fichier corrompu'
                ];
            }
        }
        
        // Trier par date de création (plus récent en premier)
        usort($backups, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        return $backups;
    }
    
    /**
     * Supprimer une sauvegarde
     */
    public function deleteBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Fichier de sauvegarde introuvable'
            ];
        }
        
        if (unlink($filepath)) {
            logMessage('INFO', 'Sauvegarde supprimée', ['filename' => $filename]);
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'error' => 'Erreur lors de la suppression'
            ];
        }
    }
    
    /**
     * Nettoyer les anciennes sauvegardes
     */
    public function cleanupOldBackups($daysToKeep = 30) {
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        $deletedCount = 0;
        $errors = [];
        
        $files = glob($this->backupDir . '*.json');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                if (unlink($file)) {
                    $deletedCount++;
                    logMessage('INFO', 'Ancienne sauvegarde supprimée', ['filename' => basename($file)]);
                } else {
                    $errors[] = 'Erreur lors de la suppression de ' . basename($file);
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'deleted_count' => $deletedCount,
            'errors' => $errors
        ];
    }
    
    /**
     * Vérifier l'intégrité d'une sauvegarde
     */
    public function verifyBackup($filename) {
        $filepath = $this->backupDir . $filename;
        
        if (!file_exists($filepath)) {
            return [
                'success' => false,
                'error' => 'Fichier introuvable'
            ];
        }
        
        try {
            $backupData = json_decode(file_get_contents($filepath), true);
            
            if (!$backupData) {
                return [
                    'success' => false,
                    'error' => 'Fichier JSON invalide'
                ];
            }
            
            $metadata = $backupData['metadata'] ?? [];
            $data = $backupData['data'] ?? [];
            
            $verification = [
                'filename' => $filename,
                'backup_id' => $metadata['backup_id'] ?? 'unknown',
                'created_at' => $metadata['created_at'] ?? 'unknown',
                'version' => $metadata['version'] ?? 'unknown',
                'tables' => $metadata['tables'] ?? [],
                'table_counts' => [],
                'file_size' => filesize($filepath),
                'file_size_formatted' => $this->formatFileSize(filesize($filepath)),
                'is_valid' => true
            ];
            
            // Vérifier chaque table
            foreach ($data as $tableName => $tableData) {
                $verification['table_counts'][$tableName] = count($tableData);
            }
            
            return [
                'success' => true,
                'verification' => $verification
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Formater la taille de fichier
     */
    private function formatFileSize($bytes) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

// Interface web pour le gestionnaire de sauvegarde
if (isset($_GET['action'])) {
    $backupManager = new BackupManager();
    
    switch ($_GET['action']) {
        case 'create_full':
            $result = $backupManager->createFullBackup();
            sendJsonResponse($result);
            break;
            
        case 'create_table':
            $tableName = $_GET['table'] ?? '';
            if (!$tableName) {
                sendErrorResponse('Nom de table requis');
            }
            $result = $backupManager->createTableBackup($tableName);
            sendJsonResponse($result);
            break;
            
        case 'list':
            $backups = $backupManager->listBackups();
            sendSuccessResponse($backups);
            break;
            
        case 'delete':
            $filename = $_GET['filename'] ?? '';
            if (!$filename) {
                sendErrorResponse('Nom de fichier requis');
            }
            $result = $backupManager->deleteBackup($filename);
            sendJsonResponse($result);
            break;
            
        case 'verify':
            $filename = $_GET['filename'] ?? '';
            if (!$filename) {
                sendErrorResponse('Nom de fichier requis');
            }
            $result = $backupManager->verifyBackup($filename);
            sendJsonResponse($result);
            break;
            
        case 'cleanup':
            $days = (int)($_GET['days'] ?? 30);
            $result = $backupManager->cleanupOldBackups($days);
            sendJsonResponse($result);
            break;
            
        default:
            sendErrorResponse('Action non reconnue');
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionnaire de Sauvegarde - <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">
            <i class="fas fa-database mr-2"></i>Gestionnaire de Sauvegarde
        </h1>
        
        <!-- Actions -->
        <div class="bg-white p-6 rounded-lg shadow mb-8">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button onclick="createFullBackup()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors">
                    <i class="fas fa-save mr-2"></i>Créer une sauvegarde complète
                </button>
                
                <button onclick="listBackups()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition-colors">
                    <i class="fas fa-list mr-2"></i>Lister les sauvegardes
                </button>
                
                <button onclick="cleanupBackups()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition-colors">
                    <i class="fas fa-broom mr-2"></i>Nettoyer les anciennes
                </button>
            </div>
        </div>
        
        <!-- Liste des sauvegardes -->
        <div id="backupsList" class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Sauvegardes disponibles</h2>
            <div id="backupsContent">
                <p class="text-gray-500">Cliquez sur "Lister les sauvegardes" pour voir les sauvegardes disponibles.</p>
            </div>
        </div>
    </div>
    
    <script>
        async function createFullBackup() {
            try {
                const response = await fetch('?action=create_full');
                const result = await response.json();
                
                if (result.success) {
                    alert(`Sauvegarde créée avec succès!\nID: ${result.backup_id}\nFichier: ${result.filename}`);
                    listBackups();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur lors de la création de la sauvegarde');
            }
        }
        
        async function listBackups() {
            try {
                const response = await fetch('?action=list');
                const result = await response.json();
                
                if (result.success) {
                    displayBackups(result.data);
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur lors de la récupération des sauvegardes');
            }
        }
        
        function displayBackups(backups) {
            const content = document.getElementById('backupsContent');
            
            if (backups.length === 0) {
                content.innerHTML = '<p class="text-gray-500">Aucune sauvegarde trouvée.</p>';
                return;
            }
            
            let html = '<div class="space-y-4">';
            
            backups.forEach(backup => {
                html += `
                    <div class="border rounded-lg p-4">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="font-semibold text-gray-900">${backup.backup_id}</h3>
                                <p class="text-sm text-gray-600">Créé le: ${backup.created_at}</p>
                                <p class="text-sm text-gray-600">Taille: ${backup.size_formatted}</p>
                                <p class="text-sm text-gray-600">Tables: ${backup.tables.join(', ')}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button onclick="verifyBackup('${backup.filename}')" class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                                <button onclick="deleteBackup('${backup.filename}')" class="text-red-600 hover:text-red-800">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            content.innerHTML = html;
        }
        
        async function deleteBackup(filename) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette sauvegarde ?')) {
                return;
            }
            
            try {
                const response = await fetch(`?action=delete&filename=${filename}`);
                const result = await response.json();
                
                if (result.success) {
                    alert('Sauvegarde supprimée avec succès');
                    listBackups();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur lors de la suppression');
            }
        }
        
        async function verifyBackup(filename) {
            try {
                const response = await fetch(`?action=verify&filename=${filename}`);
                const result = await response.json();
                
                if (result.success) {
                    const verification = result.verification;
                    alert(`Vérification de ${filename}:\n- Valide: ${verification.is_valid ? 'Oui' : 'Non'}\n- Taille: ${verification.file_size_formatted}\n- Tables: ${verification.tables.length}`);
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur lors de la vérification');
            }
        }
        
        async function cleanupBackups() {
            if (!confirm('Supprimer les sauvegardes de plus de 30 jours ?')) {
                return;
            }
            
            try {
                const response = await fetch('?action=cleanup&days=30');
                const result = await response.json();
                
                if (result.success) {
                    alert(`${result.deleted_count} sauvegardes supprimées`);
                    listBackups();
                } else {
                    alert('Erreur: ' + result.error);
                }
            } catch (error) {
                alert('Erreur lors du nettoyage');
            }
        }
    </script>
</body>
</html> 