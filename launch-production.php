<?php
/**
 * Script de lancement de production pour LivraisonP2P
 * Configure et démarre l'application avec toutes les fonctionnalités
 */

require_once 'php/config.php';
require_once 'php/supabase-api.php';
require_once 'php/email-service.php';

class ProductionLauncher {
    private $config;
    private $supabase;
    private $emailService;
    
    public function __construct() {
        $this->config = new Config();
        $this->supabase = new SupabaseAPI();
        $this->emailService = new EmailService();
    }
    
    /**
     * Lancer la production
     */
    public function launch() {
        echo "🚀 Lancement de la production LivraisonP2P\n";
        echo "==========================================\n\n";
        
        // Vérifications préliminaires
        if (!$this->checkPrerequisites()) {
            echo "❌ Vérifications préliminaires échouées\n";
            return false;
        }
        
        // Configuration de la base de données
        if (!$this->setupDatabase()) {
            echo "❌ Configuration de la base de données échouée\n";
            return false;
        }
        
        // Configuration des services
        if (!$this->setupServices()) {
            echo "❌ Configuration des services échouée\n";
            return false;
        }
        
        // Tests de fonctionnement
        if (!$this->runTests()) {
            echo "❌ Tests de fonctionnement échoués\n";
            return false;
        }
        
        // Démarrage des services
        if (!$this->startServices()) {
            echo "❌ Démarrage des services échoué\n";
            return false;
        }
        
        echo "\n✅ Production lancée avec succès !\n";
        echo "🌐 Application accessible sur: " . $this->config->get('site_url') . "\n";
        echo "📧 Service d'emails: " . $this->config->get('email_service') . "\n";
        echo "🗄️ Base de données: Supabase\n";
        
        return true;
    }
    
    /**
     * Vérifications préliminaires
     */
    private function checkPrerequisites() {
        echo "🔍 Vérifications préliminaires...\n";
        
        $checks = [
            'Configuration Supabase' => $this->checkSupabaseConfig(),
            'Configuration Email' => $this->checkEmailConfig(),
            'Fichiers requis' => $this->checkRequiredFiles(),
            'Permissions' => $this->checkPermissions(),
            'Connexion Internet' => $this->checkInternetConnection()
        ];
        
        $allPassed = true;
        
        foreach ($checks as $check => $result) {
            if ($result) {
                echo "   ✅ $check\n";
            } else {
                echo "   ❌ $check\n";
                $allPassed = false;
            }
        }
        
        return $allPassed;
    }
    
    /**
     * Vérifier la configuration Supabase
     */
    private function checkSupabaseConfig() {
        $url = $this->config->get('supabase_url');
        $key = $this->config->get('supabase_anon_key');
        
        return !empty($url) && !empty($key) && filter_var($url, FILTER_VALIDATE_URL);
    }
    
    /**
     * Vérifier la configuration Email
     */
    private function checkEmailConfig() {
        $service = $this->config->get('email_service');
        $fromEmail = $this->config->get('email_from_address');
        
        if ($service === 'smtp') {
            return !empty($fromEmail);
        } elseif ($service === 'sendgrid') {
            return !empty($this->config->get('sendgrid_api_key'));
        } elseif ($service === 'mailgun') {
            return !empty($this->config->get('mailgun_api_key')) && !empty($this->config->get('mailgun_domain'));
        }
        
        return false;
    }
    
