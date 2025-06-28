<?php
/**
 * Interface Supabase pour LivraisonP2P
 * Gestion des opérations CRUD avec Supabase
 */

require_once 'config.php';

class SupabaseAPI {
    private $supabaseUrl;
    private $supabaseKey;
    private $serviceKey;
    private $httpClient;

    public function __construct() {
        $this->supabaseUrl = SUPABASE_URL;
        $this->supabaseKey = SUPABASE_ANON_KEY;
        $this->serviceKey = SUPABASE_SERVICE_KEY;
        $this->httpClient = new GuzzleHttp\Client([
            'timeout' => 30,
            'verify' => true
        ]);
    }

    /**
     * Authentification utilisateur
     */
    public function authenticate($email, $password) {
        $url = $this->supabaseUrl . '/auth/v1/token?grant_type=password';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->supabaseKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Inscription utilisateur
     */
    public function register($userData) {
        $url = $this->supabaseUrl . '/auth/v1/signup';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->supabaseKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'email' => $userData['email'],
                'password' => $userData['password'],
                'user_metadata' => [
                    'first_name' => $userData['first_name'],
                    'last_name' => $userData['last_name'],
                    'phone' => $userData['phone'] ?? null,
                    'user_type' => $userData['user_type'] ?? 'client'
                ]
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Récupération des données utilisateur
     */
    public function getUser($userId) {
        $url = $this->supabaseUrl . '/rest/v1/users?id=eq.' . $userId;
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        $users = json_decode($response->getBody(), true);
        return $users[0] ?? null;
    }

    /**
     * Mise à jour utilisateur
     */
    public function updateUser($userId, $userData) {
        $url = $this->supabaseUrl . '/rest/v1/users?id=eq.' . $userId;
        
        $response = $this->httpClient->patch($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ],
            'json' => $userData
        ]);

        return $response->getStatusCode() === 204;
    }

