<?php

require_once __DIR__ . '/../services/GoogleMapsService.php';

class MapsController {
    private $mapsService;
    
    public function __construct() {
        $this->mapsService = new GoogleMapsService();
    }
    
    /**
     * Afficher la page principale avec la carte
     */
    public function index() {
        // Vérifier si l'utilisateur est connecté
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
        
        $pageTitle = 'Carte de Livraison';
        $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
        
        include __DIR__ . '/../views/maps/index.php';
    }
    
    /**
     * API: Géocoder une adresse
     */
    public function geocode() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $address = $input['address'] ?? '';
            
            if (empty($address)) {
                throw new Exception('Adresse requise');
            }
            
            $result = $this->mapsService->geocode($address);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Calculer la distance entre deux points
     */
    public function calculateDistance() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $lat1 = $input['lat1'] ?? null;
            $lng1 = $input['lng1'] ?? null;
            $lat2 = $input['lat2'] ?? null;
            $lng2 = $input['lng2'] ?? null;
            $mode = $input['mode'] ?? 'driving';
            
            if (!$lat1 || !$lng1 || !$lat2 || !$lng2) {
                throw new Exception('Coordonnées requises');
            }
            
            $result = $this->mapsService->calculateDistance($lat1, $lng1, $lat2, $lng2, $mode);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Obtenir l'itinéraire
     */
    public function getDirections() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $origin = $input['origin'] ?? '';
            $destination = $input['destination'] ?? '';
            $mode = $input['mode'] ?? 'driving';
            
            if (empty($origin) || empty($destination)) {
                throw new Exception('Origine et destination requises');
            }
            
            $result = $this->mapsService->getDirections($origin, $destination, $mode);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Rechercher des lieux
     */
    public function searchPlaces() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            $query = $input['query'] ?? '';
            $location = $input['location'] ?? null;
            $radius = $input['radius'] ?? 5000;
            
            if (empty($query)) {
                throw new Exception('Requête de recherche requise');
            }
            
            $result = $this->mapsService->searchPlaces($query, $location, $radius);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * API: Valider une adresse
     */
    public function validateAddress() {
        header('Content-Type: application/json');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Méthode non autorisée');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $address = $input['address'] ?? '';
            
            if (empty($address)) {
                throw new Exception('Adresse requise');
            }
            
            $result = $this->mapsService->validateAddress($address);
            
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
            
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Afficher la page de test de l'API
     */
    public function test() {
        $pageTitle = 'Test Google Maps API';
        $apiKey = $_ENV['GOOGLE_MAPS_API_KEY'];
        
        include __DIR__ . '/../views/maps/test.php';
    }
} 