    /**
     * Vérifier les fichiers requis
     */
    private function checkRequiredFiles() {
        $requiredFiles = [
            'config.js',
            'js/services/supabase.js',
            'js/modules/auth.js',
            'css/auth-styles.css',
            'auth/login.html',
            'auth/register.html',
            'php/config.php',
            'php/email-service.php'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Vérifier les permissions
     */
    private function checkPermissions() {
        $writableDirs = [
            'logs',
            'uploads',
            'cache'
        ];
        
        foreach ($writableDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            if (!is_writable($dir)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Vérifier la connexion Internet
     */
    private function checkInternetConnection() {
        $hosts = [
            'google.com',
            'supabase.com',
            'cdn.jsdelivr.net'
        ];
        
        foreach ($hosts as $host) {
            if (!@fsockopen($host, 80, $errno, $errstr, 5)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Configuration de la base de données
     */
    private function setupDatabase() {
        echo "\n🗄️ Configuration de la base de données...\n";
        
        try {
            // Test de connexion
            $result = $this->supabase->testConnection();
            if (!$result['success']) {
                echo "   ❌ Impossible de se connecter à Supabase\n";
                return false;
            }
            echo "   ✅ Connexion Supabase établie\n";
            
            // Créer les tables si elles n'existent pas
            $tables = $this->createTables();
            if (!$tables) {
                echo "   ❌ Erreur lors de la création des tables\n";
                return false;
            }
            echo "   ✅ Tables créées/mises à jour\n";
            
            // Créer les fonctions SQL
            $functions = $this->createFunctions();
            if (!$functions) {
                echo "   ❌ Erreur lors de la création des fonctions\n";
                return false;
            }
            echo "   ✅ Fonctions SQL créées\n";
            
            // Créer les politiques RLS
            $policies = $this->createPolicies();
            if (!$policies) {
                echo "   ❌ Erreur lors de la création des politiques\n";
                return false;
            }
            echo "   ✅ Politiques RLS créées\n";
            
            return true;
            
        } catch (Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Créer les tables
     */
    private function createTables() {
        $tables = [
            'email_queue' => "
                CREATE TABLE IF NOT EXISTS email_queue (
                    id BIGSERIAL PRIMARY KEY,
                    recipient_email TEXT NOT NULL,
                    subject TEXT NOT NULL,
                    content TEXT NOT NULL,
                    user_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
                    email_type VARCHAR(50) NOT NULL CHECK (email_type IN ('confirmation', 'password_reset', 'notification', 'delivery_update', 'payment_confirmation')),
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'sent', 'failed', 'read')),
                    attempts INTEGER DEFAULT 0,
                    error_message TEXT,
                    sent_at TIMESTAMPTZ,
                    read_at TIMESTAMPTZ,
                    created_at TIMESTAMPTZ DEFAULT NOW(),
                    updated_at TIMESTAMPTZ DEFAULT NOW()
                );
            ",
            'user_sessions' => "
                CREATE TABLE IF NOT EXISTS user_sessions (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
                    session_token TEXT NOT NULL,
                    device_info JSONB,
                    ip_address INET,
                    user_agent TEXT,
                    expires_at TIMESTAMPTZ NOT NULL,
                    last_activity TIMESTAMPTZ DEFAULT NOW(),
                    is_active BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMPTZ DEFAULT NOW(),
                    updated_at TIMESTAMPTZ DEFAULT NOW()
                );
            ",
            'activity_logs' => "
                CREATE TABLE IF NOT EXISTS activity_logs (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID REFERENCES profiles(id) ON DELETE SET NULL,
                    action VARCHAR(100) NOT NULL,
                    table_name VARCHAR(50),
                    record_id UUID,
                    old_values JSONB,
                    new_values JSONB,
                    ip_address INET,
                    user_agent TEXT,
                    created_at TIMESTAMPTZ DEFAULT NOW()
                );
            "
        ];
        
        foreach ($tables as $tableName => $sql) {
            $result = $this->supabase->executeQuery($sql);
            if (!$result['success']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Créer les fonctions SQL
     */
    private function createFunctions() {
        $functions = [
            'send_confirmation_email' => file_get_contents('database/email-functions.sql')
        ];
        
        foreach ($functions as $functionName => $sql) {
            $result = $this->supabase->executeQuery($sql);
            if (!$result['success']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Créer les politiques RLS
     */
    private function createPolicies() {
        $policies = [
            'email_queue_policies' => "
                ALTER TABLE email_queue ENABLE ROW LEVEL SECURITY;
                
                CREATE POLICY email_queue_user_policy ON email_queue
                    FOR SELECT USING (auth.uid() = user_id);
                
                CREATE POLICY email_queue_admin_policy ON email_queue
                    FOR ALL USING (
                        EXISTS (
                            SELECT 1 FROM profiles 
                            WHERE id = auth.uid() 
                            AND role = 'admin'
                        )
                    );
            ",
            'user_sessions_policies' => "
                ALTER TABLE user_sessions ENABLE ROW LEVEL SECURITY;
                
                CREATE POLICY user_sessions_user_policy ON user_sessions
                    FOR ALL USING (auth.uid() = user_id);
            ",
            'activity_logs_policies' => "
                ALTER TABLE activity_logs ENABLE ROW LEVEL SECURITY;
                
                CREATE POLICY activity_logs_user_policy ON activity_logs
                    FOR SELECT USING (auth.uid() = user_id);
                
                CREATE POLICY activity_logs_admin_policy ON activity_logs
                    FOR ALL USING (
                        EXISTS (
                            SELECT 1 FROM profiles 
                            WHERE id = auth.uid() 
                            AND role = 'admin'
                        )
                    );
            "
        ];
        
        foreach ($policies as $policyName => $sql) {
            $result = $this->supabase->executeQuery($sql);
            if (!$result['success']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Configuration des services
     */
    private function setupServices() {
        echo "\n⚙️ Configuration des services...\n";
        
        // Configuration du service d'email
        $emailResult = $this->setupEmailService();
        if ($emailResult) {
            echo "   ✅ Service d'email configuré\n";
        } else {
            echo "   ❌ Erreur configuration service d'email\n";
            return false;
        }
        
        // Configuration des tâches cron
        $cronResult = $this->setupCronJobs();
        if ($cronResult) {
            echo "   ✅ Tâches cron configurées\n";
        } else {
            echo "   ⚠️ Erreur configuration tâches cron\n";
        }
        
        // Configuration du cache
        $cacheResult = $this->setupCache();
        if ($cacheResult) {
            echo "   ✅ Cache configuré\n";
        } else {
            echo "   ⚠️ Erreur configuration cache\n";
        }
        
        return true;
    }
    
    /**
     * Configuration du service d'email
     */
    private function setupEmailService() {
        try {
            // Test d'envoi d'email
            $testResult = $this->emailService->sendConfirmationEmail(
                'test@example.com',
                'test@example.com',
                'test-token'
            );
            
            return $testResult['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Configuration des tâches cron
     */
    private function setupCronJobs() {
        $cronJobs = [
            // Traitement de la file d'attente des emails (toutes les 5 minutes)
            '*/5 * * * * php ' . __DIR__ . '/cron/process-emails.php',
            
            // Nettoyage des anciens emails (tous les jours à 2h)
            '0 2 * * * php ' . __DIR__ . '/cron/cleanup-emails.php',
            
            // Sauvegarde de la base de données (tous les jours à 3h)
            '0 3 * * * php ' . __DIR__ . '/cron/backup-database.php',
            
            // Nettoyage des sessions expirées (toutes les heures)
            '0 * * * * php ' . __DIR__ . '/cron/cleanup-sessions.php'
        ];
        
        $cronFile = __DIR__ . '/cron/livraisonp2p.cron';
        $cronContent = implode("\n", $cronJobs) . "\n";
        
        return file_put_contents($cronFile, $cronContent) !== false;
    }
    
    /**
     * Configuration du cache
     */
    private function setupCache() {
        $cacheDir = __DIR__ . '/cache';
        
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        // Créer un fichier de configuration cache
        $cacheConfig = [
            'enabled' => true,
            'driver' => 'file',
            'path' => $cacheDir,
            'ttl' => 3600
        ];
        
        return file_put_contents($cacheDir . '/config.json', json_encode($cacheConfig)) !== false;
    }
    
    /**
     * Tests de fonctionnement
     */
    private function runTests() {
        echo "\n🧪 Tests de fonctionnement...\n";
        
        $tests = [
            'Test d\'authentification' => $this->testAuthentication(),
            'Test d\'envoi d\'email' => $this->testEmailSending(),
            'Test de base de données' => $this->testDatabase(),
            'Test des pages web' => $this->testWebPages()
        ];
        
        $allPassed = true;
        
        foreach ($tests as $test => $result) {
            if ($result) {
                echo "   ✅ $test\n";
            } else {
                echo "   ❌ $test\n";
                $allPassed = false;
            }
        }
        
        return $allPassed;
    }
    
    /**
     * Test d'authentification
     */
    private function testAuthentication() {
        try {
            // Test de connexion à Supabase
            $result = $this->supabase->testConnection();
            return $result['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test d'envoi d'email
     */
    private function testEmailSending() {
        try {
            $result = $this->emailService->sendConfirmationEmail(
                'test@example.com',
                'test@example.com',
                'test-token'
            );
            return $result['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test de base de données
     */
    private function testDatabase() {
        try {
            $result = $this->supabase->select('profiles', 'COUNT(*)', []);
            return $result['success'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Test des pages web
     */
    private function testWebPages() {
        $pages = [
            '/auth/login.html',
            '/auth/register.html',
            '/index.html'
        ];
        
        foreach ($pages as $page) {
            if (!file_exists(__DIR__ . $page)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Démarrage des services
     */
    private function startServices() {
        echo "\n🚀 Démarrage des services...\n";
        
        // Démarrer le serveur web (si nécessaire)
        $webServer = $this->startWebServer();
        if ($webServer) {
            echo "   ✅ Serveur web démarré\n";
        } else {
            echo "   ⚠️ Serveur web non démarré (utilisez un serveur externe)\n";
        }
        
        // Démarrer le traitement des emails
        $emailProcessor = $this->startEmailProcessor();
        if ($emailProcessor) {
            echo "   ✅ Processeur d'emails démarré\n";
        } else {
            echo "   ⚠️ Processeur d'emails non démarré\n";
        }
        
        // Démarrer le monitoring
        $monitoring = $this->startMonitoring();
        if ($monitoring) {
            echo "   ✅ Monitoring démarré\n";
        } else {
            echo "   ⚠️ Monitoring non démarré\n";
        }
        
        return true;
    }
    
    /**
     * Démarrer le serveur web
     */
    private function startWebServer() {
        // Vérifier si un serveur web est déjà en cours
        $port = 8000;
        
        if ($this->isPortInUse($port)) {
            return false;
        }
        
        // Démarrer le serveur PHP intégré
        $command = "php -S localhost:$port -t " . __DIR__ . " > logs/server.log 2>&1 &";
        exec($command);
        
        return true;
    }
    
    /**
     * Démarrer le processeur d'emails
     */
    private function startEmailProcessor() {
        $command = "php " . __DIR__ . "/cron/process-emails.php > logs/email-processor.log 2>&1 &";
        exec($command);
        
        return true;
    }
    
    /**
     * Démarrer le monitoring
     */
    private function startMonitoring() {
        $monitoringScript = __DIR__ . '/monitoring/start.php';
        
        if (file_exists($monitoringScript)) {
            $command = "php $monitoringScript > logs/monitoring.log 2>&1 &";
            exec($command);
            return true;
        }
        
        return false;
    }
    
    /**
     * Vérifier si un port est utilisé
     */
    private function isPortInUse($port) {
        $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }
    
    /**
     * Générer un rapport de production
     */
    public function generateReport() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0.0',
            'environment' => 'production',
            'services' => [
                'database' => 'Supabase',
                'email' => $this->config->get('email_service'),
                'web_server' => 'PHP Built-in Server',
                'cache' => 'File-based'
            ],
            'urls' => [
                'main' => $this->config->get('site_url'),
                'login' => $this->config->get('site_url') . '/auth/login.html',
                'register' => $this->config->get('site_url') . '/auth/register.html',
                'admin' => $this->config->get('site_url') . '/php/admin-dashboard.php'
            ],
            'features' => [
                'authentication' => true,
                'email_confirmation' => true,
                'password_reset' => true,
                'social_login' => true,
                'qr_codes' => true,
                'notifications' => true,
                'real_time' => true
            ]
        ];
        
        $reportFile = __DIR__ . '/logs/production-report.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        
        return $report;
    }
}

// Exécution du script
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $launcher = new ProductionLauncher();
    
    if ($launcher->launch()) {
        $report = $launcher->generateReport();
        echo "\n📊 Rapport de production généré: logs/production-report.json\n";
        echo "🎉 LivraisonP2P est maintenant en production !\n";
    } else {
        echo "\n❌ Échec du lancement de la production\n";
        exit(1);
    }
}
?> 