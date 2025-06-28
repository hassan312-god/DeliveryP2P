<?php

class SupabaseClient {
    private $url;
    private $anonKey;
    private $serviceKey;
    
    public function __construct() {
        $this->url = $_ENV['SUPABASE_URL'] ?? '';
        $this->anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
    }
    
    /**
     * Test de connexion à Supabase
     */
    public function testConnection(): array {
        if (empty($this->url) || empty($this->anonKey)) {
            return [
                'success' => false,
                'error' => 'Configuration Supabase manquante'
            ];
        }
        
        try {
            $response = $this->makeRequest('GET', '/rest/v1/');
            return [
                'success' => true,
                'message' => 'Connexion Supabase réussie'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Insérer des données dans une table
     */
    public function insert(string $table, array $data): array {
        try {
            $response = $this->makeRequest('POST', "/rest/v1/$table", $data);
            return [
                'success' => true,
                'data' => $response,
                'message' => 'Données insérées avec succès'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Récupérer des données d'une table
     */
    public function select(string $table, array $filters = []): array {
        try {
            $query = http_build_query($filters);
            $url = "/rest/v1/$table";
            if (!empty($query)) {
                $url .= "?$query";
            }
            
            $response = $this->makeRequest('GET', $url);
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Mettre à jour des données
     */
    public function update(string $table, array $data, array $filters): array {
        try {
            $query = http_build_query($filters);
            $url = "/rest/v1/$table";
            if (!empty($query)) {
                $url .= "?$query";
            }
            
            $response = $this->makeRequest('PATCH', $url, $data);
            return [
                'success' => true,
                'data' => $response,
                'message' => 'Données mises à jour avec succès'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Supprimer des données
     */
    public function delete(string $table, array $filters): array {
        try {
            $query = http_build_query($filters);
            $url = "/rest/v1/$table";
            if (!empty($query)) {
                $url .= "?$query";
            }
            
            $response = $this->makeRequest('DELETE', $url);
            return [
                'success' => true,
                'message' => 'Données supprimées avec succès'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Créer un profil
     */
    public function createProfile(array $profileData): array {
        return $this->insert('profiles', $profileData);
    }
    
    /**
     * Récupérer les profils
     */
    public function getProfiles(array $filters = []): array {
        return $this->select('profiles', $filters);
    }
    
    /**
     * Mettre à jour un profil
     */
    public function updateProfile(string $profileId, array $data): array {
        return $this->update('profiles', $data, ['id' => "eq.$profileId"]);
    }
    
    /**
     * Créer un QR code
     */
    public function createQRCode(array $qrData): array {
        return $this->insert('qr_codes', $qrData);
    }
    
    /**
     * Récupérer les QR codes
     */
    public function getQRCodes(array $filters = []): array {
        return $this->select('qr_codes', $filters);
    }
    
    /**
     * Créer une livraison
     */
    public function createDelivery(array $deliveryData): array {
        return $this->insert('deliveries', $deliveryData);
    }
    
    /**
     * Récupérer les livraisons
     */
    public function getDeliveries(array $filters = []): array {
        return $this->select('deliveries', $filters);
    }
    
    /**
     * Effectuer une requête HTTP vers Supabase
     */
    private function makeRequest(string $method, string $endpoint, array $data = []): array {
        $url = $this->url . $endpoint;
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->anonKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if (!empty($data) && in_array($method, ['POST', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 400) {
            throw new Exception("Erreur HTTP $httpCode: $response");
        }
        
        return json_decode($response, true) ?? [];
    }
} 