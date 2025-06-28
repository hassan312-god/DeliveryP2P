<?php
/**
 * Endpoint de santé avancé pour Render
 * Tests complets de l'environnement de production
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
    $healthReport = $connectionTest->generateHealthReport();
    
    $duration = microtime(true) - $startTime;
    
    // Ajout des métriques de performance
    $healthReport['performance'] = [
        'health_check_duration_ms' => round($duration * 1000, 2),
        'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
        'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'php_version' => PHP_VERSION,
        'server_time' => date('c')
    ];
    
    // Détermination du code de statut HTTP
    $statusCode = $healthReport['overall_status'] === 'healthy' ? 200 : 503;
    
    // Headers de monitoring
    header('X-Health-Status: ' . $healthReport['overall_status']);
    header('X-Health-Version: 2.0.0');
    header('X-Health-Timestamp: ' . date('c'));
    
    http_response_code($statusCode);
    
    echo json_encode([
        'success' => $healthReport['overall_status'] === 'healthy',
        'status' => $healthReport['overall_status'],
        'timestamp' => $healthReport['timestamp'],
        'version' => $healthReport['version'],
        'environment' => $healthReport['environment'],
        'tests' => $healthReport['tests'],
        'performance' => $healthReport['performance'],
        'recommendations' => $healthReport['recommendations']
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'error' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('c'),
        'version' => '2.0.0'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} 