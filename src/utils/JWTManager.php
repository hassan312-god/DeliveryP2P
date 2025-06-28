<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use DeliveryP2P\Utils\Logger;

/**
 * Gestionnaire JWT pour l'authentification
 * Sécurité et gestion des tokens
 */
class JWTManager
{
    private string $secret;
    private string $algorithm;
    private int $expiration;
    private int $refreshExpiration;
    private Logger $logger;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? '';
        $this->algorithm = 'HS256';
        $this->expiration = (int) ($_ENV['JWT_EXPIRATION'] ?? 3600); // 1 heure
        $this->refreshExpiration = (int) ($_ENV['JWT_REFRESH_EXPIRATION'] ?? 604800); // 7 jours
        
        if (empty($this->secret)) {
            throw new \Exception('JWT_SECRET manquant dans les variables d\'environnement');
        }

        $this->logger = new Logger();
    }

    /**
     * Génère un token JWT d'accès
     */
    public function generateAccessToken(array $payload): string
    {
        $now = time();
        
        $tokenPayload = [
            'iss' => 'deliveryp2p-api', // Issuer
            'aud' => 'deliveryp2p-users', // Audience
            'iat' => $now, // Issued at
            'nbf' => $now, // Not before
            'exp' => $now + $this->expiration, // Expiration
            'jti' => $this->generateJTI(), // JWT ID
            'type' => 'access'
        ];

        // Fusion avec les données utilisateur
        $tokenPayload = array_merge($tokenPayload, $payload);

        $this->logger->info('Access token generated', [
            'user_id' => $payload['user_id'] ?? 'unknown',
            'expires_at' => date('c', $tokenPayload['exp'])
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    /**
     * Génère un token JWT de rafraîchissement
     */
    public function generateRefreshToken(string $userId): string
    {
        $now = time();
        
        $tokenPayload = [
            'iss' => 'deliveryp2p-api',
            'aud' => 'deliveryp2p-users',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->refreshExpiration,
            'jti' => $this->generateJTI(),
            'type' => 'refresh',
            'user_id' => $userId
        ];

        $this->logger->info('Refresh token generated', [
            'user_id' => $userId,
            'expires_at' => date('c', $tokenPayload['exp'])
        ]);

        return JWT::encode($tokenPayload, $this->secret, $this->algorithm);
    }

    /**
     * Valide un token JWT
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            // Conversion en array
            $payload = json_decode(json_encode($decoded), true);
            
            // Vérifications supplémentaires
            if (!$this->validatePayload($payload)) {
                return null;
            }

            $this->logger->info('Token validated successfully', [
                'user_id' => $payload['user_id'] ?? 'unknown',
                'type' => $payload['type'] ?? 'unknown'
            ]);

            return $payload;

        } catch (\Firebase\JWT\ExpiredException $e) {
            $this->logger->warning('Token expired', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            $this->logger->warning('Invalid token signature', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Firebase\JWT\BeforeValidException $e) {
            $this->logger->warning('Token not yet valid', [
                'error' => $e->getMessage()
            ]);
            return null;
        } catch (\Exception $e) {
            $this->logger->error('Token validation error', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Rafraîchit un token d'accès avec un token de rafraîchissement
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $payload = $this->validateToken($refreshToken);
        
        if (!$payload || ($payload['type'] ?? '') !== 'refresh') {
            return null;
        }

        // Vérifier si le token de rafraîchissement n'est pas révoqué
        if ($this->isTokenRevoked($payload['jti'])) {
            $this->logger->warning('Refresh token revoked', [
                'jti' => $payload['jti']
            ]);
            return null;
        }

        // Générer un nouveau token d'accès
        $newAccessToken = $this->generateAccessToken([
            'user_id' => $payload['user_id'],
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? 'user',
            'permissions' => $payload['permissions'] ?? []
        ]);

        // Optionnellement, révoquer l'ancien token de rafraîchissement
        $this->revokeToken($payload['jti']);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $this->generateRefreshToken($payload['user_id']),
            'expires_in' => $this->expiration
        ];
    }

    /**
     * Valide le payload du token
     */
    private function validatePayload(array $payload): bool
    {
        $requiredFields = ['iss', 'aud', 'iat', 'exp', 'jti', 'type'];
        
        foreach ($requiredFields as $field) {
            if (!isset($payload[$field])) {
                $this->logger->warning('Missing required field in token', [
                    'field' => $field
                ]);
                return false;
            }
        }

        // Vérifier l'émetteur
        if ($payload['iss'] !== 'deliveryp2p-api') {
            $this->logger->warning('Invalid token issuer', [
                'issuer' => $payload['iss']
            ]);
            return false;
        }

        // Vérifier l'audience
        if ($payload['aud'] !== 'deliveryp2p-users') {
            $this->logger->warning('Invalid token audience', [
                'audience' => $payload['aud']
            ]);
            return false;
        }

        // Vérifier le type
        if (!in_array($payload['type'], ['access', 'refresh'])) {
            $this->logger->warning('Invalid token type', [
                'type' => $payload['type']
            ]);
            return false;
        }

        return true;
    }

    /**
     * Génère un JWT ID unique
     */
    private function generateJTI(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Vérifie si un token est révoqué
     */
    private function isTokenRevoked(string $jti): bool
    {
        // Implémentation avec cache ou base de données
        $cache = new Cache();
        return $cache->get("revoked_token:{$jti}") !== null;
    }

    /**
     * Révoque un token
     */
    private function revokeToken(string $jti): void
    {
        $cache = new Cache();
        $cache->set("revoked_token:{$jti}", true, $this->refreshExpiration);
        
        $this->logger->info('Token revoked', [
            'jti' => $jti
        ]);
    }

    /**
     * Extrait les informations utilisateur d'un token
     */
    public function extractUserInfo(string $token): ?array
    {
        $payload = $this->validateToken($token);
        
        if (!$payload) {
            return null;
        }

        return [
            'user_id' => $payload['user_id'] ?? null,
            'email' => $payload['email'] ?? null,
            'role' => $payload['role'] ?? 'user',
            'permissions' => $payload['permissions'] ?? []
        ];
    }

    /**
     * Décode un token sans validation (pour debug uniquement)
     */
    public function decodeToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            return json_decode(json_encode($decoded), true);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère la configuration JWT
     */
    public function getConfig(): array
    {
        return [
            'algorithm' => $this->algorithm,
            'expiration' => $this->expiration,
            'refresh_expiration' => $this->refreshExpiration,
            'has_secret' => !empty($this->secret)
        ];
    }
} 