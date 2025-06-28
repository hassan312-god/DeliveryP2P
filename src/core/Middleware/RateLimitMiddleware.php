<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Middleware;

use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Utils\Cache;

/**
 * Middleware de rate limiting
 * Protection contre les abus et DDoS
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    private Logger $logger;
    private Cache $cache;
    private int $maxRequests;
    private int $windowSeconds;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->cache = new Cache();
        
        // Configuration depuis les variables d'environnement
        $this->maxRequests = (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
        $this->windowSeconds = (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 3600);
    }

    public function handle(string $method, string $uri): ?array
    {
        try {
            $identifier = $this->getClientIdentifier();
            $key = $this->generateCacheKey($identifier, $method, $uri);

            // Vérification du rate limit
            $currentCount = $this->getCurrentRequestCount($key);
            
            if ($currentCount >= $this->maxRequests) {
                $this->logger->warning('Rate limit exceeded', [
                    'identifier' => $identifier,
                    'method' => $method,
                    'uri' => $uri,
                    'count' => $currentCount,
                    'limit' => $this->maxRequests
                ]);

                return [
                    'success' => false,
                    'error' => 'Rate limit exceeded. Please try again later.',
                    'code' => 429,
                    'headers' => [
                        'X-RateLimit-Limit' => $this->maxRequests,
                        'X-RateLimit-Remaining' => 0,
                        'X-RateLimit-Reset' => $this->getResetTime($key),
                        'Retry-After' => $this->windowSeconds
                    ]
                ];
            }

            // Incrémentation du compteur
            $this->incrementRequestCount($key);

            // Ajout des headers de rate limiting
            $this->setRateLimitHeaders($key, $currentCount + 1);

            return null; // Continuer vers le prochain middleware

        } catch (\Exception $e) {
            $this->logger->error('Rate limiting error', [
                'error' => $e->getMessage(),
                'method' => $method,
                'uri' => $uri
            ]);

            // En cas d'erreur, on laisse passer la requête
            return null;
        }
    }

    /**
     * Génère un identifiant unique pour le client
     */
    private function getClientIdentifier(): string
    {
        // Priorité aux API keys
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKey)) {
            return 'api_key:' . hash('sha256', $apiKey);
        }

        // Token JWT
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return 'jwt:' . hash('sha256', $matches[1]);
        }

        // IP address avec User-Agent pour plus de précision
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return 'ip:' . hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * Génère la clé de cache pour le rate limiting
     */
    private function generateCacheKey(string $identifier, string $method, string $uri): string
    {
        $window = floor(time() / $this->windowSeconds);
        return "rate_limit:{$identifier}:{$method}:{$window}";
    }

    /**
     * Récupère le nombre de requêtes actuelles
     */
    private function getCurrentRequestCount(string $key): int
    {
        $count = $this->cache->get($key);
        return $count ? (int) $count : 0;
    }

    /**
     * Incrémente le compteur de requêtes
     */
    private function incrementRequestCount(string $key): void
    {
        $currentCount = $this->getCurrentRequestCount($key);
        $this->cache->set($key, $currentCount + 1, $this->windowSeconds);
    }

    /**
     * Calcule le temps de reset du rate limit
     */
    private function getResetTime(string $key): int
    {
        $window = floor(time() / $this->windowSeconds);
        return ($window + 1) * $this->windowSeconds;
    }

    /**
     * Définit les headers de rate limiting
     */
    private function setRateLimitHeaders(string $key, int $currentCount): void
    {
        $remaining = max(0, $this->maxRequests - $currentCount);
        $resetTime = $this->getResetTime($key);

        header("X-RateLimit-Limit: {$this->maxRequests}");
        header("X-RateLimit-Remaining: {$remaining}");
        header("X-RateLimit-Reset: {$resetTime}");
    }

    /**
     * Vérifie si une route est exemptée du rate limiting
     */
    private function isExempted(string $method, string $uri): bool
    {
        // Routes publiques exemptées
        $exemptedRoutes = [
            'GET:/health',
            'GET:/api/health',
            'OPTIONS:/',
            'OPTIONS:/api'
        ];

        $routeKey = "{$method}:{$uri}";
        return in_array($routeKey, $exemptedRoutes);
    }

    /**
     * Applique des limites différentes selon le type de client
     */
    private function getClientLimit(string $identifier): int
    {
        // Limites spéciales pour les API keys
        if (strpos($identifier, 'api_key:') === 0) {
            return (int) ($_ENV['RATE_LIMIT_API_REQUESTS'] ?? 1000);
        }

        // Limites pour les utilisateurs authentifiés
        if (strpos($identifier, 'jwt:') === 0) {
            return (int) ($_ENV['RATE_LIMIT_AUTH_REQUESTS'] ?? 500);
        }

        // Limite par défaut pour les IPs
        return $this->maxRequests;
    }
} 