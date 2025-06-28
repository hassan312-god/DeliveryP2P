<?php

/**
 * Fonctions utilitaires pour l'application
 */

if (!function_exists('asset')) {
    /**
     * Générer l'URL d'un asset
     */
    function asset($path)
    {
        return '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    /**
     * Générer une URL
     */
    function url($path = '')
    {
        $baseUrl = $_ENV['APP_URL'] ?? 'http://localhost:8000';
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    /**
     * Rediriger vers une URL
     */
    function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('old')) {
    /**
     * Récupérer une ancienne valeur de formulaire
     */
    function old($key, $default = '')
    {
        return $_SESSION['old'][$key] ?? $default;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Générer un token CSRF
     */
    function csrf_token()
    {
        return \App\Core\Middleware\CSRFMiddleware::generateToken();
    }
}

if (!function_exists('auth')) {
    /**
     * Vérifier si l'utilisateur est connecté
     */
    function auth()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('user')) {
    /**
     * Récupérer l'utilisateur connecté
     */
    function user()
    {
        if (!auth()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'first_name' => $_SESSION['user_first_name'],
            'last_name' => $_SESSION['user_last_name']
        ];
    }
}

if (!function_exists('is_admin')) {
    /**
     * Vérifier si l'utilisateur est admin
     */
    function is_admin()
    {
        return auth() && $_SESSION['user_role'] === 'admin';
    }
}

if (!function_exists('is_courier')) {
    /**
     * Vérifier si l'utilisateur est livreur
     */
    function is_courier()
    {
        return auth() && $_SESSION['user_role'] === 'courier';
    }
}

if (!function_exists('is_expeditor')) {
    /**
     * Vérifier si l'utilisateur est expéditeur
     */
    function is_expeditor()
    {
        return auth() && $_SESSION['user_role'] === 'expeditor';
    }
}

if (!function_exists('flash')) {
    /**
     * Définir un message flash
     */
    function flash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('get_flash')) {
    /**
     * Récupérer un message flash
     */
    function get_flash($key)
    {
        $message = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $message;
    }
}

if (!function_exists('has_flash')) {
    /**
     * Vérifier s'il y a un message flash
     */
    function has_flash($key)
    {
        return isset($_SESSION['flash'][$key]);
    }
} 