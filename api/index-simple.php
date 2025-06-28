<?php
// Fichier index simple pour tester l'API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la configuration
require_once __DIR__ . '/../config.php';

// Route simple
$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Routes disponibles
$routes = [
    'GET' => [
        '/health' => 'health',
        '/health-test.php' => 'health',
        '/test-simple.php' => 'test_simple',
        '/test-connection' => 'test_connection'
    ],
    'POST' => [
        '/qr/generate' => 'qr_generate'
    ]
];

// Fonction de santé
function health() {
    return [
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '2.0.0',
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'php_version' => PHP_VERSION,
        'database' => [
            'status' => !empty($_ENV['SUPABASE_URL']) ? 'configured' : 'not_configured',
            'message' => !empty($_ENV['SUPABASE_URL']) ? 'Variables Supabase configurées' : 'Variables Supabase manquantes'
        ],
        'services' => [
            'qr_code' => 'available',
            'authentication' => 'available',
            'tracking' => 'available',
            'notifications' => 'available'
        ]
    ];
}

// Fonction de test simple
function test_simple() {
    return [
        'status' => 'success',
        'message' => 'API fonctionne parfaitement !',
        'timestamp' => date('c'),
        'php_version' => PHP_VERSION
    ];
}

// Fonction de test de connexion
function test_connection() {
    return [
        'success' => true,
        'data' => [
            'supabase_config' => [
                'url_configured' => !empty($_ENV['SUPABASE_URL']),
                'anon_key_configured' => !empty($_ENV['SUPABASE_ANON_KEY']),
                'service_key_configured' => !empty($_ENV['SUPABASE_SERVICE_ROLE_KEY'])
            ],
            'connection_test' => [
                'success' => !empty($_ENV['SUPABASE_URL']),
                'message' => !empty($_ENV['SUPABASE_URL']) ? 'Variables Supabase configurées' : 'Variables Supabase manquantes'
            ]
        ]
    ];
}

// Fonction de génération QR
function qr_generate() {
    $input = json_decode(file_get_contents('php://input'), true);
    
    return [
        'success' => true,
        'data' => [
            'qr_code' => 'generated',
            'data' => $input['data'] ?? 'test',
            'size' => $input['size'] ?? 200,
            'timestamp' => date('c')
        ]
    ];
}

// Router simple
$route_found = false;
foreach ($routes[$method] ?? [] as $route => $handler) {
    if (strpos($uri, $route) !== false) {
        $route_found = true;
        $response = $handler();
        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
        break;
    }
}

// Route non trouvée
if (!$route_found) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Route not found: ' . $method . ' ' . $uri,
        'available_routes' => array_keys($routes[$method] ?? []),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
} 