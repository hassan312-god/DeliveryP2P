<?php

namespace DeliveryP2P\Services;

use DeliveryP2P\Utils\Database;
use DeliveryP2P\Utils\Logger;

/**
 * Service d'authentification pour LivraisonP2P
 * Gestion des utilisateurs avec Supabase
 */
class AuthService
{
    private Database $database;
    private Logger $logger;

    public function __construct()
    {
        $this->database = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Création d'un nouvel utilisateur
     */
    public function createUser(array $userData): ?int
    {
        try {
            $this->logger->info('Création d\'un nouvel utilisateur', [
                'email' => $userData['email']
            ]);

            $response = $this->database->post('users', $userData);
            
            if ($response['success'] && isset($response['data']['id'])) {
                $this->logger->info('Utilisateur créé avec succès', [
                    'user_id' => $response['data']['id']
                ]);
                return $response['data']['id'];
            }

            $this->logger->error('Échec de création de l\'utilisateur', [
                'response' => $response
            ]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la création de l\'utilisateur', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Recherche d'un utilisateur par email
     */
    public function findUserByEmail(string $email): ?array
    {
        try {
            $response = $this->database->get('users', [
                'email' => 'eq.' . $email,
                'select' => '*'
            ]);

            if ($response['success'] && !empty($response['data'])) {
                return $response['data'][0];
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
     * Recherche d'un utilisateur par ID
     */
    public function findUserById(int $userId): ?array
    {
        try {
            $response = $this->database->get("users?id=eq.$userId&select=*");

            if ($response['success'] && !empty($response['data'])) {
                return $response['data'][0];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la recherche d\'utilisateur par ID', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return null;
        }
    }

    /**
     * Mise à jour d'un utilisateur
     */
    public function updateUser(int $userId, array $updateData): bool
    {
        try {
            $this->logger->info('Mise à jour de l\'utilisateur', [
                'user_id' => $userId,
                'fields' => array_keys($updateData)
            ]);

            $response = $this->database->patch("users?id=eq.$userId", $updateData);
            
            if ($response['success']) {
                $this->logger->info('Utilisateur mis à jour avec succès', [
                    'user_id' => $userId
                ]);
                return true;
            }

            $this->logger->error('Échec de mise à jour de l\'utilisateur', [
                'response' => $response
            ]);
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour de l\'utilisateur', [
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
            // Recherche du token dans la base de données
            $response = $this->database->get('email_verifications', [
                'token' => 'eq.' . $token,
                'select' => 'user_id,expires_at'
            ]);

            if (!$response['success'] || empty($response['data'])) {
                return false;
            }

            $verification = $response['data'][0];
            
            // Vérification de l'expiration
            if (strtotime($verification['expires_at']) < time()) {
                return false;
            }

            // Activation de l'utilisateur
            $updateData = [
                'status' => 'active',
                'email_verified_at' => date('c'),
                'updated_at' => date('c')
            ];

            $updateResponse = $this->database->patch("users?id=eq.{$verification['user_id']}", $updateData);
            
            if ($updateResponse['success']) {
                // Suppression du token de vérification
                $this->database->delete("email_verifications?token=eq.$token");
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
            // Recherche de l'utilisateur
            $user = $this->findUserByEmail($email);
            if (!$user) {
                return false; // On ne révèle pas si l'email existe
            }

            // Génération d'un token de réinitialisation
            $token = bin2hex(random_bytes(32));
            $expiresAt = date('c', strtotime('+1 hour'));

            $resetData = [
                'user_id' => $user['id'],
                'token' => $token,
                'expires_at' => $expiresAt,
                'created_at' => date('c')
            ];

            $response = $this->database->post('password_resets', $resetData);
            
            if ($response['success']) {
                // Ici, vous pourriez envoyer un email avec le token
                // Pour le moment, on log juste le token
                $this->logger->info('Token de réinitialisation généré', [
                    'user_id' => $user['id'],
                    'token' => $token
                ]);
                return true;
            }

            return false;

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
            // Recherche du token
            $response = $this->database->get('password_resets', [
                'token' => 'eq.' . $token,
                'select' => 'user_id,expires_at'
            ]);

            if (!$response['success'] || empty($response['data'])) {
                return false;
            }

            $reset = $response['data'][0];
            
            // Vérification de l'expiration
            if (strtotime($reset['expires_at']) < time()) {
                return false;
            }

            // Mise à jour du mot de passe
            $updateData = [
                'password' => password_hash($newPassword, PASSWORD_DEFAULT),
                'updated_at' => date('c')
            ];

            $updateResponse = $this->database->patch("users?id=eq.{$reset['user_id']}", $updateData);
            
            if ($updateResponse['success']) {
                // Suppression du token de réinitialisation
                $this->database->delete("password_resets?token=eq.$token");
                return true;
            }

            return false;

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
     * Récupération de tous les utilisateurs (pour l'admin)
     */
    public function getAllUsers(int $limit = 50, int $offset = 0): array
    {
        try {
            $response = $this->database->get('users', [
                'select' => 'id,email,name,role,status,created_at',
                'order' => 'created_at.desc',
                'limit' => $limit,
                'offset' => $offset
            ]);

            if ($response['success']) {
                return $response['data'];
            }

            return [];

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération des utilisateurs', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Suppression d'un utilisateur (pour l'admin)
     */
    public function deleteUser(int $userId): bool
    {
        try {
            $response = $this->database->delete("users?id=eq.$userId");
            
            if ($response['success']) {
                $this->logger->info('Utilisateur supprimé', [
                    'user_id' => $userId
                ]);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la suppression de l\'utilisateur', [
                'error' => $e->getMessage(),
                'user_id' => $userId
            ]);
            return false;
        }
    }
} 