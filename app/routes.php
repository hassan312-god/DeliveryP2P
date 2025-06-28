<?php
// app/routes.php
use App\Core\Router;

// Routes d'authentification
Router::post('/api/auth/register', 'AuthController@register');
Router::post('/api/auth/login', 'AuthController@login');
Router::post('/api/auth/logout', 'AuthController@logout');
Router::post('/api/auth/profile-update', 'AuthController@profileUpdate');

// Routes des annonces
Router::get('/api/ads', 'AdController@index');
Router::post('/api/ads', 'AdController@create');
Router::get('/api/ads/{id}', 'AdController@show');
Router::put('/api/ads/{id}', 'AdController@update');
Router::delete('/api/ads/{id}', 'AdController@delete');
Router::get('/api/ads/mine', 'AdController@myAds');

// Routes des livraisons
Router::get('/api/deliveries', 'DeliveryController@index');
Router::get('/api/deliveries/{id}', 'DeliveryController@show');
Router::post('/api/deliveries/{id}/accept', 'DeliveryController@accept');
Router::put('/api/deliveries/{id}/status', 'DeliveryController@updateStatus');
Router::post('/api/deliveries/{id}/confirm', 'DeliveryController@confirm');
Router::get('/api/deliveries/mine', 'DeliveryController@myDeliveries');

// Routes du chat
Router::get('/api/chat/{delivery_id}', 'ChatController@messages');
Router::post('/api/chat/{delivery_id}', 'ChatController@send');

// Routes des évaluations
Router::post('/api/evaluations', 'EvaluationController@create');
Router::get('/api/evaluations/{user_id}', 'EvaluationController@userEvaluations');

// Routes publiques
Router::get('/', 'HomeController@index');
Router::get('/home', 'HomeController@index');
Router::get('/login', 'AuthController@loginForm');
Router::get('/register', 'AuthController@registerForm');
Router::get('/confirm-delivery-scan', 'DeliveryController@scanForm'); 