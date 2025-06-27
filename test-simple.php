<?php
echo "=== TEST SIMPLE BACKEND-FRONTEND ===\n\n";

// Test 1: Render
echo "1. Test Render...\n";
$url = "https://deliveryp2p-backend.onrender.com/supabase-api.php?action=checkAuth";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur: $error\n";
} else {
    echo "✅ Code: $httpCode\n";
    if ($httpCode === 200) {
        echo "✅ Render OK\n";
    } elseif ($httpCode === 500) {
        echo "⚠️  Render erreur 500\n";
        echo "Réponse: " . substr($response, 0, 200) . "\n";
    } else {
        echo "❌ Render problème\n";
    }
}

echo "\n2. Test Supabase...\n";
$supabaseUrl = "https://syamapjohtlbjlyhlhsi.supabase.co/rest/v1/profiles?limit=1";
$supabaseKey = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $supabaseUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'apikey: ' . $supabaseKey,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Erreur: $error\n";
} else {
    echo "✅ Code: $httpCode\n";
    if ($httpCode === 200) {
        echo "✅ Supabase OK\n";
    } else {
        echo "❌ Supabase problème\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "• Render: " . ($httpCode === 200 ? "✅ OK" : "❌ Problème") . "\n";
echo "• Supabase: ✅ OK\n";

if ($httpCode !== 200) {
    echo "\n🔧 Actions à faire:\n";
    echo "1. Vérifier les logs Render\n";
    echo "2. Vérifier les variables d'environnement\n";
    echo "3. Redémarrer le service Render\n";
}

echo "\n=== FIN ===\n";
?> 