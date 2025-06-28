<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Middleware;

use DeliveryP2P\Utils\Logger;

/**
 * Middleware CORS pour gérer les requêtes cross-origin
 * Sécurité et compatibilité multi-origines
 */
class CorsMiddleware implements MiddlewareInterface
{
    private Logger $logger;
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;

    public function __construct()
    {
        $this->logger = new Logger();
        
        // Configuration CORS depuis les variables d'environnement
        $this->allowedOrigins = $this->parseAllowedOrigins();
        $this->allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];
        $this->allowedHeaders = [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-API-Key',
            'Accept',
            'Origin'
        ];
    }

    public function handle(string $method, string $uri): ?array
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        // Gestion des requêtes preflight OPTIONS
        if ($requestMethod === 'OPTIONS') {
            $this->setCorsHeaders($origin);
            return [
                'success' => true,
                'message' => 'Preflight request handled',
                'code' => 200
            ];
        }

        // Vérification de l'origine pour les requêtes non-OPTIONS
        if ($origin && !$this->isOriginAllowed($origin)) {
            $this->logger->warning('CORS: Origin not allowed', [
                'origin' => $origin,
                'uri' => $uri,
                'method' => $method
            ]);

            return [
                'success' => false,
                'error' => 'Origin not allowed',
                'code' => 403
            ];
        }

        // Définition des headers CORS pour les requêtes normales
        $this->setCorsHeaders($origin);

        return null; // Continuer vers le prochain middleware
    }

    /**
     * Définit les headers CORS appropriés
     */
    private function setCorsHeaders(?string $origin): void
    {
        if ($origin && $this->isOriginAllowed($origin)) {
            header("Access-Control-Allow-Origin: {$origin}");
        } else {
            header('Access-Control-Allow-Origin: *');
        }

        header('Access-Control-Allow-Methods: ' . implode(', ', $this->allowedMethods));
        header('Access-Control-Allow-Headers: ' . implode(', ', $this->allowedHeaders));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 24 heures
    }

    /**
     * Vérifie si l'origine est autorisée
     */
    private function isOriginAllowed(string $origin): bool
    {
        // En développement, autoriser toutes les origines
        if (defined('APP_ENV') && APP_ENV === 'development') {
            return true;
        }

        // En production, vérifier contre la liste autorisée
        foreach ($this->allowedOrigins as $allowedOrigin) {
            if ($this->matchOrigin($origin, $allowedOrigin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Compare une origine avec un pattern autorisé
     */
    private function matchOrigin(string $origin, string $pattern): bool
    {
        // Correspondance exacte
        if ($origin === $pattern) {
            return true;
        }

        // Pattern avec wildcard
        if (strpos($pattern, '*') !== false) {
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match("#^{$pattern}$#", $origin);
        }

        return false;
    }

    /**
     * Parse les origines autorisées depuis les variables d'environnement
     */
    private function parseAllowedOrigins(): array
    {
        $origins = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        
        if (empty($origins)) {
            // Origines par défaut
            return [
                'https://deliveryp2p-frontend.onrender.com',
                'https://livraisonp2p.fr',
                'https://www.livraisonp2p.fr'
            ];
        }

        return array_map('trim', explode(',', $origins));
    }
} 