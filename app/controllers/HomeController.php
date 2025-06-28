<?php

namespace App\Controllers;

class HomeController
{
    public function index()
    {
        // Afficher la page d'accueil
        include VIEWS_PATH . 'home.php';
    }
} 