<?php

class GoogleMapsService {
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api';
    
    public function __construct() {
        $this->apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
        
        if (empty($this->apiKey)) {
            throw new Exception('Clé Google Maps API non configurée dans .env');
        }
    }
    
    /**
     * Géocoder une adresse (adresse vers coordonnées)
     */
    public function geocode($address) {
        $url = $this->baseUrl . '/geocode/json?address=' . urlencode($address) . '&key=' . $this->apiKey;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = $data['results'][0];
            return [
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng'],
                'formatted_address' => $result['formatted_address'],
                'place_id' => $result['place_id'],
                'types' => $result['types'] ?? []
            ];
        }
        
        throw new Exception('Erreur de géocodage: ' . ($data['error_message'] ?? $data['status']));
    }
    
    /**
     * Géocodage inverse (coordonnées vers adresse)
     */
    public function reverseGeocode($lat, $lng) {
        $url = $this->baseUrl . "/geocode/json?latlng={$lat},{$lng}&key=" . $this->apiKey;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK' && !empty($data['results'])) {
            $result = $data['results'][0];
            $address = $result['address_components'];
            
            return [
                'formatted_address' => $result['formatted_address'],
                'street_number' => $this->getAddressComponent($address, 'street_number'),
                'route' => $this->getAddressComponent($address, 'route'),
                'locality' => $this->getAddressComponent($address, 'locality'),
                'postal_code' => $this->getAddressComponent($address, 'postal_code'),
                'country' => $this->getAddressComponent($address, 'country'),
                'place_id' => $result['place_id']
            ];
        }
        
        throw new Exception('Erreur de géocodage inverse: ' . ($data['error_message'] ?? $data['status']));
    }
    
    /**
     * Calculer la distance entre deux points
     */
    public function calculateDistance($lat1, $lng1, $lat2, $lng2, $mode = 'driving') {
        $url = $this->baseUrl . "/distancematrix/json?origins={$lat1},{$lng1}&destinations={$lat2},{$lng2}&mode={$mode}&key=" . $this->apiKey;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK' && !empty($data['rows'][0]['elements'][0])) {
            $element = $data['rows'][0]['elements'][0];
            
            if ($element['status'] === 'OK') {
                return [
                    'distance' => $element['distance']['text'],
                    'distance_meters' => $element['distance']['value'],
                    'duration' => $element['duration']['text'],
                    'duration_seconds' => $element['duration']['value']
                ];
            }
        }
        
        throw new Exception('Erreur de calcul de distance: ' . ($data['error_message'] ?? $data['status']));
    }
    
    /**
     * Obtenir l'itinéraire entre deux points
     */
    public function getDirections($origin, $destination, $mode = 'driving') {
        $url = $this->baseUrl . "/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&mode={$mode}&key=" . $this->apiKey;
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK' && !empty($data['routes'])) {
            $route = $data['routes'][0];
            return [
                'distance' => $route['legs'][0]['distance']['text'],
                'duration' => $route['legs'][0]['duration']['text'],
                'steps' => $route['legs'][0]['steps'],
                'polyline' => $route['overview_polyline']['points']
            ];
        }
        
        throw new Exception('Erreur de calcul d\'itinéraire: ' . ($data['error_message'] ?? $data['status']));
    }
    
    /**
     * Rechercher des lieux (Places API)
     */
    public function searchPlaces($query, $location = null, $radius = 5000) {
        $url = "https://maps.googleapis.com/maps/api/place/textsearch/json?query=" . urlencode($query) . "&key=" . $this->apiKey;
        
        if ($location) {
            $url .= "&location={$location['lat']},{$location['lng']}&radius={$radius}";
        }
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);
        
        if ($data['status'] === 'OK') {
            return array_map(function($place) {
                return [
                    'place_id' => $place['place_id'],
                    'name' => $place['name'],
                    'formatted_address' => $place['formatted_address'],
                    'lat' => $place['geometry']['location']['lat'],
                    'lng' => $place['geometry']['location']['lng'],
                    'rating' => $place['rating'] ?? null,
                    'types' => $place['types'] ?? []
                ];
            }, $data['results']);
        }
        
        throw new Exception('Erreur de recherche de lieux: ' . ($data['error_message'] ?? $data['status']));
    }
    
    /**
     * Valider une adresse
     */
    public function validateAddress($address) {
        try {
            $result = $this->geocode($address);
            return [
                'valid' => true,
                'data' => $result
            ];
        } catch (Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtenir les coordonnées d'une adresse
     */
    public function getCoordinates($address) {
        $result = $this->geocode($address);
        return [
            'lat' => $result['lat'],
            'lng' => $result['lng']
        ];
    }
    
    /**
     * Helper pour extraire les composants d'adresse
     */
    private function getAddressComponent($addressComponents, $type) {
        foreach ($addressComponents as $component) {
            if (in_array($type, $component['types'])) {
                return $component['long_name'];
            }
        }
        return '';
    }
    
    /**
     * Générer le code JavaScript pour Google Maps
     */
    public function generateMapScript($elementId, $center = [48.8566, 2.3522], $zoom = 13) {
        return "
        <script>
        function initMap() {
            const map = new google.maps.Map(document.getElementById('{$elementId}'), {
                center: {lat: {$center[0]}, lng: {$center[1]}},
                zoom: {$zoom}
            });
            return map;
        }
        </script>
        <script async defer src='https://maps.googleapis.com/maps/api/js?key={$this->apiKey}&callback=initMap'></script>
        ";
    }
} 