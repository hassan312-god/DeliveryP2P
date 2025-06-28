<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Middleware;

use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Utils\JWTManager;
use DeliveryP2P\Core\Exceptions\UnauthorizedException;

/**
 * Middleware d'authentification JWT
 * Sécurisation des routes protégées
 */
class AuthMiddleware implements MiddlewareInterface
{
    private Logger $logger;
    private JWTManager $jwtManager;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->jwtManager = new JWTManager();
    }

    public function handle(string $method, string $uri): ?array
    {
        try {
            // Récupération du token depuis les headers
            $token = $this->extractToken();

            if (!$token) {
                $this->logger->warning('Auth: No token provided', [
                    'uri' => $uri,
                    'method' => $method,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                throw new UnauthorizedException('Authentication token required');
            }

            // Validation du token JWT
            $payload = $this->jwtManager->validateToken($token);

            if (!$payload) {
                $this->logger->warning('Auth: Invalid token', [
                    'uri' => $uri,
                    'method' => $method,
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]);

                throw new UnauthorizedException('Invalid authentication token');
            }

            // Vérification de l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                $this->logger->warning('Auth: Token expired', [
                    'uri' => $uri,
                    'method' => $method,
                    'user_id' => $payload['user_id'] ?? 'unknown'
                ]);

                throw new UnauthorizedException('Authentication token expired');
            }

            // Stockage des informations utilisateur dans la session
            $this->setUserContext($payload);

            $this->logger->info('Auth: Successful authentication', [
                'uri' => $uri,
                'method' => $method,
                'user_id' => $payload['user_id'] ?? 'unknown'
            ]);

            return null; // Continuer vers le prochain middleware

        } catch (UnauthorizedException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 401
            ];
        } catch (\Exception $e) {
            $this->logger->error('Auth: Unexpected error', [
                'error' => $e->getMessage(),
                'uri' => $uri,
                'method' => $method
            ]);

            return [
                'success' => false,
                'error' => 'Authentication failed',
                'code' => 500
            ];
        }
    }

    /**
     * Extrait le token JWT depuis les headers
     */
    private function extractToken(): ?string
    {
        // Vérification du header Authorization
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        // Vérification du header X-API-Key (fallback)
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKey)) {
            return $apiKey;
        }

        // Vérification dans les cookies (pour les applications web)
        $tokenCookie = $_COOKIE['auth_token'] ?? '';
        if (!empty($tokenCookie)) {
            return $tokenCookie;
        }

        return null;
    }

    /**
     * Définit le contexte utilisateur pour la requête
     */
    private function setUserContext(array $payload): void
    {
        // Stockage dans les variables globales pour accès facile
        $GLOBALS['current_user'] = [
            'id' => $payload['user_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? 'user',
            'permissions' => $payload['permissions'] ?? []
        ];

        // Stockage dans la session si disponible
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['user'] = $GLOBALS['current_user'];
        }
    }

    /**
     * Récupère le contexte utilisateur actuel
     */
    public static function getCurrentUser(): ?array
    {
        return $GLOBALS['current_user'] ?? null;
    }

    /**
     * Vérifie si l'utilisateur a une permission spécifique
     */
    public static function hasPermission(string $permission): bool
    {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }

        // Vérification du rôle admin
        if ($user['role'] === 'admin') {
            return true;
        }

        // Vérification des permissions spécifiques
        return in_array($permission, $user['permissions'] ?? []);
    }

    /**
     * Vérifie si l'utilisateur a un rôle spécifique
     */
    public static function hasRole(string $role): bool
    {
        $user = self::getCurrentUser();
        
        if (!$user) {
            return false;
        }

        return $user['role'] === $role;
    }
} 