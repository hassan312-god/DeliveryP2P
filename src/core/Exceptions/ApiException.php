<?php

declare(strict_types=1);

namespace DeliveryP2P\Core\Exceptions;

/**
 * Exception API pour la gestion d'erreurs standardisée
 */
class ApiException extends \Exception
{
    protected array $context;

    public function __construct(string $message = '', int $code = 0, array $context = [], \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Récupère le contexte de l'exception
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Exception pour ressource non trouvée
     */
    public static function notFound(string $message = 'Resource not found', array $context = []): self
    {
        return new self($message, 404, $context);
    }

    /**
     * Exception pour accès interdit
     */
    public static function forbidden(string $message = 'Access forbidden', array $context = []): self
    {
        return new self($message, 403, $context);
    }

    /**
     * Exception pour non autorisé
     */
    public static function unauthorized(string $message = 'Unauthorized', array $context = []): self
    {
        return new self($message, 401, $context);
    }

    /**
     * Exception pour validation échouée
     */
    public static function validationFailed(string $message = 'Validation failed', array $context = []): self
    {
        return new self($message, 422, $context);
    }

    /**
     * Exception pour conflit
     */
    public static function conflict(string $message = 'Resource conflict', array $context = []): self
    {
        return new self($message, 409, $context);
    }

    /**
     * Exception pour serveur indisponible
     */
    public static function serviceUnavailable(string $message = 'Service temporarily unavailable', array $context = []): self
    {
        return new self($message, 503, $context);
    }

    /**
     * Exception pour erreur de base de données
     */
    public static function databaseError(string $message = 'Database error', array $context = []): self
    {
        return new self($message, 500, $context);
    }
} 