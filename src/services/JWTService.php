<?php

namespace DeliveryP2P\Services;

use DeliveryP2P\Utils\Logger;

/**
 * Service JWT pour LivraisonP2P
 * Gestion des tokens d'authentification
 */
class JWTService
{
    private string $secret;
    private int $expiration;
    private Logger $logger;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'default-secret-key-change-in-production';
        $this->expiration = (int)($_ENV['JWT_EXPIRATION'] ?? 3600); // 1 heure par défaut
        $this->logger = new Logger();
    }

    /**
     * Génération d'un token JWT
     */
    public function generateToken(array $payload): string
    {
        try {
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256'
            ];

            $payload['iat'] = time();
            $payload['exp'] = time() + $this->expiration;
            $payload['iss'] = 'livraisonp2p';

            $headerEncoded = $this->base64UrlEncode(json_encode($header));
            $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

            $signature = hash_hmac('sha256', 
                $headerEncoded . '.' . $payloadEncoded, 
                $this->secret, 
                true
            );
            $signatureEncoded = $this->base64UrlEncode($signature);

            $token = $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;

            $this->logger->info('Token JWT généré', [
                'user_id' => $payload['user_id'] ?? 'unknown',
                'expires_at' => date('c', $payload['exp'])
            ]);

            return $token;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du token JWT', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validation d'un token JWT
     */
    public function validateToken(string $token): array
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                throw new \Exception('Token JWT invalide: format incorrect');
            }

            [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

            // Vérification de la signature
            $signature = hash_hmac('sha256', 
                $headerEncoded . '.' . $payloadEncoded, 
                $this->secret, 
                true
            );
            $expectedSignature = $this->base64UrlEncode($signature);

            if (!hash_equals($signatureEncoded, $expectedSignature)) {
                throw new \Exception('Token JWT invalide: signature incorrecte');
            }

            // Décodage du payload
            $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);
            
            if (!$payload) {
                throw new \Exception('Token JWT invalide: payload corrompu');
            }

            // Vérification de l'expiration
            if (isset($payload['exp']) && $payload['exp'] < time()) {
                throw new \Exception('Token JWT expiré');
            }

            // Vérification de l'émetteur
            if (isset($payload['iss']) && $payload['iss'] !== 'livraisonp2p') {
                throw new \Exception('Token JWT invalide: émetteur incorrect');
            }

            $this->logger->info('Token JWT validé', [
                'user_id' => $payload['user_id'] ?? 'unknown'
            ]);

            return $payload;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation du token JWT', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Rafraîchissement d'un token JWT
     */
    public function refreshToken(string $token): string
    {
        try {
            $payload = $this->validateToken($token);
            
            // Suppression des champs de timing pour générer un nouveau token
            unset($payload['iat'], $payload['exp']);
            
            return $this->generateToken($payload);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du rafraîchissement du token JWT', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mise en liste noire d'un token
     */
    public function blacklistToken(string $token): bool
    {
        try {
            // Ici, vous pourriez stocker le token en liste noire dans la base de données
            // Pour le moment, on log juste l'action
            $this->logger->info('Token JWT mis en liste noire');
            return true;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la mise en liste noire du token', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Vérification si un token est en liste noire
     */
    public function isTokenBlacklisted(string $token): bool
    {
        try {
            // Ici, vous pourriez vérifier dans la base de données
            // Pour le moment, on retourne false
            return false;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la vérification de la liste noire', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extraction des informations du token sans validation
     */
    public function getTokenInfo(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode($this->base64UrlDecode($parts[1]), true);
            
            return $payload ?: null;

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Encodage Base64URL
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Décodage Base64URL
     */
    private function base64UrlDecode(string $data): string
    {
        $data = strtr($data, '-_', '+/');
        $data = str_pad($data, strlen($data) % 4, '=', STR_PAD_RIGHT);
        return base64_decode($data);
    }

    /**
     * Génération d'un token de rafraîchissement
     */
    public function generateRefreshToken(array $payload): string
    {
        try {
            $refreshPayload = $payload;
            $refreshPayload['type'] = 'refresh';
            $refreshPayload['exp'] = time() + (7 * 24 * 3600); // 7 jours

            return $this->generateToken($refreshPayload);

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la génération du token de rafraîchissement', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validation d'un token de rafraîchissement
     */
    public function validateRefreshToken(string $token): array
    {
        try {
            $payload = $this->validateToken($token);
            
            if (!isset($payload['type']) || $payload['type'] !== 'refresh') {
                throw new \Exception('Token de rafraîchissement invalide');
            }

            return $payload;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la validation du token de rafraîchissement', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
} 