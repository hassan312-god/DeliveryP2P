<?php
session_start();

// Configuration de base
define('BASE_PATH', __DIR__ . '/../');
define('VIEWS_PATH', __DIR__ . '/../app/views/');
define('CONTROLLERS_PATH', __DIR__ . '/../app/controllers/');

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

// Router simple avec support des contrôleurs
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

// Définir les routes avec leurs contrôleurs et actions
$routes = [
    // Routes publiques
    'home' => ['controller' => 'HomeController', 'action' => 'index'],
    'login' => ['controller' => 'AuthController', 'action' => 'login'],
    'register' => ['controller' => 'AuthController', 'action' => 'register'],
    
    // Routes des expéditeurs
    'expeditor/dashboard' => ['controller' => 'ExpeditorController', 'action' => 'dashboard'],
    'expeditor/create-ad' => ['controller' => 'ExpeditorController', 'action' => 'createAd'],
    
    // Routes des livreurs
    'courier/dashboard' => ['controller' => 'CourierController', 'action' => 'dashboard'],
    
    // Routes admin
    'admin/dashboard' => ['controller' => 'AdminController', 'action' => 'dashboard'],
    
    // Routes des annonces
    'ad-details' => ['controller' => 'AdController', 'action' => 'details'],
    
    // Routes de chat
    'chat' => ['controller' => 'ChatController', 'action' => 'index'],
    
    // Routes QR Code
    'qrcode-display' => ['controller' => 'QRCodeController', 'action' => 'display'],
    'confirm-delivery-scan' => ['controller' => 'QRCodeController', 'action' => 'confirmDelivery'],
    
    // Routes d'évaluation
    'evaluation-form' => ['controller' => 'EvaluationController', 'action' => 'form'],
    
    // Routes Google Maps
    'maps' => ['controller' => 'MapsController', 'action' => 'index'],
    'maps/test' => ['controller' => 'MapsController', 'action' => 'test'],
    'maps/geocode' => ['controller' => 'MapsController', 'action' => 'geocode'],
    'maps/calculate-distance' => ['controller' => 'MapsController', 'action' => 'calculateDistance'],
    'maps/get-directions' => ['controller' => 'MapsController', 'action' => 'getDirections'],
    'maps/search-places' => ['controller' => 'MapsController', 'action' => 'searchPlaces'],
    'maps/validate-address' => ['controller' => 'MapsController', 'action' => 'validateAddress'],
];

// Déterminer la route
$route = $routes[$path] ?? null;

if ($route) {
    // Charger le contrôleur
    $controller_file = CONTROLLERS_PATH . $route['controller'] . '.php';
    
    if (file_exists($controller_file)) {
        require_once $controller_file;
        
        $controller_name = $route['controller'];
        $action_name = $route['action'];
        
        // Ajouter le namespace automatiquement
        $fqcn = "App\\Controllers\\$controller_name";
        
        // Créer une instance du contrôleur
        $controller = new $fqcn();
        
        // Vérifier si la méthode existe
        if (method_exists($controller, $action_name)) {
            // Appeler la méthode du contrôleur
            $controller->$action_name();
        } else {
            // Méthode non trouvée
            http_response_code(404);
            include VIEWS_PATH . '404.php';
        }
    } else {
        // Contrôleur non trouvé
        http_response_code(404);
        include VIEWS_PATH . '404.php';
    }
} else {
    // Route non trouvée - essayer l'ancien système de fichiers de vue
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
}
?> 