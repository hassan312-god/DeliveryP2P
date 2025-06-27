<?php
/**
 * Script de test pour vérifier la connexion backend-frontend
 * Teste l'API Render et la communication avec Supabase
 */

// Désactiver l'affichage des erreurs pour éviter les conflits de headers
error_reporting(0);

echo "=== TEST DE CONNEXION BACKEND-FRONTEND ===\n\n";

// 1. Test de l'endpoint Render
echo "1. Test de l'endpoint Render...\n";
$renderUrl = "https://deliveryp2p-backend.onrender.com/supabase-api.php?action=checkAuth";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $renderUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // Désactiver pour le test
    CURLOPT_SSL_VERIFYHOST => false, // Désactiver pour le test
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL: $error\n";
} else {
    echo "✅ Code HTTP: $httpCode\n";
    echo "📄 Réponse: " . substr($response, 0, 200) . "...\n\n";
}

// 2. Test de Supabase directement
echo "2. Test de Supabase...\n";
$supabaseUrl = "https://syamapjohtlbjlyhlhsi.supabase.co/rest/v1/profiles?limit=1";
$supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $supabaseUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false, // Désactiver pour le test
    CURLOPT_SSL_VERIFYHOST => false, // Désactiver pour le test
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'apikey: ' . $supabaseKey
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur cURL Supabase: $error\n";
} else {
    echo "✅ Code HTTP Supabase: $httpCode\n";
    echo "📄 Réponse Supabase: " . substr($response, 0, 200) . "...\n\n";
}

// 3. Test de l'API locale (sans headers)
echo "3. Test de l'API locale...\n";

// Simuler les variables d'environnement pour le test local
putenv('SUPABASE_URL=https://syamapjohtlbjlyhlhsi.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M');
putenv('SUPABASE_SERVICE_KEY=your-service-key');
putenv('JWT_SECRET=your-jwt-secret');
putenv('PASSWORD_SALT=your-password-salt');

// Inclure les fichiers sans exécuter les headers
ob_start();
require_once 'php/config.php';
require_once 'php/supabase-api.php';
ob_end_clean();

try {
    $api = new SupabaseAPI();
    $result = $api->healthCheck();
    
    if ($result['success']) {
        echo "✅ API locale fonctionne\n";
        echo "📊 Statut: " . $result['api_status'] . "\n";
    } else {
        echo "❌ API locale en erreur\n";
    }
} catch (Exception $e) {
    echo "❌ Exception API locale: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DU TEST ===\n";
?> 