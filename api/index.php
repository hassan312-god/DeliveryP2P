<?php
/**
 * API LivraisonP2P - Point d'entrée principal
 * Architecture hexagonale moderne avec PHP 8.2+
 * Système QR code sécurisé de nouvelle génération
 * 
 * @author LivraisonP2P Team
 * @version 2.0.0
 */

declare(strict_types=1);

// Configuration d'erreurs pour le développement
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Headers de sécurité avancés
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-Key');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');

// Gestion des requêtes OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Autoloader PSR-4
require_once __DIR__ . '/../vendor/autoload.php';

// Configuration de l'application
require_once __DIR__ . '/../config.php';

use DeliveryP2P\Core\Router;
use DeliveryP2P\Core\Middleware\CorsMiddleware;
use DeliveryP2P\Core\Middleware\AuthMiddleware;
use DeliveryP2P\Core\Middleware\RateLimitMiddleware;
use DeliveryP2P\Core\Exceptions\ApiException;
use DeliveryP2P\Utils\Logger;
use DeliveryP2P\Utils\Response;
use DeliveryP2P\Utils\Database;

try {
    // Initialisation du logger
    $logger = new Logger();
    $startTime = microtime(true);
    
    $logger->logRequest($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

    // Initialisation du routeur
    $router = new Router();

    // Middlewares globaux
    $router->addMiddleware(new CorsMiddleware());
    $router->addMiddleware(new RateLimitMiddleware());

    // Routes d'authentification
    $router->group('/auth', function($router) {
        $router->post('/register', 'AuthController@register');
        $router->post('/login', 'AuthController@login');
        $router->post('/refresh', 'AuthController@refresh');
        $router->post('/logout', 'AuthController@logout');
        $router->get('/profile', 'AuthController@profile');
        $router->put('/profile', 'AuthController@updateProfile');
        $router->post('/verify-email', 'AuthController@verifyEmail');
        $router->post('/forgot-password', 'AuthController@forgotPassword');
        $router->post('/reset-password', 'AuthController@resetPassword');
    });

    // Routes protégées par authentification
    $router->group('/deliveries', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->get('/', 'DeliveryController@index');
        $router->post('/', 'DeliveryController@store');
        $router->get('/{id}', 'DeliveryController@show');
        $router->put('/{id}', 'DeliveryController@update');
        $router->delete('/{id}', 'DeliveryController@destroy');
        $router->get('/search', 'DeliveryController@search');
        $router->post('/{id}/assign', 'DeliveryController@assign');
        $router->post('/{id}/accept', 'DeliveryController@accept');
        $router->post('/{id}/reject', 'DeliveryController@reject');
        $router->post('/{id}/pickup', 'DeliveryController@pickup');
        $router->post('/{id}/deliver', 'DeliveryController@deliver');
    });

    // Routes QR Code sécurisées
    $router->group('/qr', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->post('/generate', 'QRController@generate');
        $router->post('/validate', 'QRController@validate');
        $router->get('/{code}/info', 'QRController@info');
        $router->post('/{code}/scan', 'QRController@scan');
        $router->get('/{code}/history', 'QRController@history');
        $router->delete('/{code}/revoke', 'QRController@revoke');
    });

    // Routes de tracking temps réel
    $router->group('/tracking', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->get('/{delivery_id}', 'TrackingController@show');
        $router->post('/update', 'TrackingController@update');
        $router->get('/history', 'TrackingController@history');
        $router->post('/milestone', 'TrackingController@milestone');
    });

    // Routes de notifications
    $router->group('/notifications', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->get('/', 'NotificationController@index');
        $router->post('/subscribe', 'NotificationController@subscribe');
        $router->delete('/unsubscribe', 'NotificationController@unsubscribe');
        $router->put('/settings', 'NotificationController@updateSettings');
    });

    // Routes de paiements
    $router->group('/payments', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->post('/create-intent', 'PaymentController@createIntent');
        $router->post('/confirm', 'PaymentController@confirm');
        $router->get('/history', 'PaymentController@history');
        $router->post('/refund', 'PaymentController@refund');
    });

    // Routes d'administration
    $router->group('/admin', function($router) {
        $router->addMiddleware(new AuthMiddleware());
        
        $router->get('/dashboard', 'AdminController@dashboard');
        $router->get('/users', 'AdminController@users');
        $router->get('/deliveries', 'AdminController@deliveries');
        $router->get('/analytics', 'AdminController@analytics');
        $router->post('/users/{id}/verify', 'AdminController@verifyUser');
        $router->post('/users/{id}/suspend', 'AdminController@suspendUser');
    });

    // Route de santé de l'API avec tests de connexion
    $router->get('/health', function() {
        $db = Database::getInstance();
        $dbTest = $db->testConnection();
        
        $health = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => '2.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'php_version' => PHP_VERSION,
            'database' => [
                'status' => $dbTest['success'] ? 'connected' : 'disconnected',
                'message' => $dbTest['message'],
                'http_code' => $dbTest['http_code']
            ],
            'services' => [
                'qr_code' => 'available',
                'authentication' => 'available',
                'tracking' => 'available',
                'notifications' => 'available'
            ],
            'uptime' => [
                'start_time' => $_SERVER['REQUEST_TIME'] ?? time(),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]
        ];

        $statusCode = $dbTest['success'] ? 200 : 503;
        http_response_code($statusCode);
        
        return Response::success($health);
    });

    // Route de test de connexion Supabase spécifique Render
    $router->get('/test-connection', function() {
        try {
            $db = Database::getInstance();
            $config = $db->getConfig();
            $test = $db->testConnection();
            
            return Response::success([
                'supabase_config' => [
                    'url_configured' => !empty($config['url']),
                    'anon_key_configured' => $config['has_anon_key'],
                    'service_key_configured' => $config['has_service_key']
                ],
                'connection_test' => $test,
                'render_environment' => [
                    'app_env' => $_ENV['APP_ENV'] ?? 'unknown',
                    'supabase_url' => $_ENV['SUPABASE_URL'] ?? 'not_set',
                    'has_anon_key' => !empty($_ENV['SUPABASE_ANON_KEY']),
                    'has_service_key' => !empty($_ENV['SUPABASE_SERVICE_ROLE_KEY'])
                ]
            ]);
        } catch (\Exception $e) {
            return Response::error('Connection test failed: ' . $e->getMessage(), 500);
        }
    });

    // Route 404
    $router->setNotFoundHandler(function() {
        return Response::error('Endpoint not found', 404);
    });

    // Exécution du routeur
    $response = $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);

    // Calcul du temps d'exécution
    $duration = microtime(true) - $startTime;
    
    // Log de la réponse
    $logger->logResponse(
        $_SERVER['REQUEST_METHOD'], 
        $_SERVER['REQUEST_URI'], 
        $response['code'] ?? 200, 
        $duration
    );

    // Envoi de la réponse
    http_response_code($response['code'] ?? 200);
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (ApiException $e) {
    // Gestion des exceptions API
    $logger->logException($e);
    
    http_response_code($e->getCode());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode(),
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // Gestion des exceptions générales
    $logger->logException($e);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 500,
        'timestamp' => date('c')
    ], JSON_UNESCAPED_UNICODE);
} 