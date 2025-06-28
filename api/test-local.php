<?php
// Test simple pour vérifier que l'API fonctionne en local

// Charger l'autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Charger la configuration
require_once __DIR__ . '/../config.php';

// Test de base
echo "=== Test API Local ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Autoloader: " . (class_exists('DeliveryP2P\\Core\\Router') ? 'OK' : 'ERREUR') . "\n";
echo "Config: " . (defined('APP_ENV') ? 'OK' : 'ERREUR') . "\n";

// Test du Router
try {
    $router = new \DeliveryP2P\Core\Router();
    echo "Router: OK\n";
    
    // Test des routes
    $routes = $router->getRoutes();
    echo "Routes enregistrées: " . count($routes) . "\n";
    
    // Test de dispatch
    $response = $router->dispatch('GET', '/health');
    echo "Dispatch test: " . (isset($response['code']) ? 'OK' : 'ERREUR') . "\n";
    
} catch (Exception $e) {
    echo "Erreur Router: " . $e->getMessage() . "\n";
}

// Test des variables d'environnement
echo "\n=== Variables d'environnement ===\n";
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'non défini') . "\n";
echo "SUPABASE_URL: " . (!empty($_ENV['SUPABASE_URL']) ? 'défini' : 'non défini') . "\n";
echo "SUPABASE_ANON_KEY: " . (!empty($_ENV['SUPABASE_ANON_KEY']) ? 'défini' : 'non défini') . "\n";

echo "\n=== Test terminé ===\n"; 