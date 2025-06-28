<?php

/**
 * Configuration de la base de données Supabase
 * 
 * Ce fichier contient la configuration pour se connecter à Supabase
 * depuis l'application PHP.
 */

namespace App\Config;

use Dotenv\Dotenv;

class Database
{
    private static $instance = null;
    private $supabaseUrl;
    private $supabaseAnonKey;
    private $supabaseServiceKey;

    private function __construct()
    {
        // Charger les variables d'environnement
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();

        $this->supabaseUrl = $_ENV['SUPABASE_URL'];
        $this->supabaseAnonKey = $_ENV['SUPABASE_ANON_KEY'];
        $this->supabaseServiceKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'];
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getSupabaseUrl()
    {
        return $this->supabaseUrl;
    }

    public function getSupabaseAnonKey()
    {
        return $this->supabaseAnonKey;
    }

    public function getSupabaseServiceKey()
    {
        return $this->supabaseServiceKey;
    }

    public function getHeaders($useServiceKey = false)
    {
        $key = $useServiceKey ? $this->supabaseServiceKey : $this->supabaseAnonKey;
        
        return [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
            'apikey: ' . $key
        ];
    }
}

return [
    'supabase' => [
        'url' => $_ENV['SUPABASE_URL'] ?? '',
        'anon_key' => $_ENV['SUPABASE_ANON_KEY'] ?? '',
        'service_role_key' => $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '',
        
        // Configuration des endpoints API
        'endpoints' => [
            'auth' => '/auth/v1',
            'rest' => '/rest/v1',
            'realtime' => '/realtime/v1',
            'storage' => '/storage/v1',
        ],
        
        // Configuration des en-têtes par défaut
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'LivraisonP2P-PHP-Client/1.0',
        ],
        
        // Configuration des timeouts
        'timeout' => 30,
        'connect_timeout' => 10,
        
        // Configuration de la pagination
        'default_limit' => 50,
        'max_limit' => 1000,
    ],
    
    // Configuration pour les requêtes géospatiales
    'geo' => [
        'default_radius_km' => 50,
        'max_radius_km' => 200,
    ],
    
    // Configuration pour les notifications
    'notifications' => [
        'default_channels' => ['realtime', 'email'],
        'webpush' => [
            'vapid_public_key' => $_ENV['VAPID_PUBLIC_KEY'] ?? '',
            'vapid_private_key' => $_ENV['VAPID_PRIVATE_KEY'] ?? '',
        ],
    ],
    
    // Configuration pour les fichiers
    'storage' => [
        'bucket_name' => 'livraisonp2p',
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/webp',
            'application/pdf',
        ],
    ],
]; 