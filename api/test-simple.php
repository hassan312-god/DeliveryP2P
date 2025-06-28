<?php
/**
 * Test simple pour vérifier le routage API
 */

declare(strict_types=1);

// Headers de sécurité
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// Gestion des requêtes OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Test simple de l'API
$response = [
    'success' => true,
    'message' => 'API is working correctly!',
    'endpoint' => 'test-simple',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('c'),
    'version' => '2.0.0',
    'environment' => $_ENV['APP_ENV'] ?? 'production'
];

http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); 