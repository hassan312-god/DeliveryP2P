<?php
header('Content-Type: application/json');

echo json_encode([
    'status' => 'success',
    'message' => 'API fonctionne en local !',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION
]); 