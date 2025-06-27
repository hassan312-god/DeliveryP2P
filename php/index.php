<?php
/**
 * Point d'entrée principal pour l'API LivraisonP2P
 * Redirige vers l'API appropriée selon l'action demandée
 */

// Inclure la configuration
require_once 'config.php';

// Headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Vérifier si une action est demandée
$action = $_GET['action'] ?? '';

if ($action === 'checkAuth') {
    // Rediriger vers l'API d'authentification
    require_once 'supabase-api.php';
    exit;
}

if ($action === 'health') {
    // Endpoint de santé de l'API
    echo json_encode([
        'success' => true,
        'message' => 'LivraisonP2P API is running',
        'version' => '1.2.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'environment' => APP_ENV
    ]);
    exit;
}

// Page d'accueil par défaut
echo json_encode([
    'success' => true,
    'message' => 'LivraisonP2P Backend API',
    'version' => '1.2.0',
    'endpoints' => [
        'health' => '/?action=health',
        'auth' => '/supabase-api.php?action=checkAuth',
        'admin' => '/admin-dashboard.php',
        'qr' => '/qr-code-generator.php',
        'email' => '/email-service.php',
        'backup' => '/backup-manager.php'
    ],
    'documentation' => 'Voir README.md pour plus d\'informations'
]);
?> 