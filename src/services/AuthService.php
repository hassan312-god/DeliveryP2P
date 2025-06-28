<?php

namespace DeliveryP2P\Services;

use DeliveryP2P\Utils\Database;
use DeliveryP2P\Utils\Logger;

/**
 * Service d'authentification pour LivraisonP2P
 * Gestion des utilisateurs avec Supabase Auth + table profiles
 */
class AuthService
{
    private Database $database;
    private Logger $logger;
    private string $supabaseUrl;
    private string $supabaseAnonKey;
    private string $supabaseServiceKey;

    public function __construct()
    {
        $this->database = Database::getInstance();
        $this->logger = new Logger();
        $this->supabaseUrl = $_ENV['SUPABASE_URL'] ?? '';
        $this->supabaseAnonKey = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->supabaseServiceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? '';
    }

    /**
     * Création d'un utilisateur via Supabase Auth
     */
    public function createSupabaseUser(array $userData): array
    {
        try {
            $this->logger->info('Création d\'un utilisateur via Supabase Auth', [
                'email' => $userData['email']
            ]);

            $url = $this->supabaseUrl . '/auth/v1/signup';
            
            $data = [
                'email' => $userData['email'],
                'password' => $userData['password'],
                'email_confirm' => $userData['email_confirm'] ?? false
            ];

            $response = $this->makeHttpRequest($url, 'POST', $data, [
                'apikey: ' . $this->supabaseAnonKey,
                'Content-Type: application/json'
            ]);

            if ($response['http_code'] === 200 || $response['http_code'] === 201) {
                $this->logger->info('Utilisateur Supabase Auth créé avec succès', [
                    'user_id' => $response['data']['user']['id'] ?? 'unknown'
                ]);
                return [
                    'success' => true,
                    'user' => $response['data']['user'],
                    'session' => $response['data']['session'] ?? null
                ];
            }

            $this->logger->error('Échec de création utilisateur Supabase Auth', [
                'http_code' => $response['http_code'],
                'error' => $response['data'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $response['data']['error_description'] ?? 'Erreur lors de la création du compte',
                'http_code' => $response['http_code']
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création utilisateur Supabase Auth', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Connexion d'un utilisateur via Supabase Auth
     */
    public function loginSupabaseUser(string $email, string $password): array
    {
        try {
            $this->logger->info('Tentative de connexion Supabase Auth', [
                'email' => $email
            ]);

            $url = $this->supabaseUrl . '/auth/v1/token?grant_type=password';
            
            $data = [
                'email' => $email,
                'password' => $password
            ];

            $response = $this->makeHttpRequest($url, 'POST', $data, [
                'apikey: ' . $this->supabaseAnonKey,
                'Content-Type: application/json'
            ]);

            if ($response['http_code'] === 200) {
                $this->logger->info('Connexion Supabase Auth réussie', [
                    'user_id' => $response['data']['user']['id'] ?? 'unknown'
                ]);
                return [
                    'success' => true,
                    'user' => $response['data']['user'],
                    'access_token' => $response['data']['access_token'],
                    'refresh_token' => $response['data']['refresh_token']
                ];
            }

            $this->logger->error('Échec de connexion Supabase Auth', [
                'http_code' => $response['http_code'],
                'error' => $response['data'] ?? 'Unknown error'
            ]);

            return [
                'success' => false,
                'error' => $response['data']['error_description'] ?? 'Email ou mot de passe incorrect',
                'http_code' => $response['http_code']
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la connexion Supabase Auth', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Création d'un profil dans la table profiles
     */
    public function createProfile(array $profileData): array
    {
        try {
            $this->logger->info('Création d\'un profil utilisateur', [
                'user_id' => $profileData['id']
            ]);

            $response = $this->database->post('profiles', $profileData);
            
            if ($response['success']) {
                $this->logger->info('Profil utilisateur créé avec succès', [
                    'user_id' => $profileData['id']
                ]);
                return [
                    'success' => true,
                    'profile' => $response['data'] ?? $profileData
                ];
            }

            $this->logger->error('Échec de création du profil utilisateur', [
                'response' => $response
            ]);
            return [
                'success' => false,
                'error' => $response['error'] ?? 'Erreur lors de la création du profil'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création du profil', [
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Recherche d'un utilisateur par email dans Supabase Auth
     */
    public function findUserByEmail(string $email): ?array
    {
        try {
            // Utilisation de l'API Admin de Supabase pour rechercher un utilisateur
            $url = $this->supabaseUrl . '/auth/v1/admin/users';
            
            $response = $this->makeHttpRequest($url, 'GET', null, [
                'apikey: ' . $this->supabaseServiceKey,
                'Authorization: Bearer ' . $this->supabaseServiceKey
            ]);

            if ($response['http_code'] === 200 && isset($response['data'])) {
                foreach ($response['data'] as $user) {
                    if ($user['email'] === $email) {
                        return $user;
                    }
                }
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche d\'utilisateur par email', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return null;
        }
    }

    /**
     * Recherche d'un profil par ID
     */
    public function findProfileById(string $userId): ?array
    {
        try {
            $response = $this->database->get("profiles?id=eq.$userId&select=*");

            if ($response['success'] && !empty($response['data'])) {
                return $response['data'][0];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche de profil par ID', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return null;
        }
    }

    /**
     * Mise à jour d'un profil
     */
    public function updateProfile(string $userId, array $updateData): bool
    {
        try {
            $this->logger->info('Mise à jour du profil utilisateur', [
                'user_id' => $userId,
                'fields' => array_keys($updateData)
            ]);

            $response = $this->database->patch("profiles?id=eq.$userId", $updateData);
            
            if ($response['success']) {
                $this->logger->info('Profil utilisateur mis à jour avec succès', [
                    'user_id' => $userId
                ]);
                return true;
            }

            $this->logger->error('Échec de mise à jour du profil utilisateur', [
                'response' => $response
            ]);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du profil', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Suppression d'un utilisateur Supabase Auth
     */
    public function deleteSupabaseUser(string $userId): bool
    {
        try {
            $url = $this->supabaseUrl . '/auth/v1/admin/users/' . $userId;
            
            $response = $this->makeHttpRequest($url, 'DELETE', null, [
                'apikey: ' . $this->supabaseServiceKey,
                'Authorization: Bearer ' . $this->supabaseServiceKey
            ]);

            if ($response['http_code'] === 200) {
                $this->logger->info('Utilisateur Supabase Auth supprimé', [
                    'user_id' => $userId
                ]);
                return true;
            }

            $this->logger->error('Échec de suppression utilisateur Supabase Auth', [
                'http_code' => $response['http_code']
            ]);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression utilisateur Supabase Auth', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Vérification d'email
     */
    public function verifyEmail(string $token): bool
    {
        try {
            $url = $this->supabaseUrl . '/auth/v1/verify';
            
            $data = [
                'token_hash' => $token,
                'type' => 'signup'
            ];

            $response = $this->makeHttpRequest($url, 'POST', $data, [
                'apikey: ' . $this->supabaseAnonKey,
                'Content-Type: application/json'
            ]);

            if ($response['http_code'] === 200) {
                // Mettre à jour le statut du profil
                $user = $response['data']['user'] ?? null;
                if ($user) {
                    $this->updateProfile($user['id'], [
                        'status' => 'active',
                        'email_verified_at' => date('c')
                    ]);
                }
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification d\'email', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envoi d'un email de réinitialisation de mot de passe
     */
    public function sendPasswordReset(string $email): bool
    {
        try {
            $url = $this->supabaseUrl . '/auth/v1/recover';
            
            $data = [
                'email' => $email
            ];

            $response = $this->makeHttpRequest($url, 'POST', $data, [
                'apikey: ' . $this->supabaseAnonKey,
                'Content-Type: application/json'
            ]);

            return $response['http_code'] === 200;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de réinitialisation', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            return false;
        }
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        try {
            $url = $this->supabaseUrl . '/auth/v1/user';
            
            $data = [
                'password' => $newPassword
            ];

            $response = $this->makeHttpRequest($url, 'PUT', $data, [
                'apikey: ' . $this->supabaseAnonKey,
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]);

            return $response['http_code'] === 200;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la réinitialisation du mot de passe', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérification de l'existence d'un utilisateur
     */
    public function userExists(string $email): bool
    {
        return $this->findUserByEmail($email) !== null;
    }

    /**
     * Récupération de tous les profils (pour l'admin)
     */
    public function getAllProfiles(int $limit = 50, int $offset = 0): array
    {
        try {
            $response = $this->database->get('profiles', [
                'select' => 'id,name,email,role,status,created_at',
                'order' => 'created_at.desc',
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response['success']) {
                return $response['data'];
            }

            return [];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des profils', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Suppression d'un profil (pour l'admin)
     */
    public function deleteProfile(string $userId): bool
    {
        try {
            $response = $this->database->delete("profiles?id=eq.$userId");
            
            if ($response['success']) {
                // Supprimer aussi l'utilisateur Supabase Auth
                $this->deleteSupabaseUser($userId);
                
                $this->logger->info('Profil supprimé', [
                    'user_id' => $userId
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression du profil', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }

    /**
     * Effectue une requête HTTP vers l'API Supabase
     */
    private function makeHttpRequest(string $url, string $method, ?array $data = null, array $headers = []): array
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
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
            throw new \Exception('Erreur cURL: ' . $error);
        }
        
        $data = json_decode($response, true);
        
        return [
            'http_code' => $httpCode,
            'data' => $data,
            'raw_response' => $response
        ];
    }
} 