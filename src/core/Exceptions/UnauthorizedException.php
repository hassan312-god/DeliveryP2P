<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Exceptions;

/**
 * Exception pour accès non autorisé
 */
class UnauthorizedException extends ApiException
{
    public function __construct(string $message = 'Unauthorized', array $context = [])
    {
        parent::__construct($message, 401, $context);
    }
} 