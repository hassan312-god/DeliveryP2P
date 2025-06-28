<?php

declare(strict_types=1);

namespace DeliveryP2P\Utils;

/**
 * Tests de connexion spécifiques pour Render
 * Validation de l'environnement de production
 */
class RenderConnectionTest
{
    private Database $database;
    private Logger $logger;

    public function __construct()
    {
        $this->database = Database::getInstance();
        $this->logger = new Logger();
    }

    /**
     * Test complet de l'environnement Render
     */
    public function testRenderEnvironment(): array
    {
        $results = [
            'environment' => $this->testEnvironmentVariables(),
            'supabase' => $this->testSupabaseConnection(),
            'services' => $this->testServices(),
            'performance' => $this->testPerformance(),
            'overall_status' => 'unknown'
        ];

        // Détermination du statut global
        $allPassed = true;
        foreach ($results as $key => $result) {
            if ($key !== 'overall_status' && isset($result['status']) && $result['status'] === 'failed') {
                $allPassed = false;
                break;
            }
        }

        $results['overall_status'] = $allPassed ? 'healthy' : 'unhealthy';

        return $results;
    }

    /**
     * Test des variables d'environnement Render
     */
    private function testEnvironmentVariables(): array
    {
        $requiredVars = [
            'APP_ENV' => 'Environment de l\'application',
            'SUPABASE_URL' => 'URL Supabase',
            'SUPABASE_ANON_KEY' => 'Clé anonyme Supabase',
            'JWT_SECRET' => 'Secret JWT',
            'ENCRYPTION_KEY' => 'Clé de chiffrement',
            'QR_CODE_SECRET' => 'Secret QR code'
        ];

        $missing = [];
        $configured = [];

        foreach ($requiredVars as $var => $description) {
            if (empty($_ENV[$var])) {
                $missing[] = $var;
            } else {
                $configured[] = $var;
            }
        }

        $status = empty($missing) ? 'passed' : 'failed';

        return [
            'status' => $status,
            'configured_vars' => count($configured),
            'missing_vars' => count($missing),
            'missing_list' => $missing,
            'render_environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'php_version' => PHP_VERSION
        ];
    }

    /**
     * Test de connexion Supabase
     */
    private function testSupabaseConnection(): array
    {
        try {
            $config = $this->database->getConfig();
            $test = $this->database->testConnection();

            $status = $test['success'] ? 'passed' : 'failed';

            return [
                'status' => $status,
                'url_configured' => $config['has_anon_key'],
                'anon_key_configured' => $config['has_anon_key'],
                'service_key_configured' => $config['has_service_key'],
                'connection_test' => $test,
                'response_time_ms' => $this->measureResponseTime()
            ];

        } catch (\Exception $e) {
            $this->logger->error('Supabase connection test failed', [
                'error' => $e->getMessage()
            ]);

            return [
                'status' => 'failed',
                'error' => $e->getMessage(),
                'url_configured' => false,
                'anon_key_configured' => false,
                'service_key_configured' => false
            ];
        }
    }

    /**
     * Test des services essentiels
     */
    private function testServices(): array
    {
        $services = [
            'qr_code_service' => $this->testQRCodeService(),
            'jwt_service' => $this->testJWTService(),
            'cache_service' => $this->testCacheService(),
            'logger_service' => $this->testLoggerService()
        ];

        $allPassed = true;
        foreach ($services as $service) {
            if ($service['status'] === 'failed') {
                $allPassed = false;
            }
        }

        return [
            'status' => $allPassed ? 'passed' : 'failed',
            'services' => $services
        ];
    }

