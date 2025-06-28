<?php
require_once 'vendor/autoload.php';
require_once 'app/services/SupabaseService.php';

// Charger les variables d'environnement
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "=== Test de connexion Supabase ===\n";

try {
    $supabase = new SupabaseService();
    echo "✅ Service Supabase initialisé\n";
    
    // Test de connexion simple
    $result = $supabase->testConnection();
    echo "✅ Connexion à Supabase réussie\n";
    
    // Test de récupération des utilisateurs
    $users = $supabase->getAllUsers();
    echo "✅ Récupération des utilisateurs : " . count($users) . " utilisateur(s)\n";
    
    echo "\n🎉 Tous les tests sont passés !\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Vérifiez vos clés Supabase dans le fichier .env\n";
}
?> 