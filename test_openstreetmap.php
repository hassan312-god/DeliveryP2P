<?php
// Test OpenStreetMap + Nominatim (Alternative gratuite à Google Maps)

class NominatimService {
    private $baseUrl = 'https://nominatim.openstreetmap.org';
    
    // Géocodage (adresse vers coordonnées)
    public function geocode($address) {
        $url = $this->baseUrl . '/search?format=json&q=' . urlencode($address) . '&limit=1';
        
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: LivraisonP2P/1.0 (contact@livraisonp2p.com)',
                        'Accept: application/json'
                    ]
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            $data = json_decode($response, true);
            
            if (!empty($data)) {
                return [
                    'lat' => floatval($data[0]['lat']),
                    'lng' => floatval($data[0]['lon']),
                    'display_name' => $data[0]['display_name']
                ];
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    // Géocodage inverse (coordonnées vers adresse)
    public function reverseGeocode($lat, $lng) {
        $url = $this->baseUrl . "/reverse?format=json&lat={$lat}&lon={$lng}&zoom=18&addressdetails=1";
        
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: LivraisonP2P/1.0 (contact@livraisonp2p.com)',
                        'Accept: application/json'
                    ]
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            $data = json_decode($response, true);
            
            return [
                'address' => $data['display_name'],
                'street' => $data['address']['road'] ?? '',
                'city' => $data['address']['city'] ?? $data['address']['town'] ?? '',
                'postcode' => $data['address']['postcode'] ?? ''
            ];
        } catch (Exception $e) {
            return null;
        }
    }
}

echo "=== Test OpenStreetMap + Nominatim (GRATUIT) ===\n\n";

$nominatim = new NominatimService();

// Test 1: Géocodage
echo "1. Test de géocodage pour 'Paris, France'...\n";
$coords = $nominatim->geocode('Paris, France');

if ($coords) {
    echo "✅ Géocodage réussi !\n";
    echo "   Latitude : " . $coords['lat'] . "\n";
    echo "   Longitude : " . $coords['lng'] . "\n";
    echo "   Adresse : " . $coords['display_name'] . "\n\n";
    
    // Test 2: Géocodage inverse
    echo "2. Test de géocodage inverse...\n";
    $address = $nominatim->reverseGeocode($coords['lat'], $coords['lng']);
    
    if ($address) {
        echo "✅ Géocodage inverse réussi !\n";
        echo "   Adresse : " . $address['address'] . "\n";
        echo "   Rue : " . $address['street'] . "\n";
        echo "   Ville : " . $address['city'] . "\n";
        echo "   Code postal : " . $address['postcode'] . "\n\n";
    }
} else {
    echo "❌ Erreur de géocodage\n\n";
}

// Test 3: Autres adresses
$testAddresses = ['Lyon, France', 'Marseille, France', 'Toulouse, France'];

echo "3. Test de plusieurs adresses...\n";
foreach ($testAddresses as $address) {
    $result = $nominatim->geocode($address);
    if ($result) {
        echo "✅ {$address} : " . $result['lat'] . ", " . $result['lng'] . "\n";
    } else {
        echo "❌ {$address} : Erreur\n";
    }
}

echo "\n🎉 Test terminé !\n";
echo "💡 OpenStreetMap + Nominatim est TOTALEMENT GRATUIT !\n";
echo "📊 Pas de limite de requêtes, pas de clé API nécessaire.\n";
echo "⚠️  Important : Respectez la limite de 1 requête/seconde\n";
?> 