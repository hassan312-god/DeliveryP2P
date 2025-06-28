<?php

namespace App\Core\Middleware;

class CSRFMiddleware
{
    public function handle($request, $next)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            $sessionToken = $_SESSION['csrf_token'] ?? null;
            
            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                http_response_code(403);
                echo json_encode(['error' => 'CSRF token validation failed']);
                exit;
            }
        }
        
        return $next($request);
    }
    
    public static function generateToken()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
} 