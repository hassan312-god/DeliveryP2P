<?php

namespace App\Core\Middleware;

class AdminMiddleware
{
    public function handle($request, $next)
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: /login');
            exit;
        }
        
        return $next($request);
    }
} 