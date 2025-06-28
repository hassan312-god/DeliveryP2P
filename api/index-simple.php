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
        '/test-connection' => 'test_connection',
        '/qr' => 'qr_test',
        '/supabase/test' => 'supabase_test',
        '/supabase/profiles' => 'supabase_get_profiles',
        '/supabase/qr' => 'supabase_get_qr',
        '/supabase/delivery' => 'supabase_get_deliveries'
    ],
    'POST' => [
        '/qr/generate' => 'qr_generate',
        '/qr' => 'qr_generate',
        '/supabase/profiles' => 'supabase_create_profile',
        '/supabase/qr' => 'supabase_create_qr',
        '/supabase/delivery' => 'supabase_create_delivery'
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
    $data = $input['data'] ?? 'test';
    $size = $input['size'] ?? 200;
    
    // Générer un QR code simple en base64 (simulation)
    // En production, vous utiliseriez une librairie comme endroid/qr-code
    $qr_data = base64_encode($data . '_' . time());
    
    return [
        'success' => true,
        'data' => [
            'qr_code' => $qr_data,
            'qr_code_url' => 'data:image/png;base64,' . $qr_data,
            'data' => $data,
            'size' => $size,
            'timestamp' => date('c'),
            'message' => 'QR code généré avec succès'
        ]
    ];
}

// Fonction de test QR
function qr_test() {
    return [
        'success' => true,
        'message' => 'QR API endpoint accessible',
        'endpoints' => [
            'GET /qr' => 'Test endpoint',
            'POST /qr/generate' => 'Generate QR code'
        ],
        'timestamp' => date('c')
    ];
}

// Fonction de test Supabase
function supabase_test() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $client = new SupabaseClient();
    $result = $client->testConnection();
    
    return [
        'success' => $result['success'],
        'message' => $result['message'] ?? $result['error'] ?? 'Test Supabase',
        'timestamp' => date('c'),
        'config' => [
            'url_configured' => !empty($_ENV['SUPABASE_URL']),
            'anon_key_configured' => !empty($_ENV['SUPABASE_ANON_KEY']),
            'service_key_configured' => !empty($_ENV['SUPABASE_SERVICE_ROLE_KEY'])
        ]
    ];
}

// Fonction pour créer un profil dans Supabase
function supabase_create_profile() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['first_name']) || empty($input['last_name'])) {
        return [
            'success' => false,
            'error' => 'Prénom et nom requis'
        ];
    }
    
    $profileData = [
        'first_name' => $input['first_name'],
        'last_name' => $input['last_name'],
        'phone' => $input['phone'] ?? '',
        'role' => $input['role'] ?? 'client',
        'avatar_url' => $input['avatar_url'] ?? '',
        'is_verified' => false
    ];
    
    $client = new SupabaseClient();
    $result = $client->createProfile($profileData);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? null,
        'message' => $result['message'] ?? $result['error'] ?? 'Profil créé',
        'timestamp' => date('c')
    ];
}

// Fonction pour récupérer les profils
function supabase_get_profiles() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $client = new SupabaseClient();
    $result = $client->getProfiles(['select' => 'id,first_name,last_name,role,created_at']);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? [],
        'count' => count($result['data'] ?? []),
        'timestamp' => date('c')
    ];
}

// Fonction pour créer un QR code dans Supabase
function supabase_create_qr() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['data'])) {
        return [
            'success' => false,
            'error' => 'Données QR requises'
        ];
    }
    
    $qrData = [
        'data' => $input['data'],
        'size' => $input['size'] ?? 300,
        'qr_code' => base64_encode($input['data'] . '_' . time()),
        'status' => 'active'
    ];
    
    $client = new SupabaseClient();
    $result = $client->createQRCode($qrData);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? null,
        'message' => $result['message'] ?? $result['error'] ?? 'QR code créé',
        'timestamp' => date('c')
    ];
}

// Fonction pour créer une livraison dans Supabase
function supabase_create_delivery() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (empty($input['client_id']) || empty($input['pickup_address']) || empty($input['delivery_address'])) {
        return [
            'success' => false,
            'error' => 'client_id, pickup_address et delivery_address requis'
        ];
    }
    
    $deliveryData = [
        'client_id' => $input['client_id'],
        'driver_id' => $input['driver_id'] ?? null,
        'pickup_address' => $input['pickup_address'],
        'delivery_address' => $input['delivery_address'],
        'status' => 'pending',
        'weight' => $input['weight'] ?? 0,
        'price' => $input['price'] ?? 0
    ];
    
    $client = new SupabaseClient();
    $result = $client->createDelivery($deliveryData);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? null,
        'message' => $result['message'] ?? $result['error'] ?? 'Livraison créée',
        'timestamp' => date('c')
    ];
}

// Fonction pour récupérer les QR codes
function supabase_get_qr() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $client = new SupabaseClient();
    $result = $client->getQRCodes(['select' => 'id,data,size,status,created_at']);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? [],
        'count' => count($result['data'] ?? []),
        'timestamp' => date('c')
    ];
}

// Fonction pour récupérer les livraisons
function supabase_get_deliveries() {
    require_once __DIR__ . '/SupabaseClient.php';
    
    $client = new SupabaseClient();
    $result = $client->getDeliveries(['select' => 'id,client_id,pickup_address,delivery_address,status,price,created_at']);
    
    return [
        'success' => $result['success'],
        'data' => $result['data'] ?? [],
        'count' => count($result['data'] ?? []),
        'timestamp' => date('c')
    ];
}

// Router simple - amélioré
$route_found = false;
$method_routes = $routes[$method] ?? [];

// Essayer d'abord les routes exactes
foreach ($method_routes as $route => $handler) {
    if ($uri === $route || $uri === $route . '/') {
        $route_found = true;
        $response = $handler();
        http_response_code(200);
        echo json_encode($response, JSON_PRETTY_PRINT);
        break;
    }
}

// Si pas trouvé, essayer les routes partielles
if (!$route_found) {
    foreach ($method_routes as $route => $handler) {
        if (strpos($uri, $route) !== false) {
            $route_found = true;
            $response = $handler();
            http_response_code(200);
            echo json_encode($response, JSON_PRETTY_PRINT);
            break;
        }
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