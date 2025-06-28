<?php

declare(strict_types=1);

namespace DeliveryP2P\Core;

use DeliveryP2P\Core\Middleware\MiddlewareInterface;
use DeliveryP2P\Core\Exceptions\RouteNotFoundException;
use DeliveryP2P\Utils\Logger;

/**
 * Routeur moderne avec support des middlewares
 * Architecture hexagonale pour LivraisonP2P
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    private array $groupMiddlewares = [];
    private ?string $currentGroup = null;
    private Logger $logger;

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Ajoute un middleware global
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Définit un groupe de routes avec middlewares
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        $previousGroup = $this->currentGroup;
        $this->currentGroup = $prefix;
        $this->groupMiddlewares[$prefix] = $middlewares;

        $callback($this);

        $this->currentGroup = $previousGroup;
    }

    /**
     * Ajoute une route GET
     */
    public function get(string $path, $handler): void
    {
        $this->addRoute('GET', $path, $handler);
    }

    /**
     * Ajoute une route POST
     */
    public function post(string $path, $handler): void
    {
        $this->addRoute('POST', $path, $handler);
    }

    /**
     * Ajoute une route PUT
     */
    public function put(string $path, $handler): void
    {
        $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Ajoute une route DELETE
     */
    public function delete(string $path, $handler): void
    {
        $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Ajoute une route avec méthode spécifique
     */
    private function addRoute(string $method, string $path, $handler): void
    {
        $fullPath = $this->currentGroup ? $this->currentGroup . $path : $path;
        $routeKey = $method . ':' . $fullPath;

        $this->routes[$routeKey] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middlewares' => $this->groupMiddlewares[$this->currentGroup] ?? []
        ];

        $this->logger->info('Route registered', [
            'method' => $method,
            'path' => $fullPath,
            'group' => $this->currentGroup
        ]);
    }

    /**
     * Définit le handler pour les routes non trouvées
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler;
    }

    /**
     * Dispatch la requête vers la route appropriée
     */
    public function dispatch(string $method, string $uri): array
    {
        $this->logger->info('Dispatching request', [
            'method' => $method,
            'uri' => $uri
        ]);

        // Nettoyer l'URI
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/');
        if (empty($uri)) {
            $uri = '/';
        }

        // Chercher la route correspondante
        $route = $this->findRoute($method, $uri);

        if (!$route) {
            $this->logger->warning('Route not found', [
                'method' => $method,
                'uri' => $uri
            ]);

            if (isset($this->notFoundHandler)) {
                return call_user_func($this->notFoundHandler);
            }

            throw new RouteNotFoundException("Route not found: {$method} {$uri}");
        }

        // Exécuter les middlewares globaux
        foreach ($this->middlewares as $middleware) {
            $response = $middleware->handle($method, $uri);
            if ($response !== null) {
                return $response;
            }
        }

        // Exécuter les middlewares du groupe
        foreach ($route['middlewares'] as $middleware) {
            $response = $middleware->handle($method, $uri);
            if ($response !== null) {
                return $response;
            }
        }

        // Extraire les paramètres de l'URI
        $params = $this->extractParams($route['path'], $uri);

        // Exécuter le handler
        return $this->executeHandler($route['handler'], $params);
    }

    /**
     * Trouve la route correspondante
     */
    private function findRoute(string $method, string $uri): ?array
    {
        $routeKey = $method . ':' . $uri;

        // Recherche exacte
        if (isset($this->routes[$routeKey])) {
            return $this->routes[$routeKey];
        }

        // Recherche avec paramètres
        foreach ($this->routes as $key => $route) {
            if ($route['method'] === $method && $this->matchPattern($route['path'], $uri)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Vérifie si l'URI correspond au pattern de la route
     */
    private function matchPattern(string $pattern, string $uri): bool
    {
        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $pattern);
        $pattern = '#^' . $pattern . '$#';
        
        return (bool) preg_match($pattern, $uri);
    }

    /**
     * Extrait les paramètres de l'URI
     */
    private function extractParams(string $pattern, string $uri): array
    {
        $params = [];
        $patternParts = explode('/', trim($pattern, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        foreach ($patternParts as $index => $part) {
            if (preg_match('/\{([^}]+)\}/', $part, $matches)) {
                $paramName = $matches[1];
                $params[$paramName] = $uriParts[$index] ?? null;
            }
        }

        return $params;
    }

    /**
     * Exécute le handler de la route
     */
    private function executeHandler($handler, array $params = []): array
    {
        try {
            if (is_callable($handler)) {
                return call_user_func_array($handler, $params);
            }

            if (is_string($handler)) {
                // Format: Controller@method
                if (strpos($handler, '@') !== false) {
                    [$controllerClass, $method] = explode('@', $handler);
                    $controllerClass = "DeliveryP2P\\Controllers\\{$controllerClass}";
                    
                    if (!class_exists($controllerClass)) {
                        throw new \Exception("Controller not found: {$controllerClass}");
                    }

                    $controller = new $controllerClass();
                    
                    if (!method_exists($controller, $method)) {
                        throw new \Exception("Method not found: {$method} in {$controllerClass}");
                    }

                    return call_user_func_array([$controller, $method], $params);
                }
            }

            throw new \Exception("Invalid route handler");
        } catch (\Exception $e) {
            $this->logger->error('Handler execution failed', [
                'error' => $e->getMessage(),
                'params' => $params
            ]);
            throw $e;
        }
    }

    /**
     * Retourne toutes les routes enregistrées (pour debug)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
} 