    /**
     * Test du service QR Code
     */
    private function testQRCodeService(): array
    {
        try {
            $qrService = new \DeliveryP2P\Services\QRCodeService();
            
            // Test de génération d'un QR de test
            $testData = [
                'qr_id' => 'test-' . uniqid(),
                'delivery_id' => 'test-delivery',
                'type' => 'test',
                'timestamp' => time(),
                'expiry' => time() + 3600
            ];

            $encrypted = $this->callPrivateMethod($qrService, 'encryptData', [$testData]);
            $decrypted = $this->callPrivateMethod($qrService, 'decryptData', [$encrypted]);

            $success = $decrypted && $decrypted['qr_id'] === $testData['qr_id'];

            return [
                'status' => $success ? 'passed' : 'failed',
                'encryption_working' => $success,
                'test_data' => $success ? 'valid' : 'invalid'
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test du service JWT
     */
    private function testJWTService(): array
    {
        try {
            $jwtManager = new \DeliveryP2P\Utils\JWTManager();
            
            $testPayload = [
                'user_id' => 'test-user',
                'email' => 'test@example.com',
                'role' => 'user'
            ];

            $token = $jwtManager->generateAccessToken($testPayload);
            $validated = $jwtManager->validateToken($token);

            $success = $validated && $validated['user_id'] === 'test-user';

            return [
                'status' => $success ? 'passed' : 'failed',
                'token_generation' => !empty($token),
                'token_validation' => $success
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test du service Cache
     */
    private function testCacheService(): array
    {
        try {
            $cache = new Cache();
            
            $testKey = 'test-' . uniqid();
            $testValue = 'test-value-' . time();
            
            $setResult = $cache->set($testKey, $testValue, 60);
            $getResult = $cache->get($testKey);
            $deleteResult = $cache->delete($testKey);

            $success = $setResult && $getResult === $testValue && $deleteResult;

            return [
                'status' => $success ? 'passed' : 'failed',
                'set_operation' => $setResult,
                'get_operation' => $getResult === $testValue,
                'delete_operation' => $deleteResult
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test du service Logger
     */
    private function testLoggerService(): array
    {
        try {
            $logger = new Logger();
            
            $logger->info('Test log message', ['test' => true]);
            
            return [
                'status' => 'passed',
                'logging_working' => true
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test de performance
     */
    private function testPerformance(): array
    {
        $startTime = microtime(true);
        
        // Test de charge simple
        $operations = 0;
        $maxTime = 5; // 5 secondes max
        
        while ((microtime(true) - $startTime) < $maxTime && $operations < 1000) {
            $this->database->get('users', [], ['limit' => 1]);
            $operations++;
        }
        
        $duration = microtime(true) - $startTime;
        $opsPerSecond = $operations / $duration;
        
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        
        return [
            'status' => $opsPerSecond > 10 ? 'passed' : 'warning',
            'operations_per_second' => round($opsPerSecond, 2),
            'total_operations' => $operations,
            'duration_seconds' => round($duration, 2),
            'memory_usage_mb' => round($memoryUsage / 1024 / 1024, 2),
            'memory_peak_mb' => round($memoryPeak / 1024 / 1024, 2)
        ];
    }

    /**
     * Mesure le temps de réponse de Supabase
     */
    private function measureResponseTime(): float
    {
        $startTime = microtime(true);
        
        try {
            $this->database->get('users', [], ['limit' => 1]);
        } catch (\Exception $e) {
            // Ignore les erreurs pour la mesure de temps
        }
        
        return round((microtime(true) - $startTime) * 1000, 2);
    }

    /**
     * Appelle une méthode privée (pour les tests)
     */
    private function callPrivateMethod($object, string $methodName, array $args)
    {
        $reflection = new \ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        
        return $method->invokeArgs($object, $args);
    }

    /**
     * Génère un rapport de santé complet
     */
    public function generateHealthReport(): array
    {
        $testResults = $this->testRenderEnvironment();
        
        $report = [
            'timestamp' => date('c'),
            'environment' => $_ENV['APP_ENV'] ?? 'unknown',
            'version' => '2.0.0',
            'overall_status' => $testResults['overall_status'],
            'tests' => $testResults,
            'recommendations' => $this->generateRecommendations($testResults)
        ];

        return $report;
    }

    /**
     * Génère des recommandations basées sur les tests
     */
    private function generateRecommendations(array $testResults): array
    {
        $recommendations = [];

        // Vérification des variables d'environnement
        if ($testResults['environment']['status'] === 'failed') {
            $recommendations[] = 'Configurer toutes les variables d\'environnement requises dans le dashboard Render';
        }

        // Vérification de Supabase
        if ($testResults['supabase']['status'] === 'failed') {
            $recommendations[] = 'Vérifier la configuration Supabase et les clés d\'API';
        }

        // Vérification des performances
        if ($testResults['performance']['status'] === 'warning') {
            $recommendations[] = 'Optimiser les performances de la base de données';
        }

        // Vérification des services
        if ($testResults['services']['status'] === 'failed') {
            $recommendations[] = 'Vérifier la configuration des services internes';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'Tous les systèmes fonctionnent correctement';
        }

        return $recommendations;
    }
} 