<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Exceptions;

/**
 * Exception pour routes non trouvées
 */
class RouteNotFoundException extends ApiException
{
    public function __construct(string $message = 'Route not found', array $context = [])
    {
        parent::__construct($message, 404, $context);
    }
} 