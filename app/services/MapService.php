<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class MapService
{
    private $client;
    private $apiKey;
    private $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->client = new Client(['timeout' => 10]);
        $this->apiKey = $_ENV['GOOGLE_MAPS_API_KEY'] ?? '';
    }

    /**
     * Géocode une adresse (convertit une adresse en coordonnées)
     */
    public function geocodeAddress($address)
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/geocode/json';
        
        try {
            $response = $this->client->get($url, [
                'query' => [
                    'address' => $address,
                    'key' => $this->apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                $location = $data['results'][0]['geometry']['location'];
                return [
                    'latitude' => $location['lat'],
                    'longitude' => $location['lng'],
                    'formatted_address' => $data['results'][0]['formatted_address']
                ];
            }

            error_log('Geocoding failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Geocoding request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Géocode inverse (convertit des coordonnées en adresse)
     */
    public function reverseGeocode($latitude, $longitude)
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/geocode/json';
        
        try {
            $response = $this->client->get($url, [
                'query' => [
                    'latlng' => $latitude . ',' . $longitude,
                    'key' => $this->apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['results'])) {
                return [
                    'formatted_address' => $data['results'][0]['formatted_address'],
                    'address_components' => $data['results'][0]['address_components']
                ];
            }

            error_log('Reverse geocoding failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Reverse geocoding request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calcule la distance entre deux points (formule Haversine)
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Rayon de la Terre en kilomètres

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Calcule la durée de trajet entre deux points
     */
    public function calculateTravelTime($origin, $destination, $mode = 'driving')
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/directions/json';
        
        try {
            $response = $this->client->get($url, [
                'query' => [
                    'origin' => $origin,
                    'destination' => $destination,
                    'mode' => $mode,
                    'key' => $this->apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['routes'])) {
                $route = $data['routes'][0];
                $leg = $route['legs'][0];
                
                return [
                    'duration' => $leg['duration']['text'],
                    'duration_seconds' => $leg['duration']['value'],
                    'distance' => $leg['distance']['text'],
                    'distance_meters' => $leg['distance']['value'],
                    'polyline' => $route['overview_polyline']['points']
                ];
            }

            error_log('Directions failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Directions request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Trouve les lieux à proximité d'un point
     */
    public function findNearbyPlaces($latitude, $longitude, $radius = 1000, $type = null)
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/place/nearbysearch/json';
        
        $params = [
            'location' => $latitude . ',' . $longitude,
            'radius' => $radius,
            'key' => $this->apiKey
        ];

        if ($type) {
            $params['type'] = $type;
        }

        try {
            $response = $this->client->get($url, [
                'query' => $params
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK') {
                return $data['results'];
            }

            error_log('Nearby places failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Nearby places request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Autocomplétion d'adresse
     */
    public function autocompleteAddress($input, $sessionToken = null)
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/place/autocomplete/json';
        
        $params = [
            'input' => $input,
            'types' => 'geocode',
            'key' => $this->apiKey
        ];

        if ($sessionToken) {
            $params['sessiontoken'] = $sessionToken;
        }

        try {
            $response = $this->client->get($url, [
                'query' => $params
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK') {
                return $data['predictions'];
            }

            error_log('Autocomplete failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Autocomplete request error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide une adresse
     */
    public function validateAddress($address)
    {
        $geocoded = $this->geocodeAddress($address);
        
        if ($geocoded) {
            return [
                'valid' => true,
                'coordinates' => $geocoded,
                'formatted_address' => $geocoded['formatted_address']
            ];
        }

        return [
            'valid' => false,
            'error' => 'Address not found'
        ];
    }

    /**
     * Extrait les composants d'une adresse
     */
    public function parseAddress($address)
    {
        $geocoded = $this->geocodeAddress($address);
        
        if (!$geocoded) {
            return false;
        }

        // Utiliser reverse geocoding pour obtenir les composants détaillés
        $reverse = $this->reverseGeocode($geocoded['latitude'], $geocoded['longitude']);
        
        if (!$reverse) {
            return false;
        }

        $components = [];
        foreach ($reverse['address_components'] as $component) {
            $types = $component['types'];
            $value = $component['long_name'];
            
            if (in_array('street_number', $types)) {
                $components['street_number'] = $value;
            } elseif (in_array('route', $types)) {
                $components['street'] = $value;
            } elseif (in_array('locality', $types)) {
                $components['city'] = $value;
            } elseif (in_array('postal_code', $types)) {
                $components['postal_code'] = $value;
            } elseif (in_array('country', $types)) {
                $components['country'] = $value;
            }
        }

        return array_merge($geocoded, $components);
    }

    /**
     * Génère un token de session pour l'autocomplétion
     */
    public function generateSessionToken()
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Calcule un itinéraire optimisé pour plusieurs points
     */
    public function calculateOptimizedRoute($waypoints, $origin, $destination)
    {
        if (empty($this->apiKey)) {
            error_log('Google Maps API key not configured');
            return false;
        }

        $url = $this->baseUrl . '/directions/json';
        
        try {
            $response = $this->client->get($url, [
                'query' => [
                    'origin' => $origin,
                    'destination' => $destination,
                    'waypoints' => 'optimize:true|' . implode('|', $waypoints),
                    'key' => $this->apiKey
                ]
            ]);

            $data = json_decode($response->getBody(), true);

            if ($data['status'] === 'OK' && !empty($data['routes'])) {
                return $data['routes'][0];
            }

            error_log('Optimized route failed: ' . ($data['status'] ?? 'Unknown error'));
            return false;

        } catch (RequestException $e) {
            error_log('Optimized route request error: ' . $e->getMessage());
            return false;
        }
    }
} 