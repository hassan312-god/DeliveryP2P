<?php
/**
 * Configuration de l'application LivraisonP2P
 * Fichier de configuration centralisé
 */

// Configuration de la base de données Supabase
define('SUPABASE_URL', 'https://syamapjohtlbjlyhlhsi.supabase.co');
define('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M');
define('SUPABASE_SERVICE_KEY', ''); // Clé service pour les opérations admin

// Configuration de l'application
define('APP_NAME', 'LivraisonP2P');
define('APP_VERSION', '1.1.0');
define('APP_ENV', 'development'); // development, staging, production

// Configuration des emails
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'support@livraisonp2p.com');
define('SMTP_PASSWORD', '');
define('SMTP_ENCRYPTION', 'tls');

// Configuration des fichiers
define('UPLOAD_DIR', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// Configuration de sécurité
define('JWT_SECRET', 'your-secret-key-here');
define('PASSWORD_SALT', 'livraisonp2p-salt-2024');
define('SESSION_TIMEOUT', 24 * 60 * 60); // 24 heures

// Configuration des prix (XOF)
define('BASE_PRICE_PER_KM', 100);
define('MINIMUM_PRICE', 500);
define('URGENT_MULTIPLIER', 1.5);
define('NIGHT_MULTIPLIER', 1.2);
define('WEEKEND_MULTIPLIER', 1.1);

// Configuration des notifications
define('PUSH_PUBLIC_KEY', '');
define('PUSH_PRIVATE_KEY', '');

// Configuration des logs
define('LOG_DIR', 'logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configuration des timeouts
define('API_TIMEOUT', 30);
define('CURL_TIMEOUT', 60);

// Configuration des zones de couverture (Dakar)
$COVERAGE_ZONES = [
    'Dakar Centre' => ['lat' => 14.7167, 'lng' => -17.4677],
    'Plateau' => ['lat' => 14.7247, 'lng' => -17.4441],
    'Médina' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Fann' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Almadies' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Yoff' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Ouakam' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Mermoz' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Sacré-Cœur' => ['lat' => 14.7167, 'lng' => -17.4500],
    'Point E' => ['lat' => 14.7167, 'lng' => -17.4500]
];

// Configuration des statuts de livraison
$DELIVERY_STATUSES = [
    'pending' => ['label' => 'En attente', 'color' => 'yellow'],
    'accepted' => ['label' => 'Acceptée', 'color' => 'blue'],
    'picked_up' => ['label' => 'Récupérée', 'color' => 'purple'],
    'in_transit' => ['label' => 'En cours', 'color' => 'orange'],
    'delivered' => ['label' => 'Livrée', 'color' => 'green'],
    'cancelled' => ['label' => 'Annulée', 'color' => 'red']
];

// Configuration des rôles
$USER_ROLES = [
    'client' => ['label' => 'Client', 'permissions' => ['create_delivery', 'view_own_deliveries', 'rate_courier', 'generate_qr_codes']],
    'livreur' => ['label' => 'Livreur', 'permissions' => ['accept_delivery', 'view_available_deliveries', 'update_delivery_status', 'generate_qr_codes']],
    'admin' => ['label' => 'Administrateur', 'permissions' => ['view_all_deliveries', 'manage_users', 'view_analytics', 'manage_qr_codes']]
];

// Configuration des méthodes de paiement
$PAYMENT_METHODS = [
    'mobile_money' => ['label' => 'Mobile Money', 'icon' => 'fas fa-mobile-alt'],
    'card' => ['label' => 'Carte bancaire', 'icon' => 'fas fa-credit-card'],
    'cash' => ['label' => 'Espèces', 'icon' => 'fas fa-money-bill-wave']
];

// Configuration des types de QR codes
$QR_CODE_TYPES = [
    'delivery' => ['label' => 'Livraison', 'icon' => 'fas fa-truck', 'color' => 'blue'],
    'user' => ['label' => 'Utilisateur', 'icon' => 'fas fa-user', 'color' => 'green'],
    'payment' => ['label' => 'Paiement', 'icon' => 'fas fa-credit-card', 'color' => 'purple'],
    'location' => ['label' => 'Localisation', 'icon' => 'fas fa-map-marker-alt', 'color' => 'orange'],
    'custom' => ['label' => 'Personnalisé', 'icon' => 'fas fa-edit', 'color' => 'gray']
];

// Fonction pour obtenir la configuration selon l'environnement
function getConfig($key, $default = null) {
    if (defined($key)) {
        return constant($key);
    }
    return $default;
}

// Fonction pour vérifier si on est en mode développement
function isDevelopment() {
    return APP_ENV === 'development';
}

// Fonction pour vérifier si on est en mode production
function isProduction() {
    return APP_ENV === 'production';
}

// Fonction pour obtenir l'URL de base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}

// Fonction pour logger
function logMessage($level, $message, $context = []) {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $logFile = LOG_DIR . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
    $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

// Fonction pour nettoyer les entrées utilisateur
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Fonction pour valider un email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Fonction pour valider un numéro de téléphone sénégalais (optionnel)
function validatePhone($phone) {
    // Si le téléphone est vide ou null, c'est valide (optionnel)
    if (empty($phone) || $phone === null) {
        return true;
    }
    
    $pattern = '/^(\+221|221)?[0-9]{9}$/';
    return preg_match($pattern, $phone);
}

// Fonction pour générer un token sécurisé
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Fonction pour hasher un mot de passe
function hashPassword($password) {
    return password_hash($password . PASSWORD_SALT, PASSWORD_BCRYPT);
}

// Fonction pour vérifier un mot de passe
function verifyPassword($password, $hash) {
    return password_verify($password . PASSWORD_SALT, $hash);
}

// Fonction pour calculer la distance entre deux points
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // Rayon de la Terre en km
    
    $latDelta = deg2rad($lat2 - $lat1);
    $lonDelta = deg2rad($lon2 - $lon1);
    
    $a = sin($latDelta / 2) * sin($latDelta / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($lonDelta / 2) * sin($lonDelta / 2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    
    return $earthRadius * $c;
}

// Fonction pour calculer le prix d'une livraison
function calculateDeliveryPrice($distance, $priority = 'normal', $isUrgent = false, $isNight = false, $isWeekend = false) {
    $basePrice = max(BASE_PRICE_PER_KM * $distance, MINIMUM_PRICE);
    
    $multiplier = 1.0;
    
    if ($isUrgent) {
        $multiplier *= URGENT_MULTIPLIER;
    }
    
    if ($isNight) {
        $multiplier *= NIGHT_MULTIPLIER;
    }
    
    if ($isWeekend) {
        $multiplier *= WEEKEND_MULTIPLIER;
    }
    
    return round($basePrice * $multiplier);
}

// Fonction pour formater un montant en XOF
function formatCurrency($amount) {
    return number_format($amount, 0, ',', ' ') . ' XOF';
}

// Fonction pour formater une date
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Fonction pour obtenir l'heure actuelle au Sénégal
function getSenegalTime() {
    date_default_timezone_set('Africa/Dakar');
    return date('Y-m-d H:i:s');
}

// Fonction pour vérifier si c'est la nuit (entre 22h et 6h)
function isNightTime() {
    $hour = (int)date('H');
    return $hour >= 22 || $hour < 6;
}

// Fonction pour vérifier si c'est le weekend
function isWeekend() {
    $dayOfWeek = date('N');
    return $dayOfWeek >= 6; // Samedi = 6, Dimanche = 7
}

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Fonction pour envoyer une réponse d'erreur
function sendErrorResponse($message, $statusCode = 400) {
    sendJsonResponse(['error' => $message], $statusCode);
}

// Fonction pour envoyer une réponse de succès
function sendSuccessResponse($data = null, $message = 'Succès') {
    $response = ['success' => true, 'message' => $message];
    if ($data !== null) {
        $response['data'] = $data;
    }
    sendJsonResponse($response);
}

// Initialisation des headers CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gestion des requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration des erreurs selon l'environnement
if (isDevelopment()) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Configuration de la timezone
date_default_timezone_set('Africa/Dakar');

// Démarrage de la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log de démarrage
logMessage('INFO', 'Application démarrée', [
    'env' => APP_ENV,
    'version' => APP_VERSION,
    'url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
]);
?> 