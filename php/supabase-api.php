<?php
/**
 * Classe pour interagir avec l'API Supabase
 * Gestion des requêtes HTTP vers Supabase
 */

require_once 'config.php';

class SupabaseAPI {
    private $baseUrl;
    private $anonKey;
    private $serviceKey;
    
    public function __construct() {
        $this->baseUrl = SUPABASE_URL;
        $this->anonKey = SUPABASE_ANON_KEY;
        $this->serviceKey = SUPABASE_SERVICE_KEY;
    }
    
    /**
     * Effectuer une requête HTTP vers Supabase
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null, $useServiceKey = false) {
        $url = $this->baseUrl . '/rest/v1/' . ltrim($endpoint, '/');
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Utiliser la clé service pour les opérations admin
        if ($useServiceKey && !empty($this->serviceKey)) {
            $headers[] = 'Authorization: Bearer ' . $this->serviceKey;
        } else {
            $headers[] = 'apikey: ' . $this->anonKey;
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            logMessage('ERROR', 'Erreur cURL: ' . $error);
            return ['success' => false, 'error' => 'Erreur de connexion'];
        }
        
        $responseData = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'data' => $responseData];
        } else {
            logMessage('ERROR', 'Erreur API Supabase', [
                'endpoint' => $endpoint,
                'method' => $method,
                'httpCode' => $httpCode,
                'response' => $responseData
            ]);
            return ['success' => false, 'error' => $responseData['message'] ?? 'Erreur API'];
        }
    }
    
    /**
     * Authentifier un utilisateur
     */
    public function authenticate($email, $password) {
        $url = $this->baseUrl . '/auth/v1/token?grant_type=password';
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->anonKey
        ];
        
