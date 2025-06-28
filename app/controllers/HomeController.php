<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        $pageTitle = 'Accueil';
        include __DIR__ . '/../views/home.php';
    }
} 