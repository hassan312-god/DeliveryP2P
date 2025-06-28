<?php
session_start();

// Configuration de base
define('BASE_PATH', __DIR__ . '/../');
define('VIEWS_PATH', __DIR__ . '/views/');

// Autoloader Composer
require_once BASE_PATH . 'vendor/autoload.php';

// Charger les helpers
require_once BASE_PATH . 'app/core/helpers.php';

// Charger les variables d'environnement
try {
    $dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
    $dotenv->load();
} catch (Exception $e) {
    // Si .env n'existe pas, utiliser les valeurs par défaut
    $_ENV['APP_ENV'] = 'development';
    $_ENV['APP_DEBUG'] = true;
    $_ENV['APP_URL'] = 'http://localhost:8000';
}

// Router simple
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = trim($path, '/');

// Si pas de chemin, rediriger vers la page d'accueil
if (empty($path)) {
    $path = 'home';
}

// Vérifier si l'utilisateur est connecté
$is_logged_in = isset($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? null;

// Définir les routes publiques (accessibles sans connexion)
$public_routes = ['home', 'login', 'register', 'confirm-delivery-scan'];

// Vérifier l'accès
if (!$is_logged_in && !in_array($path, $public_routes)) {
    header('Location: /login');
    exit;
}

// Rediriger les utilisateurs connectés
if ($is_logged_in && in_array($path, ['login', 'register'])) {
    if ($user_role === 'admin') {
        header('Location: /admin/dashboard');
    } elseif ($user_role === 'courier') {
        header('Location: /courier/dashboard');
    } else {
        header('Location: /expeditor/dashboard');
    }
    exit;
}

// Mapper les routes vers les vues
$route_map = [
    'home' => 'home.php',
    'login' => 'auth/login.php',
    'register' => 'auth/register.php',
    'expeditor/dashboard' => 'expeditor/dashboard.php',
    'expeditor/create-ad' => 'expeditor/create_ad.php',
    'courier/dashboard' => 'courier/dashboard.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'ad-details' => 'ad_details.php',
    'chat' => 'chat_modal.php',
    'qrcode-display' => 'qrcode_display.php',
    'confirm-delivery-scan' => 'confirm_delivery_scan.php',
    'evaluation-form' => 'evaluation_form.php'
];

// Déterminer le fichier de vue
$view_file = $route_map[$path] ?? '404.php';

// Inclure le header
include VIEWS_PATH . 'common/header.php';

// Inclure la vue spécifique
$view_path = VIEWS_PATH . $view_file;
if (file_exists($view_path)) {
    include $view_path;
} else {
    include VIEWS_PATH . '404.php';
}

// Inclure le footer
include VIEWS_PATH . 'common/footer.php';
?> 