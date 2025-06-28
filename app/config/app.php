<?php

return [
    'name' => 'LivraisonP2P',
    'version' => '1.0.0',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost:8000',
    'timezone' => 'Europe/Paris',
    'locale' => 'fr',
    
    'providers' => [
        // Services providers
    ],
    
    'middleware' => [
        'auth' => \App\Core\Middleware\AuthMiddleware::class,
        'admin' => \App\Core\Middleware\AdminMiddleware::class,
        'csrf' => \App\Core\Middleware\CSRFMiddleware::class,
    ],
    
    'session' => [
        'driver' => 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => storage_path('framework/sessions'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => 'livraisonp2p_session',
        'path' => '/',
        'domain' => null,
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    'mail' => [
        'default' => $_ENV['MAIL_MAILER'] ?? 'smtp',
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['SMTP_PORT'] ?? 587,
                'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
                'username' => $_ENV['SMTP_USERNAME'] ?? '',
                'password' => $_ENV['SMTP_PASSWORD'] ?? '',
                'timeout' => null,
                'local_domain' => null,
            ],
        ],
        'from' => [
            'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@livraisonp2p.com',
            'name' => $_ENV['MAIL_FROM_NAME'] ?? 'LivraisonP2P',
        ],
    ],
]; 