<?php

namespace App\Core;

class Router
{
    private $routes = [];
    private $middlewares = [];

    /**
     * Ajoute une route GET
     */
    public function get($path, $handler, $middleware = null)
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Ajoute une route POST
     */
    public function post($path, $handler, $middleware = null)
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Ajoute une route PUT
     */
    public function put($path, $handler, $middleware = null)
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Ajoute une route DELETE
     */
    public function delete($path, $handler, $middleware = null)
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Ajoute une route pour toutes les méthodes HTTP
     */
    public function any($path, $handler, $middleware = null)
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], $path, $handler, $middleware);
    }

    /**
     * Ajoute une route API
     */
    public function api($path, $handler, $middleware = null)
    {
        $this->addRoute(['GET', 'POST', 'PUT', 'DELETE'], '/api' . $path, $handler, $middleware);
    }

    /**
     * Ajoute une route avec gestion des paramètres
     */
    private function addRoute($methods, $path, $handler, $middleware = null)
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => $method,
                'path' => $path,
                'handler' => $handler,
                'middleware' => $middleware
            ];
        }
    }

    /**
     * Exécute le routeur
     */
    public function dispatch($method, $uri)
    {
        // Nettoyer l'URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri)) {
                $params = $this->extractParams($route['path'], $uri);
                
                // Exécuter le middleware si présent
                if ($route['middleware']) {
                    $middlewareResult = $this->executeMiddleware($route['middleware']);
                    if ($middlewareResult === false) {
                        return $this->sendResponse(['error' => 'Unauthorized'], 401);
                    }
                }

                return $this->executeHandler($route['handler'], $params);
            }
        }

        // Route non trouvée
        return $this->sendResponse(['error' => 'Route not found'], 404);
    }

    /**
     * Vérifie si un chemin correspond à un pattern
     */
    private function matchPath($pattern, $path)
    {
        // Convertir le pattern en regex
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        return preg_match($pattern, $path);
    }

    /**
     * Extrait les paramètres d'une URL
     */
    private function extractParams($pattern, $path)
    {
        $params = [];
        
        // Trouver les noms des paramètres
        preg_match_all('/\{([^}]+)\}/', $pattern, $paramNames);
        
        // Convertir le pattern en regex pour extraire les valeurs
        $regex = preg_replace('/\{([^}]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $path, $matches)) {
            array_shift($matches); // Supprimer le match complet
            
            foreach ($paramNames[1] as $index => $name) {
                if (isset($matches[$index])) {
                    $params[$name] = $matches[$index];
                }
            }
        }
        
        return $params;
    }

    /**
     * Exécute un middleware
     */
    private function executeMiddleware($middleware)
    {
        if (is_callable($middleware)) {
            return call_user_func($middleware);
        }

        if (is_string($middleware) && class_exists($middleware)) {
            $instance = new $middleware();
            if (method_exists($instance, 'handle')) {
                return $instance->handle();
            }
        }

        return true;
    }

    /**
     * Exécute un handler
     */
    private function executeHandler($handler, $params = [])
    {
        try {
            if (is_callable($handler)) {
                $result = call_user_func_array($handler, $params);
            } elseif (is_string($handler)) {
                $result = $this->executeControllerMethod($handler, $params);
            } else {
                throw new \Exception('Invalid handler');
            }

            return $this->sendResponse($result);
        } catch (\Exception $e) {
            error_log('Handler execution error: ' . $e->getMessage());
            return $this->sendResponse(['error' => 'Internal server error'], 500);
        }
    }

    /**
     * Exécute une méthode de contrôleur
     */
    private function executeControllerMethod($handler, $params)
    {
        if (strpos($handler, '@') === false) {
            throw new \Exception('Invalid controller method format');
        }

        list($controller, $method) = explode('@', $handler);
        $controllerClass = 'App\\Controllers\\' . $controller;

        if (!class_exists($controllerClass)) {
            throw new \Exception("Controller {$controllerClass} not found");
        }

        $controllerInstance = new $controllerClass();
        
        if (!method_exists($controllerInstance, $method)) {
            throw new \Exception("Method {$method} not found in {$controllerClass}");
        }

        return call_user_func_array([$controllerInstance, $method], $params);
    }

    /**
     * Envoie une réponse HTTP
     */
    private function sendResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        
        // Déterminer le type de contenu
        $contentType = 'application/json';
        if (is_string($data) && (strpos($data, '<!DOCTYPE html>') === 0 || strpos($data, '<html>') === 0)) {
            $contentType = 'text/html';
        }
        
        header('Content-Type: ' . $contentType);
        
        if ($contentType === 'application/json') {
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } else {
            echo $data;
        }
        
        return true;
    }

    /**
     * Ajoute un middleware global
     */
    public function addMiddleware($middleware)
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Exécute tous les middlewares globaux
     */
    public function executeGlobalMiddlewares()
    {
        foreach ($this->middlewares as $middleware) {
            $result = $this->executeMiddleware($middleware);
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Redirige vers une autre URL
     */
    public function redirect($url, $statusCode = 302)
    {
        http_response_code($statusCode);
        header('Location: ' . $url);
        exit;
    }

    /**
     * Génère une URL pour une route nommée
     */
    public function url($name, $params = [])
    {
        // Implémentation simple - dans une vraie application, 
        // on aurait un système de noms de routes
        $url = $name;
        
        foreach ($params as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        return $url;
    }
} 