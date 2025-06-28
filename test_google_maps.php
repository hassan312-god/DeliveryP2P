<?php
// Test Google Maps API (avec compte Google Developer)

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

$apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';

echo "=== Test Google Maps API (Compte Developer) ===\n";
echo "Clé API : " . substr($apiKey, 0, 10) . "...\n\n";

if (empty($apiKey) || $apiKey === 'AIzaSyB...ta_clé_google_maps_ici') {
    echo "❌ Clé API Google Maps non configurée\n";
    echo "Suivez les étapes pour obtenir une clé dans Google Cloud Console\n";
    echo "1. Va sur https://console.cloud.google.com/\n";
    echo "2. Sélectionne ton projet existant\n";
    echo "3. Active Maps JavaScript API + Geocoding API\n";
    echo "4. Crée une clé API dans Identifiants\n";
    exit(1);
}

// Test 1: Géocodage (adresse vers coordonnées)
echo "1. Test de géocodage pour 'Paris, France'...\n";
$address = urlencode('Paris, France');
$url = "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key={$apiKey}";

$response = file_get_contents($url);
$data = json_decode($response, true);

if ($data['status'] === 'OK') {
    $location = $data['results'][0]['geometry']['location'];
    echo "✅ Géocodage réussi !\n";
    echo "   Latitude : " . $location['lat'] . "\n";
    echo "   Longitude : " . $location['lng'] . "\n";
    echo "   Adresse formatée : " . $data['results'][0]['formatted_address'] . "\n\n";
    
    // Test 2: Géocodage inverse (coordonnées vers adresse)
    echo "2. Test de géocodage inverse...\n";
    $lat = $location['lat'];
    $lng = $location['lng'];
    $reverseUrl = "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$apiKey}";
    
    $reverseResponse = file_get_contents($reverseUrl);
    $reverseData = json_decode($reverseResponse, true);
    
    if ($reverseData['status'] === 'OK') {
        echo "✅ Géocodage inverse réussi !\n";
        echo "   Adresse : " . $reverseData['results'][0]['formatted_address'] . "\n\n";
    }
    
    // Test 3: Autres adresses
    echo "3. Test de plusieurs adresses...\n";
    $testAddresses = ['Lyon, France', 'Marseille, France', 'Toulouse, France'];
    
    foreach ($testAddresses as $testAddress) {
        $testUrl = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($testAddress) . "&key={$apiKey}";
        $testResponse = file_get_contents($testUrl);
        $testData = json_decode($testResponse, true);
        
        if ($testData['status'] === 'OK') {
            $testLocation = $testData['results'][0]['geometry']['location'];
            echo "✅ {$testAddress} : " . $testLocation['lat'] . ", " . $testLocation['lng'] . "\n";
        } else {
            echo "❌ {$testAddress} : " . $testData['status'] . "\n";
        }
    }
    
} else {
    echo "❌ Erreur de géocodage : " . $data['status'] . "\n";
    echo "Message : " . ($data['error_message'] ?? 'Aucun message d\'erreur') . "\n";
    
    if ($data['status'] === 'REQUEST_DENIED') {
        echo "\n💡 Solutions possibles :\n";
        echo "1. Vérifiez que la clé API est correcte\n";
        echo "2. Activez Maps JavaScript API et Geocoding API\n";
        echo "3. Configurez les restrictions de domaine\n";
        echo "4. Vérifiez la facturation (crédit $300/mois)\n";
    }
}

echo "\n🎉 Test terminé !\n";
echo "💡 Avec ton compte Google Developer, tu as $300 de crédit gratuit/mois\n";
echo "📊 Surveille l'utilisation dans Google Cloud Console\n";
?> 