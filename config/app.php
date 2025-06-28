<?php
/**
 * Configuration principale de l'application LivraisonP2P
 * 
 * @author LivraisonP2P Team
 * @version 1.0.0
 */

declare(strict_types=1);

// Chargement des variables d'environnement
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Configuration de l'application
define('APP_NAME', $_ENV['APP_NAME'] ?? 'LivraisonP2P');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'production');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? 'false');
define('APP_URL', $_ENV['APP_URL'] ?? 'https://livraisonp2p.com');
define('APP_VERSION', '1.0.0');

// Configuration Supabase
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? '');
define('SUPABASE_ANON_KEY', $_ENV['SUPABASE_ANON_KEY'] ?? '');
define('SUPABASE_SERVICE_KEY', $_ENV['SUPABASE_SERVICE_KEY'] ?? '');

// Configuration JWT
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? '');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION', 3600); // 1 heure
define('JWT_REFRESH_EXPIRATION', 604800); // 7 jours

// Configuration de sécurité
define('PASSWORD_SALT', $_ENV['PASSWORD_SALT'] ?? '');
define('ENCRYPTION_KEY', $_ENV['ENCRYPTION_KEY'] ?? '');
define('QR_CODE_SECRET', $_ENV['QR_CODE_SECRET'] ?? '');

// Configuration SMTP
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_FROM_EMAIL', $_ENV['SMTP_FROM_EMAIL'] ?? '');
define('SMTP_FROM_NAME', $_ENV['SMTP_FROM_NAME'] ?? APP_NAME);

// Configuration des paiements
define('STRIPE_SECRET_KEY', $_ENV['STRIPE_SECRET_KEY'] ?? '');
define('STRIPE_PUBLISHABLE_KEY', $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '');
define('STRIPE_WEBHOOK_SECRET', $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '');

// Configuration des notifications
define('FIREBASE_SERVER_KEY', $_ENV['FIREBASE_SERVER_KEY'] ?? '');
define('SENDGRID_API_KEY', $_ENV['SENDGRID_API_KEY'] ?? '');
define('TWILIO_ACCOUNT_SID', $_ENV['TWILIO_ACCOUNT_SID'] ?? '');
define('TWILIO_AUTH_TOKEN', $_ENV['TWILIO_AUTH_TOKEN'] ?? '');

// Configuration de géolocalisation
define('GOOGLE_MAPS_API_KEY', $_ENV['GOOGLE_MAPS_API_KEY'] ?? '');
define('MAPBOX_ACCESS_TOKEN', $_ENV['MAPBOX_ACCESS_TOKEN'] ?? '');

// Configuration des logs
define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? 'info');
define('LOG_PATH', __DIR__ . '/../storage/logs');

// Configuration du cache
define('CACHE_ENABLED', $_ENV['CACHE_ENABLED'] ?? 'true');
define('CACHE_TTL', $_ENV['CACHE_TTL'] ?? 3600);

// Configuration du rate limiting
define('RATE_LIMIT_REQUESTS', $_ENV['RATE_LIMIT_REQUESTS'] ?? 100);
define('RATE_LIMIT_WINDOW', $_ENV['RATE_LIMIT_WINDOW'] ?? 3600);

// Configuration des fichiers
define('UPLOAD_MAX_SIZE', $_ENV['UPLOAD_MAX_SIZE'] ?? 10485760); // 10MB
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'text/plain'
]);

// Configuration des QR codes
define('QR_CODE_SIZE', $_ENV['QR_CODE_SIZE'] ?? 300);
define('QR_CODE_MARGIN', $_ENV['QR_CODE_MARGIN'] ?? 10);
define('QR_CODE_ERROR_CORRECTION', $_ENV['QR_CODE_ERROR_CORRECTION'] ?? 'M');

// Configuration des livraisons
define('DELIVERY_RADIUS_KM', $_ENV['DELIVERY_RADIUS_KM'] ?? 50);
define('DELIVERY_TIMEOUT_MINUTES', $_ENV['DELIVERY_TIMEOUT_MINUTES'] ?? 30);
define('MAX_DELIVERY_WEIGHT_KG', $_ENV['MAX_DELIVERY_WEIGHT_KG'] ?? 25);

// Configuration des commissions
define('PLATFORM_COMMISSION_PERCENT', $_ENV['PLATFORM_COMMISSION_PERCENT'] ?? 15);
define('MINIMUM_DELIVERY_FEE', $_ENV['MINIMUM_DELIVERY_FEE'] ?? 5);

// Configuration des notifications
define('PUSH_NOTIFICATIONS_ENABLED', $_ENV['PUSH_NOTIFICATIONS_ENABLED'] ?? 'true');
define('EMAIL_NOTIFICATIONS_ENABLED', $_ENV['EMAIL_NOTIFICATIONS_ENABLED'] ?? 'true');
define('SMS_NOTIFICATIONS_ENABLED', $_ENV['SMS_NOTIFICATIONS_ENABLED'] ?? 'false');

// Configuration de la base de données
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_PORT', $_ENV['DB_PORT'] ?? 5432);
define('DB_NAME', $_ENV['DB_NAME'] ?? 'postgres');
define('DB_USER', $_ENV['DB_USER'] ?? 'postgres');
define('DB_PASSWORD', $_ENV['DB_PASSWORD'] ?? '');

// Configuration des timezones
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'Europe/Paris');

// Configuration des locales
setlocale(LC_ALL, $_ENV['LOCALE'] ?? 'fr_FR.UTF-8');

// Configuration des sessions
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', APP_ENV === 'production' ? '1' : '0');
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', '3600');

// Configuration de la mémoire
ini_set('memory_limit', $_ENV['MEMORY_LIMIT'] ?? '256M');
ini_set('max_execution_time', $_ENV['MAX_EXECUTION_TIME'] ?? '30');

// Configuration des erreurs
if (APP_DEBUG === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Configuration des logs d'erreurs
ini_set('log_errors', '1');
ini_set('error_log', LOG_PATH . '/php_errors.log');

// Vérification des configurations critiques
$requiredConfigs = [
    'SUPABASE_URL',
    'SUPABASE_ANON_KEY',
    'SUPABASE_SERVICE_KEY',
    'JWT_SECRET',
    'PASSWORD_SALT',
    'ENCRYPTION_KEY',
    'QR_CODE_SECRET'
];

foreach ($requiredConfigs as $config) {
    if (empty(constant($config))) {
        throw new Exception("Configuration manquante: {$config}");
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