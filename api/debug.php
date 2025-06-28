<?php
header('Content-Type: application/json');

echo json_encode([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
    'PHP_SELF' => $_SERVER['PHP_SELF'],
    'PATH_INFO' => $_SERVER['PATH_INFO'] ?? 'null',
    'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'null',
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'HTTP_HOST' => $_SERVER['HTTP_HOST'],
    'SERVER_NAME' => $_SERVER['SERVER_NAME'],
    'timestamp' => date('c')
], JSON_PRETTY_PRINT); 