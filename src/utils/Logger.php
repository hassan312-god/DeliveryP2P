<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\JsonFormatter;

/**
 * Système de logging structuré
 * Observabilité et monitoring pour Render
 */
class Logger
{
    private MonologLogger $logger;
    private string $logLevel;
    private string $logPath;

    public function __construct()
    {
        $this->logLevel = $_ENV['LOG_LEVEL'] ?? 'info';
        $this->logPath = $_ENV['LOG_PATH'] ?? __DIR__ . '/../../storage/logs';
        
        // Création du répertoire de logs si nécessaire
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0755, true);
        }

        $this->initializeLogger();
    }

    /**
     * Initialise le logger avec les handlers appropriés
     */
    private function initializeLogger(): void
    {
        $this->logger = new MonologLogger('deliveryp2p-api');

        // Handler pour stdout (important pour Render)
        $stdoutHandler = new StreamHandler('php://stdout', $this->getLogLevel());
        $stdoutHandler->setFormatter(new JsonFormatter());
        $this->logger->pushHandler($stdoutHandler);

        // Handler pour les fichiers avec rotation
        $fileHandler = new RotatingFileHandler(
            $this->logPath . '/app.log',
            30, // Garder 30 jours
            $this->getLogLevel()
        );
        $fileHandler->setFormatter(new JsonFormatter());
        $this->logger->pushHandler($fileHandler);

        // Handler spécial pour les erreurs
        $errorHandler = new RotatingFileHandler(
            $this->logPath . '/error.log',
            30,
            MonologLogger::ERROR
        );
        $errorHandler->setFormatter(new JsonFormatter());
        $this->logger->pushHandler($errorHandler);
    }

    /**
     * Convertit le niveau de log en constante Monolog
     */
    private function getLogLevel(): int
    {
        return match (strtolower($this->logLevel)) {
            'debug' => MonologLogger::DEBUG,
            'info' => MonologLogger::INFO,
            'notice' => MonologLogger::NOTICE,
            'warning' => MonologLogger::WARNING,
            'error' => MonologLogger::ERROR,
            'critical' => MonologLogger::CRITICAL,
            'alert' => MonologLogger::ALERT,
            'emergency' => MonologLogger::EMERGENCY,
            default => MonologLogger::INFO
        };
    }

    /**
     * Log un message de debug
     */
    public function debug(string $message, array $context = []): void
    {
        $this->logger->debug($message, $this->enrichContext($context));
    }

    /**
     * Log un message d'information
     */
    public function info(string $message, array $context = []): void
    {
        $this->logger->info($message, $this->enrichContext($context));
    }

    /**
     * Log un avertissement
     */
    public function warning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $this->enrichContext($context));
    }

    /**
     * Log une erreur
     */
    public function error(string $message, array $context = []): void
    {
        $this->logger->error($message, $this->enrichContext($context));
    }

    /**
     * Log une erreur critique
     */
    public function critical(string $message, array $context = []): void
    {
        $this->logger->critical($message, $this->enrichContext($context));
    }

    /**
     * Log une alerte
     */
    public function alert(string $message, array $context = []): void
    {
        $this->logger->alert($message, $this->enrichContext($context));
    }

    /**
     * Log une urgence
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->logger->emergency($message, $this->enrichContext($context));
    }

    /**
     * Enrichit le contexte avec des informations système
     */
    private function enrichContext(array $context): array
    {
        $enriched = $context;

        // Informations de base
        $enriched['timestamp'] = date('c');
        $enriched['environment'] = $_ENV['APP_ENV'] ?? 'unknown';
        $enriched['version'] = '1.0.0';

        // Informations de requête
        if (isset($_SERVER['REQUEST_METHOD'])) {
            $enriched['request'] = [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                'ip' => $this->getClientIP(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? null
            ];
        }

        // Informations utilisateur si disponible
        if (isset($GLOBALS['current_user'])) {
            $enriched['user'] = [
                'id' => $GLOBALS['current_user']['id'] ?? null,
                'role' => $GLOBALS['current_user']['role'] ?? null
            ];
        }

        // Informations système
        $enriched['system'] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'] ?? 0
        ];

        return $enriched;
    }

    /**
     * Récupère l'IP du client
     */
    private function getClientIP(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR', // Proxy
            'HTTP_X_REAL_IP', // Nginx
            'REMOTE_ADDR' // Direct
        ];

        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                
                // Pour X-Forwarded-For, prendre la première IP
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Log une requête API
     */
    public function logRequest(string $method, string $uri, array $context = []): void
    {
        $this->info('API Request', array_merge($context, [
            'method' => $method,
            'uri' => $uri
        ]));
    }

    /**
     * Log une réponse API
     */
    public function logResponse(string $method, string $uri, int $statusCode, float $duration, array $context = []): void
    {
        $level = $statusCode >= 400 ? 'warning' : 'info';
        
        $this->$level('API Response', array_merge($context, [
            'method' => $method,
            'uri' => $uri,
            'status_code' => $statusCode,
            'duration_ms' => round($duration * 1000, 2)
        ]));
    }

    /**
     * Log une erreur d'exception
     */
    public function logException(\Throwable $exception, array $context = []): void
    {
        $this->error('Exception occurred', array_merge($context, [
            'exception' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ]
        ]));
    }

    /**
     * Log une activité de sécurité
     */
    public function logSecurity(string $event, array $context = []): void
    {
        $this->warning('Security event: ' . $event, array_merge($context, [
            'security_event' => true
        ]));
    }

    /**
     * Log une activité de performance
     */
    public function logPerformance(string $operation, float $duration, array $context = []): void
    {
        $level = $duration > 1.0 ? 'warning' : 'info';
        
        $this->$level('Performance: ' . $operation, array_merge($context, [
            'duration_seconds' => $duration,
            'performance_metric' => true
        ]));
    }

    /**
     * Récupère les logs récents
     */
    public function getRecentLogs(int $limit = 100): array
    {
        $logFile = $this->logPath . '/app.log';
        
        if (!file_exists($logFile)) {
            return [];
        }

        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = [];

        foreach (array_slice($lines, -$limit) as $line) {
            $log = json_decode($line, true);
            if ($log) {
                $logs[] = $log;
            }
        }

        return array_reverse($logs);
    }

    /**
     * Nettoie les anciens logs
     */
    public function cleanOldLogs(int $daysToKeep = 30): void
    {
        $cutoff = time() - ($daysToKeep * 24 * 60 * 60);
        
        $files = glob($this->logPath . '/*.log');
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
} 