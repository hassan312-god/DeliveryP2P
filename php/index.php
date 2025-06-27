<?php
/**
 * Point d'entrée principal pour l'API LivraisonP2P
 * Redirige vers l'API appropriée selon l'action demandée
 */

// Démarrer le buffer de sortie pour éviter les problèmes de headers
ob_start();

// Inclure la configuration
require_once 'config.php';

// Vérifier si les headers CORS ont déjà été envoyés
if (!headers_sent()) {
    // Headers CORS (seulement si pas déjà envoyés)
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    header('Content-Type: application/json');
}

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_flush();
    exit;
}

// Vérifier si une action est demandée
$action = $_GET['action'] ?? '';

if ($action === 'checkAuth') {
    // Rediriger vers l'API d'authentification
    require_once 'supabase-api.php';
    ob_end_flush();
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
    ob_end_flush();
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

// Vider le buffer et terminer
ob_end_flush();
?> 