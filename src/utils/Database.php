<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

use DeliveryP2P\Utils\Logger;

/**
 * Classe Database adaptée pour Render
 * Connexion Supabase via ANON_KEY uniquement
 */
class Database
{
    private static ?self $instance = null;
    private string $supabaseUrl;
    private string $anonKey;
    private string $serviceRoleKey;
    private Logger $logger;
    private array $defaultHeaders;

    private function __construct()
    {
        // Variables depuis Render Dashboard uniquement
        $this->supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        $this->anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->serviceRoleKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '';
        
        if (!$this->supabaseUrl || !$this->anonKey) {
            throw new \Exception('Variables Supabase manquantes dans Render Dashboard');
        }

        $this->logger = new Logger();
        
        $this->defaultHeaders = [
            'apikey: ' . $this->anonKey,
            'Authorization: Bearer ' . $this->anonKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }

    /**
     * Instance singleton
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Effectue une requête GET vers Supabase
     */
    public function get(string $table, array $filters = [], array $options = []): array
    {
        $url = $this->buildUrl($table, $filters, $options);
        
        return $this->makeRequest($url, 'GET');
    }

    /**
     * Effectue une requête POST vers Supabase
     */
    public function post(string $table, array $data): array
    {
        $url = $this->buildUrl($table);
        
        return $this->makeRequest($url, 'POST', $data);
    }

    /**
     * Effectue une requête PUT vers Supabase
     */
    public function put(string $table, array $filters, array $data): array
    {
        $url = $this->buildUrl($table, $filters);
        
        return $this->makeRequest($url, 'PUT', $data);
    }

    /**
     * Effectue une requête DELETE vers Supabase
     */
    public function delete(string $table, array $filters): array
    {
        $url = $this->buildUrl($table, $filters);
        
        return $this->makeRequest($url, 'DELETE');
    }

    /**
     * Effectue une requête PATCH vers Supabase
     */
    public function patch(string $table, array $filters, array $data): array
    {
        $url = $this->buildUrl($table, $filters);
        
        return $this->makeRequest($url, 'PATCH', $data);
    }

    /**
     * Exécute une fonction PostgreSQL
     */
    public function rpc(string $function, array $params = []): array
    {
        $url = rtrim($this->supabaseUrl, '/') . '/rest/v1/rpc/' . $function;
        
        return $this->makeRequest($url, 'POST', $params);
    }

    /**
     * Effectue une requête SQL personnalisée
     */
    public function query(string $sql, array $params = []): array
    {
        $url = rtrim($this->supabaseUrl, '/') . '/rest/v1/';
        
        $headers = $this->defaultHeaders;
        $headers[] = 'Content-SQL: ' . $sql;
        
        return $this->makeRequest($url, 'POST', $params, $headers);
    }

    /**
     * Construit l'URL pour les requêtes
     */
    private function buildUrl(string $table, array $filters = [], array $options = []): string
    {
        $url = rtrim($this->supabaseUrl, '/') . '/rest/v1/' . $table;
        
        $queryParams = [];
        
        // Ajout des filtres
        foreach ($filters as $key => $value) {
            if (is_array($value)) {
                $queryParams[] = $key . '=in.(' . implode(',', $value) . ')';
            } else {
                $queryParams[] = $key . '=eq.' . urlencode($value);
            }
        }
        
        // Ajout des options (select, order, limit, etc.)
        foreach ($options as $key => $value) {
            $queryParams[] = $key . '=' . urlencode($value);
        }
        
        if (!empty($queryParams)) {
            $url .= '?' . implode('&', $queryParams);
        }
        
        return $url;
    }

    /**
     * Effectue la requête HTTP vers Supabase
     */
    private function makeRequest(string $url, string $method = 'GET', ?array $data = null, ?array $customHeaders = null): array
    {
        $headers = $customHeaders ?? $this->defaultHeaders;
        
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'DeliveryP2P-API/1.0'
        ]);

        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            $this->logger->error('Database request failed', [
                'url' => $url,
                'method' => $method,
                'error' => $error
            ]);
            
            throw new \Exception("Database connection error: {$error}");
        }

        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('Invalid JSON response', [
                'url' => $url,
                'method' => $method,
                'response' => $response
            ]);
            
            throw new \Exception('Invalid JSON response from database');
        }

        $this->logger->info('Database request completed', [
            'url' => $url,
            'method' => $method,
            'http_code' => $httpCode,
            'response_size' => strlen($response)
        ]);

        return [
            'data' => $responseData,
            'status_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300
        ];
    }

    /**
     * Test de connexion à Supabase
     */
    public function testConnection(): array
    {
        try {
            $result = $this->get('users', [], ['limit' => 1]);
            
            return [
                'success' => $result['success'],
                'message' => $result['success'] ? 'Connection successful' : 'Connection failed',
                'http_code' => $result['status_code']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'http_code' => 0
            ];
        }
    }

    /**
     * Utilise la clé de service pour les opérations administratives
     */
    public function withServiceRole(): self
    {
        $instance = clone $this;
        $instance->defaultHeaders = [
            'apikey: ' . $this->serviceRoleKey,
            'Authorization: Bearer ' . $this->serviceRoleKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        return $instance;
    }

    /**
     * Récupère les informations de configuration
     */
    public function getConfig(): array
    {
        return [
            'url' => $this->supabaseUrl,
            'has_anon_key' => !empty($this->anonKey),
            'has_service_key' => !empty($this->serviceRoleKey)
        ];
    }
} 