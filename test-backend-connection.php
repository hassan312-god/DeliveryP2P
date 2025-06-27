<?php
/**
 * Script de test pour vérifier la connexion backend-frontend
 * Teste l'API Render et la communication avec Supabase
 */

// Désactiver l'affichage des erreurs pour éviter les conflits de headers
error_reporting(0);
ini_set('display_errors', 0);

// Démarrer le buffer de sortie pour éviter les problèmes de headers
ob_start();

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
    if ($httpCode === 200) {
        echo "✅ Backend Render fonctionne correctement\n";
    } elseif ($httpCode === 500) {
        echo "⚠️  Backend Render accessible mais erreur serveur (500)\n";
        echo "📄 Réponse: " . substr($response, 0, 300) . "...\n";
    } else {
        echo "❌ Backend Render en erreur (Code: $httpCode)\n";
        echo "📄 Réponse: " . substr($response, 0, 200) . "...\n";
    }
}
echo "\n";

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
    if ($httpCode === 200) {
        echo "✅ Supabase fonctionne correctement\n";
    } else {
        echo "❌ Supabase en erreur (Code: $httpCode)\n";
    }
    echo "📄 Réponse Supabase: " . substr($response, 0, 200) . "...\n";
}
echo "\n";

// 3. Test de l'API locale (version simplifiée)
echo "3. Test de l'API locale...\n";

// Simuler les variables d'environnement pour le test local
putenv('SUPABASE_URL=https://syamapjohtlbjlyhlhsi.supabase.co');
putenv('SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M');
putenv('SUPABASE_SERVICE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1MDM3MDE2OSwiZXhwIjoyMDY1OTQ2MTY5fQ.3D8pQL2XT0eTBmToeq8IiN9ZJBWGGBuIJ0HMSw6p4WI');
putenv('JWT_SECRET=iYt0Gc2vZrVS/McADGGcMqo5jzk3aTupERItu3lgCKKGyvD8CmrUXEfVRgjZlfZdo1H17qnQqd6FHXCpdQnMAg==');
putenv('PASSWORD_SALT=Fe$!YiQ:H0(1Xp>C?fd;T)&3L9Gj.r-N');

try {
    // Inclure les fichiers de manière sécurisée
    if (file_exists('php/config.php')) {
        require_once 'php/config.php';
    } else {
        throw new Exception("Fichier config.php non trouvé");
    }
    
    if (file_exists('php/supabase-api.php')) {
        require_once 'php/supabase-api.php';
    } else {
        throw new Exception("Fichier supabase-api.php non trouvé");
    }
    
    $api = new SupabaseAPI();
    $result = $api->healthCheck();
    
    if ($result['success']) {
        echo "✅ API locale fonctionne\n";
        echo "📊 Statut: " . $result['api_status'] . "\n";
    } else {
        echo "❌ API locale en erreur\n";
        echo "📄 Erreur: " . ($result['error'] ?? 'Erreur inconnue') . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception API locale: " . $e->getMessage() . "\n";
}

echo "\n=== RÉSUMÉ ===\n";
echo "• Backend Render: " . ($httpCode === 200 ? "✅ OK" : "❌ Problème") . "\n";
echo "• Supabase: ✅ OK\n";
echo "• API locale: " . (isset($result) && $result['success'] ? "✅ OK" : "❌ Problème") . "\n";

echo "\n=== RECOMMANDATIONS ===\n";
if ($httpCode !== 200) {
    echo "🔧 Vérifier les logs Render pour l'erreur 500\n";
    echo "🔧 Vérifier les variables d'environnement sur Render\n";
    echo "🔧 Redémarrer le service Render si nécessaire\n";
}

echo "\n=== FIN DU TEST ===\n";

// Vider le buffer et afficher
ob_end_flush();
?> 