<?php
/**
 * Configuration principale de LivraisonP2P
 * Variables d'environnement et configuration globale
 */

declare(strict_types=1);

// Chargement des variables d'environnement depuis .env si disponible
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

// Configuration de base
define('APP_NAME', $_ENV['APP_NAME'] ?? 'LivraisonP2P');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? 'false', FILTER_VALIDATE_BOOLEAN));
define('APP_URL', $_ENV['APP_URL'] ?? 'https://deliveryp2p-api.onrender.com');

// Configuration Supabase
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? '');
define('SUPABASE_ANON_KEY', $_ENV['SUPABASE_ANON_KEY'] ?? '');
define('SUPABASE_SERVICE_ROLE_KEY', $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? '');

// Configuration JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '');
define('JWT_EXPIRATION', (int) ($_ENV['JWT_EXPIRATION'] ?? 3600));
define('JWT_REFRESH_EXPIRATION', (int) ($_ENV['JWT_REFRESH_EXPIRATION'] ?? 604800));

// Configuration de chiffrement
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? '');
define('QR_ENCRYPTION_KEY', $_ENV['QR_ENCRYPTION_KEY'] ?? $_ENV['ENCRYPTION_KEY'] ?? '');
define('QR_CODE_SECRET', $_ENV['QR_CODE_SECRET'] ?? '');

// Configuration de sécurité
define('PASSWORD_SALT', $_ENV['PASSWORD_SALT'] ?? '');
define('CORS_ALLOWED_ORIGINS', $_ENV['CORS_ALLOWED_ORIGINS'] ?? '');

// Configuration des logs
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
define('LOG_PATH', $_ENV['LOG_PATH'] ?? __DIR__ . '/storage/logs');

