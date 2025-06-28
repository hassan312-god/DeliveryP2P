<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Service pour interagir avec l'API Supabase
 * 
 * Ce service encapsule toutes les interactions avec Supabase :
 * - Authentification des utilisateurs
 * - Opérations CRUD sur les tables
 * - Gestion des fichiers (Storage)
 * - Notifications en temps réel
 */
class SupabaseService
{
    private $client;
    private $config;
    private $baseUrl;
    private $anonKey;
    private $serviceRoleKey;
    
    public function __construct()
    {
        $this->config = require BASE_PATH . 'app/config/database.php';
        $this->baseUrl = $this->config['supabase']['url'];
        $this->anonKey = $this->config['supabase']['anon_key'];
        $this->serviceRoleKey = $this->config['supabase']['service_role_key'];
        
        $this->client = new Client([
            'timeout' => $this->config['supabase']['timeout'],
            'connect_timeout' => $this->config['supabase']['connect_timeout'],
            'headers' => $this->config['supabase']['headers'],
        ]);
    }
    
    /**
     * Méthode générique pour appeler l'API Supabase
     */
    private function _callApi($method, $endpoint, $data = [], $headers = [], $useServiceRole = false)
    {
        $url = $this->baseUrl . $endpoint;
        $apiKey = $useServiceRole ? $this->serviceRoleKey : $this->anonKey;
        
        $requestHeaders = array_merge([
            'apikey' => $apiKey,
            'Authorization' => 'Bearer ' . $apiKey,
        ], $headers);
        
        $options = [
            'headers' => $requestHeaders,
        ];
        
        if (!empty($data)) {
            if ($method === 'GET') {
                $options['query'] = $data;
            } else {
                $options['json'] = $data;
            }
        }
        
        try {
            $response = $this->client->request($method, $url, $options);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            $error = [
                'error' => true,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
            
            if ($e->hasResponse()) {
                $error['response'] = json_decode($e->getResponse()->getBody()->getContents(), true);
            }
            
            return $error;
        }
    }
    
    // =====================================================
    // MÉTHODES D'AUTHENTIFICATION
    // =====================================================
    
    /**
     * Inscrire un nouvel utilisateur
     */
    public function signUp($email, $password, $userData = [])
    {
        $data = array_merge([
            'email' => $email,
            'password' => $password,
        ], $userData);
        
        return $this->_callApi('POST', '/auth/v1/signup', $data);
    }
    
    /**
     * Connecter un utilisateur
     */
    public function signIn($email, $password)
    {
        $data = [
            'email' => $email,
            'password' => $password,
        ];
        
        return $this->_callApi('POST', '/auth/v1/token?grant_type=password', $data);
    }
    
    /**
     * Déconnecter un utilisateur
     */
    public function signOut($accessToken)
    {
        return $this->_callApi('POST', '/auth/v1/logout', [], [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
    }
    
    /**
     * Rafraîchir un token
     */
    public function refreshToken($refreshToken)
    {
        $data = [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
        ];
        
        return $this->_callApi('POST', '/auth/v1/token', $data);
    }
    
    /**
     * Récupérer les informations de l'utilisateur connecté
     */
    public function getUser($accessToken)
    {
        return $this->_callApi('GET', '/auth/v1/user', [], [
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
    }
    
    // =====================================================
    // MÉTHODES CRUD GÉNÉRIQUES
    // =====================================================
    
    /**
     * Sélectionner des données
     */
    public function select($table, $filters = [], $columns = '*', $limit = null, $offset = null)
    {
        $queryParams = [];
        
        if ($columns !== '*') {
            $queryParams['select'] = $columns;
        }
        
        if ($limit) {
            $queryParams['limit'] = $limit;
        }
        
        if ($offset) {
            $queryParams['offset'] = $offset;
        }
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $queryParams[$key] = $value;
        }
        
        return $this->_callApi('GET', '/rest/v1/' . $table, $queryParams);
    }
    
    /**
     * Insérer des données
     */
    public function insert($table, $data, $useServiceRole = false)
    {
        return $this->_callApi('POST', '/rest/v1/' . $table, $data, [], $useServiceRole);
    }
    
    /**
     * Mettre à jour des données
     */
    public function update($table, $data, $filters = [], $useServiceRole = false)
    {
        $queryParams = [];
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $queryParams[$key] = $value;
        }
        
        $url = '/rest/v1/' . $table;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        return $this->_callApi('PATCH', $url, $data, [], $useServiceRole);
    }
    
    /**
     * Supprimer des données
     */
    public function delete($table, $filters = [], $useServiceRole = false)
    {
        $queryParams = [];
        
        // Ajouter les filtres
        foreach ($filters as $key => $value) {
            $queryParams[$key] = $value;
        }
        
        $url = '/rest/v1/' . $table;
        if (!empty($queryParams)) {
            $url .= '?' . http_build_query($queryParams);
        }
        
        return $this->_callApi('DELETE', $url, [], [], $useServiceRole);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX UTILISATEURS
    // =====================================================
    
    /**
     * Créer un utilisateur
     */
    public function createUser($userData)
    {
        return $this->insert('users', $userData, true);
    }
    
    /**
     * Récupérer un utilisateur par ID
     */
    public function getUserById($id)
    {
        $result = $this->select('users', ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Récupérer un utilisateur par email
     */
    public function getUserByEmail($email)
    {
        $result = $this->select('users', ['email' => 'eq.' . $email]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Mettre à jour un utilisateur
     */
    public function updateUser($id, $data)
    {
        return $this->update('users', $data, ['id' => 'eq.' . $id]);
    }
    
    /**
     * Supprimer un utilisateur
     */
    public function deleteUser($id)
    {
        return $this->delete('users', ['id' => 'eq.' . $id], true);
    }
    
    /**
     * Récupérer tous les utilisateurs (admin)
     */
    public function getAllUsers($limit = 50, $offset = 0)
    {
        return $this->select('users', [], '*', $limit, $offset);
    }
    
    /**
     * Récupérer les livreurs disponibles
     */
    public function getAvailableCouriers($limit = 50, $offset = 0)
    {
        return $this->select('users', [
            'role' => 'eq.courier',
            'is_available' => 'eq.true'
        ], '*', $limit, $offset);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX ANNONCES
    // =====================================================
    
    /**
     * Créer une annonce
     */
    public function createAd($adData)
    {
        return $this->insert('ads', $adData);
    }
    
    /**
     * Récupérer une annonce par ID
     */
    public function getAd($id)
    {
        $result = $this->select('ads', ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Récupérer les annonces disponibles
     */
    public function getAvailableAds($limit = 50, $offset = 0)
    {
        return $this->select('ads', [
            'status' => 'eq.pending'
        ], '*', $limit, $offset);
    }
    
    /**
     * Récupérer les annonces d'un expéditeur
     */
    public function getAdsByExpeditor($expeditorId, $limit = 50, $offset = 0)
    {
        return $this->select('ads', [
            'expeditor_id' => 'eq.' . $expeditorId
        ], '*', $limit, $offset);
    }
    
    /**
     * Mettre à jour une annonce
     */
    public function updateAd($id, $data)
    {
        return $this->update('ads', $data, ['id' => 'eq.' . $id]);
    }
    
    /**
     * Supprimer une annonce
     */
    public function deleteAd($id)
    {
        return $this->delete('ads', ['id' => 'eq.' . $id]);
    }
    
    /**
     * Rechercher des annonces par critères
     */
    public function searchAds($criteria)
    {
        return $this->select('ads', $criteria);
    }
    
    /**
     * Marquer une annonce comme acceptée
     */
    public function markAdAsAccepted($id, $courierId)
    {
        return $this->update('ads', [
            'status' => 'accepted'
        ], ['id' => 'eq.' . $id]);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX LIVRAISONS
    // =====================================================
    
    /**
     * Créer une livraison
     */
    public function createDelivery($deliveryData)
    {
        return $this->insert('deliveries', $deliveryData);
    }
    
    /**
     * Récupérer une livraison par ID
     */
    public function getDelivery($id)
    {
        $result = $this->select('deliveries', ['id' => 'eq.' . $id]);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * Récupérer les livraisons d'un livreur
     */
    public function getDeliveriesByCourier($courierId, $limit = 50, $offset = 0)
    {
        return $this->select('deliveries', [
            'courier_id' => 'eq.' . $courierId
        ], '*', $limit, $offset);
    }
    
    /**
     * Récupérer les livraisons d'un expéditeur
     */
    public function getDeliveriesByExpeditor($expeditorId, $limit = 50, $offset = 0)
    {
        // Requête avec jointure pour récupérer les livraisons via les annonces
        $queryParams = [
            'select' => 'deliveries.*,ads.expeditor_id',
            'ads.expeditor_id' => 'eq.' . $expeditorId
        ];
        
        return $this->_callApi('GET', '/rest/v1/deliveries', $queryParams);
    }
    
    /**
     * Mettre à jour le statut d'une livraison
     */
    public function updateDeliveryStatus($id, $status, $additionalData = [])
    {
        $data = array_merge([
            'delivery_status' => $status
        ], $additionalData);
        
        return $this->update('deliveries', $data, ['id' => 'eq.' . $id]);
    }
    
    /**
     * Confirmer une livraison
     */
    public function confirmDelivery($id, $qrCode)
    {
        return $this->update('deliveries', [
            'delivery_status' => 'delivered',
            'delivery_time' => date('c'),
            'recipient_confirmation_time' => date('c')
        ], ['id' => 'eq.' . $id, 'qr_code_hash' => 'eq.' . $qrCode]);
    }
    
    /**
     * Récupérer toutes les livraisons (admin)
     */
    public function getAllDeliveries($limit = 50, $offset = 0)
    {
        return $this->select('deliveries', [], '*', $limit, $offset);
    }
    
    /**
     * Récupérer les livraisons par statut
     */
    public function getDeliveriesByStatus($status, $limit = 50, $offset = 0)
    {
        return $this->select('deliveries', [
            'delivery_status' => 'eq.' . $status
        ], '*', $limit, $offset);
    }
    
    /**
     * Supprimer une livraison
     */
    public function deleteDelivery($id)
    {
        return $this->delete('deliveries', ['id' => 'eq.' . $id]);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX MESSAGES DE CHAT
    // =====================================================
    
    /**
     * Envoyer un message
     */
    public function sendMessage($deliveryId, $senderId, $message, $messageType = 'text')
    {
        return $this->insert('chat_messages', [
            'delivery_id' => $deliveryId,
            'sender_id' => $senderId,
            'message' => $message,
            'message_type' => $messageType
        ]);
    }
    
    /**
     * Récupérer les messages d'une livraison
     */
    public function getMessages($deliveryId, $limit = 50, $offset = 0)
    {
        return $this->select('chat_messages', [
            'delivery_id' => 'eq.' . $deliveryId
        ], '*', $limit, $offset);
    }
    
    /**
     * Marquer les messages comme lus
     */
    public function markMessagesAsRead($deliveryId, $userId)
    {
        return $this->update('chat_messages', [
            'is_read' => true,
            'read_at' => date('c')
        ], [
            'delivery_id' => 'eq.' . $deliveryId,
            'sender_id' => 'neq.' . $userId,
            'is_read' => 'eq.false'
        ]);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX ÉVALUATIONS
    // =====================================================
    
    /**
     * Créer une évaluation
     */
    public function createEvaluation($evaluationData)
    {
        return $this->insert('evaluations', $evaluationData);
    }
    
    /**
     * Récupérer les évaluations d'un utilisateur
     */
    public function getUserEvaluations($userId, $limit = 50, $offset = 0)
    {
        return $this->select('evaluations', [
            'evaluated_user_id' => 'eq.' . $userId
        ], '*', $limit, $offset);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX NOTIFICATIONS
    // =====================================================
    
    /**
     * Créer une notification
     */
    public function createNotification($notificationData)
    {
        return $this->insert('notifications', $notificationData);
    }
    
    /**
     * Récupérer les notifications d'un utilisateur
     */
    public function getUserNotifications($userId, $limit = 50, $offset = 0)
    {
        return $this->select('notifications', [
            'user_id' => 'eq.' . $userId
        ], '*', $limit, $offset);
    }
    
    /**
     * Marquer une notification comme lue
     */
    public function markNotificationAsRead($notificationId)
    {
        return $this->update('notifications', [
            'is_read' => true
        ], ['id' => 'eq.' . $notificationId]);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AUX ABONNEMENTS WEB PUSH
    // =====================================================
    
    /**
     * Créer un abonnement Web Push
     */
    public function createWebPushSubscription($subscriptionData)
    {
        return $this->insert('web_push_subscriptions', $subscriptionData);
    }
    
    /**
     * Récupérer les abonnements d'un utilisateur
     */
    public function getUserWebPushSubscriptions($userId)
    {
        return $this->select('web_push_subscriptions', [
            'user_id' => 'eq.' . $userId,
            'is_active' => 'eq.true'
        ]);
    }
    
    /**
     * Désactiver un abonnement Web Push
     */
    public function deactivateWebPushSubscription($id)
    {
        return $this->update('web_push_subscriptions', [
            'is_active' => false
        ], ['id' => 'eq.' . $id]);
    }
    
    // =====================================================
    // MÉTHODES SPÉCIFIQUES AU STORAGE
    // =====================================================
    
    /**
     * Uploader un fichier
     */
    public function uploadFile($bucket, $path, $fileContent, $mimeType = null)
    {
        $headers = [
            'Content-Type' => $mimeType ?: 'application/octet-stream',
        ];
        
        return $this->_callApi('POST', '/storage/v1/object/' . $bucket . '/' . $path, $fileContent, $headers);
    }
    
    /**
     * Télécharger un fichier
     */
    public function downloadFile($bucket, $path)
    {
        return $this->_callApi('GET', '/storage/v1/object/' . $bucket . '/' . $path);
    }
    
    /**
     * Supprimer un fichier
     */
    public function deleteFile($bucket, $path)
    {
        return $this->_callApi('DELETE', '/storage/v1/object/' . $bucket . '/' . $path);
    }
    
    // =====================================================
    // MÉTHODES UTILITAIRES
    // =====================================================
    
    /**
     * Calculer la distance entre deux points
     */
    public function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        // Utiliser la fonction PostgreSQL si disponible, sinon calculer en PHP
        $queryParams = [
            'select' => 'calculate_distance(' . $lat1 . ',' . $lon1 . ',' . $lat2 . ',' . $lon2 . ') as distance'
        ];
        
        $result = $this->_callApi('GET', '/rest/v1/rpc/calculate_distance', $queryParams);
        return !empty($result) ? $result[0]['distance'] : null;
    }
    
    /**
     * Rechercher des annonces par proximité
     */
    public function searchAdsByProximity($latitude, $longitude, $radiusKm = 50, $limit = 50)
    {
        // Cette requête nécessite une fonction PostgreSQL personnalisée
        // ou peut être implémentée avec des filtres de distance
        $queryParams = [
            'select' => '*, calculate_distance(' . $latitude . ',' . $longitude . ',pickup_latitude,pickup_longitude) as distance',
            'status' => 'eq.pending',
            'order' => 'distance.asc',
            'limit' => $limit
        ];
        
        return $this->_callApi('GET', '/rest/v1/ads', $queryParams);
    }
} 