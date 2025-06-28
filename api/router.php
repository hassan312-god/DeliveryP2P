<?php
// Router pour le serveur PHP intégré
$uri = $_SERVER['REQUEST_URI'];

// Supprimer les paramètres de requête
$uri = parse_url($uri, PHP_URL_PATH);

// Si le fichier existe physiquement, le servir directement
if (file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// Sinon, rediriger vers index.php
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php'; 