// Configuration du cache
define('CACHE_ENABLED', filter_var($_ENV['CACHE_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('CACHE_TTL', (int) ($_ENV['CACHE_TTL'] ?? 3600));
define('CACHE_PATH', $_ENV['CACHE_PATH'] ?? __DIR__ . '/storage/cache');

// Configuration du rate limiting
define('RATE_LIMIT_REQUESTS', (int) ($_ENV['RATE_LIMIT_REQUESTS'] ?? 100));
define('RATE_LIMIT_WINDOW', (int) ($_ENV['RATE_LIMIT_WINDOW'] ?? 3600));
define('RATE_LIMIT_API_REQUESTS', (int) ($_ENV['RATE_LIMIT_API_REQUESTS'] ?? 1000));
define('RATE_LIMIT_AUTH_REQUESTS', (int) ($_ENV['RATE_LIMIT_AUTH_REQUESTS'] ?? 500));

// Configuration des fichiers
define('UPLOAD_MAX_SIZE', (int) ($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760)); // 10MB
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? __DIR__ . '/storage/uploads');

// Configuration des QR codes
define('QR_CODE_SIZE', (int) ($_ENV['QR_CODE_SIZE'] ?? 300));
define('QR_CODE_MARGIN', (int) ($_ENV['QR_CODE_MARGIN'] ?? 10));
define('QR_CODE_ERROR_CORRECTION', $_ENV['QR_CODE_ERROR_CORRECTION'] ?? 'M');

// Configuration des livraisons
define('DELIVERY_RADIUS_KM', (float) ($_ENV['DELIVERY_RADIUS_KM'] ?? 0.5));
define('DELIVERY_TIMEOUT_MINUTES', (int) ($_ENV['DELIVERY_TIMEOUT_MINUTES'] ?? 30));
define('MAX_DELIVERY_WEIGHT_KG', (float) ($_ENV['MAX_DELIVERY_WEIGHT_KG'] ?? 25));

// Configuration des commissions
define('PLATFORM_COMMISSION_PERCENT', (float) ($_ENV['PLATFORM_COMMISSION_PERCENT'] ?? 15));
define('MINIMUM_DELIVERY_FEE', (float) ($_ENV['MINIMUM_DELIVERY_FEE'] ?? 5));

// Configuration des notifications
define('PUSH_NOTIFICATIONS_ENABLED', filter_var($_ENV['PUSH_NOTIFICATIONS_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('EMAIL_NOTIFICATIONS_ENABLED', filter_var($_ENV['EMAIL_NOTIFICATIONS_ENABLED'] ?? 'true', FILTER_VALIDATE_BOOLEAN));
define('SMS_NOTIFICATIONS_ENABLED', filter_var($_ENV['SMS_NOTIFICATIONS_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOLEAN));

// Configuration SMTP
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', (int) ($_ENV['SMTP_PORT'] ?? 587));
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@livraisonp2p.com');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? 'LivraisonP2P');

// Configuration des paiements (Stripe)
define('STRIPE_PUBLIC_KEY', $_ENV['STRIPE_PUBLIC_KEY'] ?? '');
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

// Configuration des notifications push (Firebase)
define('FIREBASE_SERVER_KEY', $_ENV['FIREBASE_SERVER_KEY'] ?? '');

// Configuration SendGrid
define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY'] ?? '');

// Configuration Twilio
define('TWILIO_ACCOUNT_SID', $_ENV['TWILIO_ACCOUNT_SID'] ?? '');
define('TWILIO_AUTH_TOKEN', $_ENV['TWILIO_AUTH_TOKEN'] ?? '');

// Configuration de géolocalisation
define('GOOGLE_MAPS_API_KEY', $_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
define('MAPBOX_ACCESS_TOKEN', $_ENV['MAPBOX_ACCESS_TOKEN'] ?? '');

// Configuration des timezones
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'Europe/Paris');
define('LOCALE', $_ENV['LOCALE'] ?? 'fr_FR.UTF-8');

// Configuration de la mémoire
define('MEMORY_LIMIT', $_ENV['MEMORY_LIMIT'] ?? '256M');
define('MAX_EXECUTION_TIME', (int) ($_ENV['MAX_EXECUTION_TIME'] ?? 30));

// Configuration PHP
define('PHP_VERSION', $_ENV['PHP_VERSION'] ?? '8.2');
define('COMPOSER_MEMORY_LIMIT', $_ENV['COMPOSER_MEMORY_LIMIT'] ?? '-1');

// Configuration de l'environnement
if (APP_ENV === 'production') {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Configuration des timezones
date_default_timezone_set(TIMEZONE);
setlocale(LC_ALL, LOCALE);

// Configuration de la mémoire
ini_set('memory_limit', MEMORY_LIMIT);
ini_set('max_execution_time', (string) MAX_EXECUTION_TIME);

// Création des répertoires de stockage si nécessaire
$storageDirs = [
    LOG_PATH,
    CACHE_PATH,
    UPLOAD_PATH,
    __DIR__ . '/storage/qr_codes'
];

foreach ($storageDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Fonction utilitaire pour vérifier la configuration
function checkRequiredConfig(): array
{
    $required = [
        'SUPABASE_URL' => SUPABASE_URL,
        'SUPABASE_ANON_KEY' => SUPABASE_ANON_KEY,
        'JWT_SECRET' => JWT_SECRET,
        'ENCRYPTION_KEY' => ENCRYPTION_KEY,
        'QR_CODE_SECRET' => QR_CODE_SECRET
    ];

    $missing = [];
    foreach ($required as $key => $value) {
        if (empty($value)) {
            $missing[] = $key;
        }
    }

    return [
        'valid' => empty($missing),
        'missing' => $missing
    ];
}

// Vérification de la configuration en mode production
if (APP_ENV === 'production') {
    $configCheck = checkRequiredConfig();
    if (!$configCheck['valid']) {
        error_log('Configuration manquante: ' . implode(', ', $configCheck['missing']));
        if (APP_DEBUG) {
            throw new Exception('Configuration manquante: ' . implode(', ', $configCheck['missing']));
        }
    }
}

// Fonction utilitaire pour obtenir la configuration
function config(string $key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Fonction utilitaire pour vérifier l'environnement
function isProduction(): bool {
    return APP_ENV === 'production';
}

function isDevelopment(): bool {
    return APP_ENV === 'development';
}

function isTesting(): bool {
    return APP_ENV === 'testing';
} 