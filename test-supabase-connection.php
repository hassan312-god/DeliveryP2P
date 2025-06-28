<?php
header('Content-Type: application/json');

// Charger la configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/api/SupabaseClient.php';

echo "🔍 Test de connexion Supabase...\n\n";

// Vérifier les variables d'environnement
echo "📋 Variables d'environnement :\n";
echo "- SUPABASE_URL: " . (isset($_ENV['SUPABASE_URL']) ? '✅ Configurée' : '❌ Manquante') . "\n";
echo "- SUPABASE_ANON_KEY: " . (isset($_ENV['SUPABASE_ANON_KEY']) ? '✅ Configurée' : '❌ Manquante') . "\n";
echo "- SUPABASE_SERVICE_ROLE_KEY: " . (isset($_ENV['SUPABASE_SERVICE_ROLE_KEY']) ? '✅ Configurée' : '❌ Manquante') . "\n\n";

// Tester la connexion
try {
    $client = new SupabaseClient();
    $result = $client->testConnection();
    
    echo "🔗 Test de connexion :\n";
    if ($result['success']) {
        echo "✅ " . $result['message'] . "\n\n";
        
        // Tester la récupération des profils
        echo "👥 Test de récupération des profils :\n";
        $profiles = $client->getProfiles(['select' => 'id,first_name,last_name,role,created_at']);
        
        if ($profiles['success']) {
            echo "✅ " . count($profiles['data']) . " profils trouvés\n";
            if (!empty($profiles['data'])) {
                echo "📋 Exemple de profil :\n";
                print_r($profiles['data'][0]);
            }
        } else {
            echo "❌ Erreur : " . $profiles['error'] . "\n";
        }
        
    } else {
        echo "❌ " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception : " . $e->getMessage() . "\n";
}

echo "\n🎯 Configuration recommandée pour Render :\n";
echo "SUPABASE_URL=https://syamapjohtlbjlyhlhsi.supabase.co\n";
echo "SUPABASE_ANON_KEY=<ta-clé-anon>\n";
echo "SUPABASE_SERVICE_ROLE_KEY=<ta-clé-service-role>\n";
echo "APP_ENV=production\n"; 