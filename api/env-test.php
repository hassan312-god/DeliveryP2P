<?php
/**
 * Test des variables d'environnement
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// Récupération de toutes les variables d'environnement
$env_vars = [
    'SUPABASE_URL' => $_ENV['SUPABASE_URL'] ?? 'NOT_SET',
    'SUPABASE_ANON_KEY' => $_ENV['SUPABASE_ANON_KEY'] ? 'SET' : 'NOT_SET',
    'SUPABASE_SERVICE_KEY' => $_ENV['SUPABASE_SERVICE_KEY'] ? 'SET' : 'NOT_SET',
    'JWT_SECRET' => $_ENV['JWT_SECRET'] ? 'SET' : 'NOT_SET',
    'APP_ENV' => $_ENV['APP_ENV'] ?? 'NOT_SET',
    'PHP_VERSION' => PHP_VERSION
];

// Test de connexion si les variables sont configurées
$connection_test = null;
if ($_ENV['SUPABASE_URL'] && $_ENV['SUPABASE_ANON_KEY']) {
    try {
        $testUrl = $_ENV['SUPABASE_URL'] . '/rest/v1/';
        $headers = [
            'apikey: ' . $_ENV['SUPABASE_ANON_KEY'],
            'Authorization: Bearer ' . $_ENV['SUPABASE_ANON_KEY']
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $connection_test = [
            'success' => $httpCode === 200,
            'http_code' => $httpCode,
            'error' => $error ?: null,
            'test_url' => $testUrl
        ];
    } catch (Exception $e) {
        $connection_test = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

$result = [
    'timestamp' => date('c'),
    'environment_variables' => $env_vars,
    'connection_test' => $connection_test,
    'server_info' => [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        'request_method' => $_SERVER['REQUEST_METHOD'],
        'request_uri' => $_SERVER['REQUEST_URI']
    ]
];

echo json_encode($result, JSON_PRETTY_PRINT); 