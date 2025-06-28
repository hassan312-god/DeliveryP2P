<?php

namespace DeliveryP2P\Controllers;

use DeliveryP2P\Core\Exceptions\ApiException;
use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Utils\Response;
use DeliveryP2P\Utils\Database;
use DeliveryP2P\Services\AuthService;
use DeliveryP2P\Services\JWTService;

/**
 * Contrôleur d'authentification pour LivraisonP2P
 * Gestion de l'inscription, connexion et gestion des profils utilisateurs
 * Utilise Supabase Auth + table profiles
 */
class AuthController
{
    private Logger $logger;
    private AuthService $authService;
    private JWTService $jwtService;
    private Database $database;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->authService = new AuthService();
        $this->jwtService = new JWTService();
        $this->database = Database::getInstance();
    }

    /**
     * Inscription d'un nouvel utilisateur via Supabase Auth + profiles
     */
    public function register(): array
    {
        try {
            $this->logger->info('Tentative d\'inscription d\'un nouvel utilisateur via Supabase Auth');

            // Récupération des données de la requête
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new ApiException('Données JSON invalides', 400);
            }

            // Validation des données requises
            $requiredFields = ['email', 'password', 'name', 'phone'];
            foreach ($requiredFields as $field) {
                if (empty($input[$field])) {
                    throw new ApiException("Le champ '$field' est requis", 400);
                }
            }

            // Validation de l'email
            if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ApiException('Format d\'email invalide', 400);
            }

            // Validation du mot de passe
            if (strlen($input['password']) < 6) {
                throw new ApiException('Le mot de passe doit contenir au moins 6 caractères', 400);
            }

            // Test de connexion à Supabase
            $dbTest = $this->database->testConnection();
            if (!$dbTest['success']) {
                $this->logger->error('Échec de connexion à Supabase lors de l\'inscription', [
                    'error' => $dbTest['message'],
                    'http_code' => $dbTest['http_code']
                ]);
                throw new ApiException('Erreur de connexion à la base de données', 503);
            }

            // Vérification si l'utilisateur existe déjà dans Supabase Auth
            $existingUser = $this->authService->findUserByEmail($input['email']);
            if ($existingUser) {
                throw new ApiException('Un utilisateur avec cet email existe déjà', 409);
            }

            // 1. Création de l'utilisateur via Supabase Auth
            $authData = [
                'email' => $input['email'],
                'password' => $input['password'],
                'email_confirm' => true // Auto-confirmation pour le test
            ];

            $authResponse = $this->authService->createSupabaseUser($authData);
            
            if (!$authResponse['success']) {
                $this->logger->error('Échec de création utilisateur Supabase Auth', [
                    'error' => $authResponse['error']
                ]);
                throw new ApiException('Erreur lors de la création du compte utilisateur', 500);
            }

            $authUserId = $authResponse['user']['id'];

            // 2. Création du profil dans la table profiles
            $profileData = [
                'id' => $authUserId, // Utilise l'ID Supabase Auth comme clé primaire
                'name' => $input['name'],
                'phone' => $input['phone'],
                'role' => $input['role'] ?? 'client',
                'status' => 'active', // Actif par défaut
                'created_at' => date('c'),
                'updated_at' => date('c')
            ];

            $profileResponse = $this->authService->createProfile($profileData);
            
            if (!$profileResponse['success']) {
                // Si la création du profil échoue, supprimer l'utilisateur Auth
                $this->authService->deleteSupabaseUser($authUserId);
                throw new ApiException('Erreur lors de la création du profil utilisateur', 500);
            }

            // Génération du token JWT
            $token = $this->jwtService->generateToken([
                'user_id' => $authUserId,
                'email' => $input['email'],
                'role' => $profileData['role']
            ]);

            $this->logger->info('Inscription réussie via Supabase Auth', [
                'auth_user_id' => $authUserId,
                'email' => $input['email']
            ]);

            return Response::success([
                'message' => 'Inscription réussie',
                'user' => [
                    'id' => $authUserId,
                    'email' => $input['email'],
                    'name' => $input['name'],
                    'role' => $profileData['role'],
                    'status' => $profileData['status']
                ],
                'token' => $token,
                'supabase_connection' => [
                    'status' => 'connected',
                    'test_result' => $dbTest
                ]
            ], 201);

        } catch (ApiException $e) {
            $this->logger->error('Erreur lors de l\'inscription', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue lors de l\'inscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Connexion d'un utilisateur via Supabase Auth
     */
    public function login(): array
    {
        try {
            $this->logger->info('Tentative de connexion utilisateur via Supabase Auth');

            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || empty($input['email']) || empty($input['password'])) {
                throw new ApiException('Email et mot de passe requis', 400);
            }

            // Test de connexion à Supabase
            $dbTest = $this->database->testConnection();
            if (!$dbTest['success']) {
                throw new ApiException('Erreur de connexion à la base de données', 503);
            }

            // Connexion via Supabase Auth
            $loginResponse = $this->authService->loginSupabaseUser($input['email'], $input['password']);
            
            if (!$loginResponse['success']) {
                throw new ApiException('Email ou mot de passe incorrect', 401);
            }

            $authUser = $loginResponse['user'];
            $accessToken = $loginResponse['access_token'];

            // Récupération du profil utilisateur
            $profile = $this->authService->findProfileById($authUser['id']);
            if (!$profile) {
                throw new ApiException('Profil utilisateur non trouvé', 404);
            }

            // Vérification du statut
            if ($profile['status'] !== 'active') {
                throw new ApiException('Compte non activé. Veuillez vérifier votre email.', 403);
            }

            // Génération du token JWT local (optionnel, car Supabase fournit déjà un token)
            $jwtToken = $this->jwtService->generateToken([
                'user_id' => $authUser['id'],
                'email' => $authUser['email'],
                'role' => $profile['role']
            ]);

            $this->logger->info('Connexion réussie via Supabase Auth', [
                'auth_user_id' => $authUser['id'],
                'email' => $authUser['email']
            ]);

            return Response::success([
                'message' => 'Connexion réussie',
                'user' => [
                    'id' => $authUser['id'],
                    'email' => $authUser['email'],
                    'name' => $profile['name'],
                    'role' => $profile['role'],
                    'status' => $profile['status']
                ],
                'token' => $jwtToken,
                'supabase_token' => $accessToken, // Token Supabase pour les requêtes API
                'supabase_connection' => [
                    'status' => 'connected',
                    'test_result' => $dbTest
                ]
            ]);

        } catch (ApiException $e) {
            $this->logger->error('Erreur lors de la connexion', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur inattendue lors de la connexion', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Récupération du profil utilisateur
     */
    public function profile(): array
    {
        try {
            $userId = $this->getCurrentUserId();
            
            // Récupération du profil depuis la table profiles
            $profile = $this->authService->findProfileById($userId);
            
            if (!$profile) {
                throw new ApiException('Profil utilisateur non trouvé', 404);
            }

            return Response::success([
                'profile' => $profile
            ]);

        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la récupération du profil', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Mise à jour du profil utilisateur
     */
    public function updateProfile(): array
    {
        try {
            $userId = $this->getCurrentUserId();
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                throw new ApiException('Données JSON invalides', 400);
            }

            $updateData = [];
            $allowedFields = ['name', 'phone', 'address'];

            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }

            if (empty($updateData)) {
                throw new ApiException('Aucune donnée à mettre à jour', 400);
            }

            $updateData['updated_at'] = date('c');
            
            $success = $this->authService->updateProfile($userId, $updateData);
            
            if (!$success) {
                throw new ApiException('Erreur lors de la mise à jour du profil', 500);
            }

            return Response::success([
                'message' => 'Profil mis à jour avec succès'
            ]);

        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise à jour du profil', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Déconnexion
     */
    public function logout(): array
    {
        try {
            $token = $this->getBearerToken();
            if ($token) {
                $this->jwtService->blacklistToken($token);
            }

            return Response::success([
                'message' => 'Déconnexion réussie'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la déconnexion', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Vérification d'email
     */
    public function verifyEmail(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['token'])) {
                throw new ApiException('Token de vérification requis', 400);
            }

            $success = $this->authService->verifyEmail($input['token']);
            
            if (!$success) {
                throw new ApiException('Token de vérification invalide ou expiré', 400);
            }

            return Response::success([
                'message' => 'Email vérifié avec succès'
            ]);

        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification d\'email', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Mot de passe oublié
     */
    public function forgotPassword(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['email'])) {
                throw new ApiException('Email requis', 400);
            }

            $success = $this->authService->sendPasswordReset($input['email']);
            
            return Response::success([
                'message' => 'Email de réinitialisation envoyé'
            ]);

        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la demande de réinitialisation', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Réinitialisation du mot de passe
     */
    public function resetPassword(): array
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['token']) || empty($input['password'])) {
                throw new ApiException('Token et nouveau mot de passe requis', 400);
            }

            $success = $this->authService->resetPassword($input['token'], $input['password']);
            
            if (!$success) {
                throw new ApiException('Token invalide ou expiré', 400);
            }

            return Response::success([
                'message' => 'Mot de passe réinitialisé avec succès'
            ]);

        } catch (ApiException $e) {
            return Response::error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la réinitialisation du mot de passe', [
                'error' => $e->getMessage()
            ]);
            return Response::error('Erreur interne du serveur', 500);
        }
    }

    /**
     * Récupération de l'ID utilisateur depuis le token JWT
     */
    private function getCurrentUserId(): string
    {
        $token = $this->getBearerToken();
        if (!$token) {
            throw new ApiException('Token d\'authentification requis', 401);
        }

        $payload = $this->jwtService->validateToken($token);
        return $payload['user_id'];
    }

    /**
     * Récupération du token Bearer depuis les headers
     */
    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
} 