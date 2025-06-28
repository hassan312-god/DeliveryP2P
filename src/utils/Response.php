<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

/**
 * Classe Response pour standardiser les réponses API
 * Format cohérent pour toutes les réponses
 */
class Response
{
    /**
     * Génère une réponse de succès
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'code' => $code,
            'timestamp' => date('c')
        ];
    }

    /**
     * Génère une réponse d'erreur
     */
    public static function error(string $message, int $code = 400, $data = null): array
    {
        return [
            'success' => false,
            'error' => $message,
            'data' => $data,
            'code' => $code,
            'timestamp' => date('c')
        ];
    }

    /**
     * Génère une réponse de validation
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): array
    {
        return [
            'success' => false,
            'error' => $message,
            'validation_errors' => $errors,
            'code' => 422,
            'timestamp' => date('c')
        ];
    }

    /**
     * Génère une réponse paginée
     */
    public static function paginated(array $data, int $page, int $perPage, int $total): array
    {
        $totalPages = ceil($total / $perPage);
        
        return [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'code' => 200,
            'timestamp' => date('c')
        ];
    }

    /**
     * Génère une réponse de liste
     */
    public static function list(array $items, int $total = null): array
    {
        $response = [
            'success' => true,
            'data' => $items,
            'code' => 200,
            'timestamp' => date('c')
        ];

        if ($total !== null) {
            $response['total'] = $total;
        }

        return $response;
    }

    /**
     * Génère une réponse de ressource créée
     */
    public static function created($data = null, string $message = 'Resource created successfully'): array
    {
        return self::success($data, $message, 201);
    }

    /**
     * Génère une réponse de ressource mise à jour
     */
    public static function updated($data = null, string $message = 'Resource updated successfully'): array
    {
        return self::success($data, $message, 200);
    }

    /**
     * Génère une réponse de ressource supprimée
     */
    public static function deleted(string $message = 'Resource deleted successfully'): array
    {
        return self::success(null, $message, 200);
    }

    /**
     * Génère une réponse de ressource non trouvée
     */
    public static function notFound(string $message = 'Resource not found'): array
    {
        return self::error($message, 404);
    }

    /**
     * Génère une réponse d'accès interdit
     */
    public static function forbidden(string $message = 'Access forbidden'): array
    {
        return self::error($message, 403);
    }

    /**
     * Génère une réponse non autorisée
     */
    public static function unauthorized(string $message = 'Unauthorized'): array
    {
        return self::error($message, 401);
    }

    /**
     * Génère une réponse de conflit
     */
    public static function conflict(string $message = 'Resource conflict'): array
    {
        return self::error($message, 409);
    }

    /**
     * Génère une réponse de serveur indisponible
     */
    public static function serviceUnavailable(string $message = 'Service temporarily unavailable'): array
    {
        return self::error($message, 503);
    }
} 