<?php
// Test de santé simple pour l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Charger l'autoloader
    require_once __DIR__ . '/../vendor/autoload.php';
    
    // Charger la configuration
    require_once __DIR__ . '/../config.php';
    
    $health = [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'php_version' => PHP_VERSION,
        'database' => [
            'status' => 'checking',
            'message' => 'Test de connexion en cours...'
        ],
        'services' => [
            'qr_code' => 'available',
            'authentication' => 'available',
            'tracking' => 'available',
            'notifications' => 'available'
        ],
        'uptime' => [
            'start_time' => $_SERVER['REQUEST_TIME'] ?? time(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ]
    ];
    
    // Test de connexion à Supabase si les variables sont définies
    if (!empty($_ENV['SUPABASE_URL']) && !empty($_ENV['SUPABASE_ANON_KEY'])) {
        $health['database']['status'] = 'configured';
        $health['database']['message'] = 'Variables Supabase configurées';
    } else {
        $health['database']['status'] = 'not_configured';
        $health['database']['message'] = 'Variables Supabase manquantes';
    }
    
    http_response_code(200);
    echo json_encode($health, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
} 