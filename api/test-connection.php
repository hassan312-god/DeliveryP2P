<?php
/**
 * Endpoint de test de connexion pour Render
 * Vérification complète de l'environnement de production
 */

declare(strict_types=1);

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use DeliveryP2P\Utils\RenderConnectionTest;
use DeliveryP2P\Utils\Response;

try {
    $startTime = microtime(true);
    
    // Test complet de l'environnement Render
    $connectionTest = new RenderConnectionTest();
    $testResults = $connectionTest->testRenderEnvironment();
    
    $duration = microtime(true) - $startTime;
    
    // Informations de base
    $connectionInfo = [
        'timestamp' => date('c'),
        'environment' => $_ENV['APP_ENV'] ?? 'unknown',
        'version' => '2.0.0',
        'php_version' => PHP_VERSION,
        'server_time' => date('c'),
        'test_duration_ms' => round($duration * 1000, 2)
    ];
    
    // Vérification des variables d'environnement critiques
    $criticalVars = [
        'SUPABASE_URL' => $_ENV['SUPABASE_URL'] ?? null,
        'SUPABASE_ANON_KEY' => $_ENV['SUPABASE_ANON_KEY'] ?? null,
        'JWT_SECRET' => $_ENV['JWT_SECRET'] ?? null,
        'ENCRYPTION_KEY' => $_ENV['ENCRYPTION_KEY'] ?? null,
        'QR_CODE_SECRET' => $_ENV['QR_CODE_SECRET'] ?? null
    ];
    
    $missingVars = [];
    foreach ($criticalVars as $var => $value) {
        if (empty($value)) {
            $missingVars[] = $var;
        }
    }
    
    // Statut global
    $overallStatus = 'healthy';
    if (!empty($missingVars) || $testResults['overall_status'] !== 'healthy') {
        $overallStatus = 'unhealthy';
    }
    
    // Réponse détaillée
    $response = [
        'success' => $overallStatus === 'healthy',
        'status' => $overallStatus,
        'connection_info' => $connectionInfo,
        'environment_variables' => [
            'configured' => count($criticalVars) - count($missingVars),
            'missing' => count($missingVars),
            'missing_list' => $missingVars,
            'render_environment' => $_ENV['APP_ENV'] ?? 'unknown'
        ],
        'tests' => $testResults,
        'recommendations' => []
    ];
    
    // Ajout de recommandations
    if (!empty($missingVars)) {
        $response['recommendations'][] = 'Configurer les variables d\'environnement manquantes dans le dashboard Render: ' . implode(', ', $missingVars);
    }
    
    if ($testResults['supabase']['status'] === 'failed') {
        $response['recommendations'][] = 'Vérifier la configuration Supabase et les clés d\'API';
    }
    
    if ($testResults['services']['status'] === 'failed') {
        $response['recommendations'][] = 'Vérifier la configuration des services internes';
    }
    
    if (empty($response['recommendations'])) {
        $response['recommendations'][] = 'Tous les systèmes fonctionnent correctement';
    }
    
    // Détermination du code de statut HTTP
    $statusCode = $overallStatus === 'healthy' ? 200 : 503;
    
    // Headers de monitoring
    header('X-Connection-Status: ' . $overallStatus);
    header('X-Connection-Version: 2.0.0');
    header('X-Connection-Timestamp: ' . date('c'));
    
    http_response_code($statusCode);
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'error' => 'Connection test failed: ' . $e->getMessage(),
        'timestamp' => date('c'),
        'version' => '2.0.0',
        'recommendations' => [
            'Vérifier les logs de l\'application',
            'Contacter l\'administrateur système'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} 