    /**
     * Création d'une livraison
     */
    public function createDelivery($deliveryData) {
        $url = $this->supabaseUrl . '/rest/v1/deliveries';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'user_id' => $deliveryData['user_id'],
                'pickup_address' => $deliveryData['pickup_address'],
                'delivery_address' => $deliveryData['delivery_address'],
                'pickup_lat' => $deliveryData['pickup_lat'],
                'pickup_lng' => $deliveryData['pickup_lng'],
                'delivery_lat' => $deliveryData['delivery_lat'],
                'delivery_lng' => $deliveryData['delivery_lng'],
                'description' => $deliveryData['description'],
                'weight' => $deliveryData['weight'],
                'dimensions' => $deliveryData['dimensions'],
                'fragile' => $deliveryData['fragile'] ?? false,
                'urgent' => $deliveryData['urgent'] ?? false,
                'budget' => $deliveryData['budget'],
                'status' => 'pending',
                'created_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Récupération des livraisons
     */
    public function getDeliveries($filters = []) {
        $url = $this->supabaseUrl . '/rest/v1/deliveries';
        
        $queryParams = [];
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $queryParams[] = $key . '=eq.' . urlencode($value);
            }
        }
        
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Mise à jour d'une livraison
     */
    public function updateDelivery($deliveryId, $deliveryData) {
        $url = $this->supabaseUrl . '/rest/v1/deliveries?id=eq.' . $deliveryId;
        
        $response = $this->httpClient->patch($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => $deliveryData
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Suppression d'une livraison
     */
    public function deleteDelivery($deliveryId) {
        $url = $this->supabaseUrl . '/rest/v1/deliveries?id=eq.' . $deliveryId;
        
        $response = $this->httpClient->delete($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        return $response->getStatusCode() === 204;
    }

    /**
     * Création d'un QR code
     */
    public function createQRCode($qrData) {
        $url = $this->supabaseUrl . '/rest/v1/qr_codes';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'delivery_id' => $qrData['delivery_id'],
                'qr_code' => $qrData['qr_code'],
                'encrypted_data' => $qrData['encrypted_data'],
                'expires_at' => $qrData['expires_at'],
                'created_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Validation d'un QR code
     */
    public function validateQRCode($qrCode) {
        $url = $this->supabaseUrl . '/rest/v1/qr_codes?qr_code=eq.' . $qrCode;
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        $qrCodes = json_decode($response->getBody(), true);
        return $qrCodes[0] ?? null;
    }

    /**
     * Enregistrement d'un scan de QR code
     */
    public function recordQRScan($scanData) {
        $url = $this->supabaseUrl . '/rest/v1/qr_scans';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'qr_code' => $scanData['qr_code'],
                'delivery_id' => $scanData['delivery_id'],
                'scanned_by' => $scanData['scanned_by'],
                'latitude' => $scanData['latitude'],
                'longitude' => $scanData['longitude'],
                'device_info' => $scanData['device_info'],
                'ip_address' => $scanData['ip_address'],
                'scanned_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Création d'une notification
     */
    public function createNotification($notificationData) {
        $url = $this->supabaseUrl . '/rest/v1/notifications';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'user_id' => $notificationData['user_id'],
                'title' => $notificationData['title'],
                'message' => $notificationData['message'],
                'type' => $notificationData['type'],
                'delivery_id' => $notificationData['delivery_id'] ?? null,
                'read' => false,
                'created_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Récupération des notifications
     */
    public function getNotifications($userId, $limit = 50) {
        $url = $this->supabaseUrl . '/rest/v1/notifications?user_id=eq.' . $userId . '&order=created_at.desc&limit=' . $limit;
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead($notificationId) {
        $url = $this->supabaseUrl . '/rest/v1/notifications?id=eq.' . $notificationId;
        
        $response = $this->httpClient->patch($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'read' => true,
                'read_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Création d'un paiement
     */
    public function createPayment($paymentData) {
        $url = $this->supabaseUrl . '/rest/v1/payments';
        
        $response = $this->httpClient->post($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => [
                'delivery_id' => $paymentData['delivery_id'],
                'user_id' => $paymentData['user_id'],
                'courier_id' => $paymentData['courier_id'] ?? null,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'] ?? 'EUR',
                'payment_method' => $paymentData['payment_method'],
                'status' => $paymentData['status'],
                'stripe_payment_intent_id' => $paymentData['stripe_payment_intent_id'] ?? null,
                'created_at' => date('c')
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Mise à jour d'un paiement
     */
    public function updatePayment($paymentId, $paymentData) {
        $url = $this->supabaseUrl . '/rest/v1/payments?id=eq.' . $paymentId;
        
        $response = $this->httpClient->patch($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=representation'
            ],
            'json' => $paymentData
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Récupération des statistiques
     */
    public function getStats($userId = null, $period = 'month') {
        $url = $this->supabaseUrl . '/rest/v1/stats';
        
        $queryParams = ['period=eq.' . $period];
        if ($userId) {
            $queryParams[] = 'user_id=eq.' . $userId;
        }
        
        $url .= '?' . implode('&', $queryParams);
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Recherche de livraisons
     */
    public function searchDeliveries($query, $filters = []) {
        $url = $this->supabaseUrl . '/rest/v1/deliveries';
        
        $queryParams = [];
        if (!empty($query)) {
            $queryParams[] = 'or=(description.ilike.%' . urlencode($query) . '%,pickup_address.ilike.%' . urlencode($query) . '%,delivery_address.ilike.%' . urlencode($query) . '%)';
        }
        
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                $queryParams[] = $key . '=eq.' . urlencode($value);
            }
        }
        
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }
        
        $response = $this->httpClient->get($url, [
            'headers' => [
                'apikey' => $this->serviceKey,
                'Authorization' => 'Bearer ' . $this->serviceKey
            ]
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Gestion des erreurs
     */
    private function handleError($response) {
        $statusCode = $response->getStatusCode();
        $body = json_decode($response->getBody(), true);
        
        if ($statusCode >= 400) {
            throw new Exception('Supabase API Error: ' . ($body['message'] ?? 'Unknown error'), $statusCode);
        }
        
        return $body;
    }
} 