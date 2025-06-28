<?php
/**
 * Test simple de connexion à Supabase
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // Vérification des variables d'environnement
    $supabaseUrl = $_ENV['SUPABASE_URL'] ?? null;
    $supabaseAnonKey = $_ENV['SUPABASE_ANON_KEY'] ?? null;
    $supabaseServiceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? null;
    
    $config = [
        'supabase_url' => $supabaseUrl ? 'configured' : 'missing',
        'supabase_anon_key' => $supabaseAnonKey ? 'configured' : 'missing',
        'supabase_service_key' => $supabaseServiceKey ? 'configured' : 'missing'
    ];
    
    if (!$supabaseUrl || !$supabaseAnonKey) {
        throw new Exception('Variables d\'environnement Supabase manquantes');
    }
    
    // Test de connexion à l'API Supabase
    $testUrl = $supabaseUrl . '/rest/v1/';
    $headers = [
        'apikey: ' . $supabaseAnonKey,
        'Authorization: Bearer ' . $supabaseAnonKey,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Erreur cURL: ' . $error);
    }
    
    $result = [
        'success' => $httpCode === 200,
        'http_code' => $httpCode,
        'config' => $config,
        'supabase_url' => $supabaseUrl,
        'test_url' => $testUrl,
        'response' => $response,
        'timestamp' => date('c')
    ];
    
    if ($httpCode === 200) {
        http_response_code(200);
        echo json_encode($result, JSON_PRETTY_PRINT);
    } else {
        http_response_code(503);
        $result['error'] = 'Connexion à Supabase échouée';
        echo json_encode($result, JSON_PRETTY_PRINT);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'config' => $config ?? [],
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT);
} 