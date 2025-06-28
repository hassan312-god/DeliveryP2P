<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Middleware;

/**
 * Interface pour les middlewares
 * Architecture hexagonale - Port
 */
interface MiddlewareInterface
{
    /**
     * Traite la requête et retourne une réponse ou null pour continuer
     */
    public function handle(string $method, string $uri): ?array;
} 