        $data = [
            'email' => $email,
            'password' => $password
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($responseData['access_token'])) {
            return ['success' => true, 'data' => $responseData];
        } else {
            return ['success' => false, 'error' => $responseData['error_description'] ?? 'Erreur d\'authentification'];
        }
    }
    
    /**
     * Créer un nouvel utilisateur
     */
    public function createUser($userData) {
        $url = $this->baseUrl . '/auth/v1/signup';
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . $this->anonKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($userData)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $responseData = json_decode($response, true);
        
        if ($httpCode === 200) {
            return ['success' => true, 'data' => $responseData];
        } else {
            return ['success' => false, 'error' => $responseData['error_description'] ?? 'Erreur de création d\'utilisateur'];
        }
    }
    
    /**
     * Récupérer les données d'une table
     */
    public function select($table, $filters = [], $orderBy = null, $limit = null) {
        $endpoint = $table;
        $params = [];
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $params[] = $key . '=eq.' . urlencode($value);
        }
        
        // Ajouter l'ordre
        if ($orderBy) {
            $params[] = 'order=' . urlencode($orderBy);
        }
        
        // Ajouter la limite
        if ($limit) {
            $params[] = 'limit=' . $limit;
        }
        
        if (!empty($params)) {
            $endpoint .= '?' . implode('&', $params);
        }
        
        return $this->makeRequest($endpoint);
    }
    
    /**
     * Insérer des données dans une table
     */
    public function insert($table, $data) {
        return $this->makeRequest($table, 'POST', $data);
    }
    
    /**
     * Mettre à jour des données dans une table
     */
    public function update($table, $data, $filters = []) {
        $endpoint = $table;
        $params = [];
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $params[] = $key . '=eq.' . urlencode($value);
        }
        
        if (!empty($params)) {
            $endpoint .= '?' . implode('&', $params);
        }
        
        return $this->makeRequest($endpoint, 'PATCH', $data);
    }
    
    /**
     * Supprimer des données d'une table
     */
    public function delete($table, $filters = []) {
        $endpoint = $table;
        $params = [];
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $params[] = $key . '=eq.' . urlencode($value);
        }
        
        if (!empty($params)) {
            $endpoint .= '?' . implode('&', $params);
        }
        
        return $this->makeRequest($endpoint, 'DELETE');
    }
    
    /**
     * Exécuter une fonction RPC
     */
    public function rpc($function, $params = []) {
        $endpoint = 'rpc/' . $function;
        return $this->makeRequest($endpoint, 'POST', $params);
    }
    
    /**
     * Récupérer les profils utilisateurs
     */
    public function getProfiles($filters = []) {
        return $this->select('profiles', $filters, 'created_at.desc');
    }
    
    /**
     * Récupérer les livraisons
     */
    public function getDeliveries($filters = []) {
        return $this->select('deliveries', $filters, 'created_at.desc');
    }
    
    /**
     * Récupérer les QR codes
     */
    public function getQRCodes($filters = []) {
        return $this->select('qr_codes', $filters, 'created_at.desc');
    }
    
    /**
     * Récupérer les paiements
     */
    public function getPayments($filters = []) {
        return $this->select('payments', $filters, 'created_at.desc');
    }
    
    /**
     * Récupérer les notifications
     */
    public function getNotifications($filters = []) {
        return $this->select('notifications', $filters, 'created_at.desc');
    }
    
    /**
     * Créer une livraison
     */
    public function createDelivery($deliveryData) {
        return $this->insert('deliveries', $deliveryData);
    }
    
    /**
     * Créer un QR code
     */
    public function createQRCode($qrCodeData) {
        return $this->insert('qr_codes', $qrCodeData);
    }
    
    /**
     * Créer un paiement
     */
    public function createPayment($paymentData) {
        return $this->insert('payments', $paymentData);
    }
    
    /**
     * Créer une notification
     */
    public function createNotification($notificationData) {
        return $this->insert('notifications', $notificationData);
    }
    
    /**
     * Mettre à jour le statut d'une livraison
     */
    public function updateDeliveryStatus($deliveryId, $status, $additionalData = []) {
        $data = array_merge(['status' => $status], $additionalData);
        return $this->update('deliveries', $data, ['id' => $deliveryId]);
    }
    
    /**
     * Obtenir les statistiques des QR codes
     */
    public function getQRCodeStats($userId) {
        return $this->rpc('get_qr_code_stats', ['p_user_id' => $userId]);
    }
    
    /**
     * Rechercher dans les QR codes
     */
    public function searchQRCodes($userId, $searchTerm, $type = null) {
        $params = [
            'p_user_id' => $userId,
            'p_search_term' => $searchTerm
        ];
        
        if ($type) {
            $params['p_type'] = $type;
        }
        
        return $this->rpc('search_qr_codes', $params);
    }
    
    /**
     * Exporter les QR codes d'un utilisateur
     */
    public function exportUserQRCodes($userId) {
        return $this->rpc('export_user_qr_codes', ['p_user_id' => $userId]);
    }
    
    /**
     * Obtenir les statistiques générales
     */
    public function getGeneralStats() {
        $stats = [];
        
        // Statistiques des utilisateurs
        $usersResult = $this->select('profiles');
        if ($usersResult['success']) {
            $stats['total_users'] = count($usersResult['data']);
            $stats['users_by_role'] = [];
            foreach ($usersResult['data'] as $user) {
                $role = $user['role'] ?? 'unknown';
                $stats['users_by_role'][$role] = ($stats['users_by_role'][$role] ?? 0) + 1;
            }
        }
        
        // Statistiques des livraisons
        $deliveriesResult = $this->select('deliveries');
        if ($deliveriesResult['success']) {
            $stats['total_deliveries'] = count($deliveriesResult['data']);
            $stats['deliveries_by_status'] = [];
            foreach ($deliveriesResult['data'] as $delivery) {
                $status = $delivery['status'] ?? 'unknown';
                $stats['deliveries_by_status'][$status] = ($stats['deliveries_by_status'][$status] ?? 0) + 1;
            }
        }
        
        // Statistiques des QR codes
        $qrCodesResult = $this->select('qr_codes');
        if ($qrCodesResult['success']) {
            $stats['total_qr_codes'] = count($qrCodesResult['data']);
            $stats['qr_codes_by_type'] = [];
            foreach ($qrCodesResult['data'] as $qrCode) {
                $type = $qrCode['type'] ?? 'unknown';
                $stats['qr_codes_by_type'][$type] = ($stats['qr_codes_by_type'][$type] ?? 0) + 1;
            }
        }
        
        // Statistiques des paiements
        $paymentsResult = $this->select('payments');
        if ($paymentsResult['success']) {
            $stats['total_payments'] = count($paymentsResult['data']);
            $stats['total_amount'] = 0;
            foreach ($paymentsResult['data'] as $payment) {
                $stats['total_amount'] += $payment['amount'] ?? 0;
            }
        }
        
        return ['success' => true, 'data' => $stats];
    }
    
    /**
     * Vérifier la santé de l'API
     */
    public function healthCheck() {
        $result = $this->select('profiles', [], null, 1);
        return [
            'success' => $result['success'],
            'timestamp' => date('Y-m-d H:i:s'),
            'api_status' => $result['success'] ? 'healthy' : 'unhealthy'
        ];
    }
}
?> 