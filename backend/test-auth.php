<?php
/**
 * Script de test pour l'authentification LivraisonP2P
 * Teste l'intégration avec Supabase et les emails de confirmation
 */

require_once 'php/config.php';
require_once 'php/supabase-api.php';
require_once 'php/email-service.php';

class AuthTest {
    private $config;
    private $supabase;
    private $emailService;
    
    public function __construct() {
        $this->config = new Config();
        $this->supabase = new SupabaseAPI();
        $this->emailService = new EmailService();
    }
    
    /**
     * Test de connexion à Supabase
     */
    public function testSupabaseConnection() {
        echo "=== Test de connexion Supabase ===\n";
        
        try {
            $result = $this->supabase->testConnection();
            
            if ($result['success']) {
                echo "✅ Connexion Supabase réussie\n";
                return true;
            } else {
                echo "❌ Échec de connexion Supabase: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test d'inscription d'utilisateur
     */
    public function testUserRegistration() {
        echo "\n=== Test d'inscription utilisateur ===\n";
        
        $testEmail = 'test-' . time() . '@example.com';
        $testPassword = 'TestPassword123!';
        
        $userData = [
            'email' => $testEmail,
            'password' => $testPassword,
            'nom' => 'Test',
            'prenom' => 'Utilisateur',
            'telephone' => '+33123456789',
            'role' => 'client'
        ];
        
        try {
            // Test d'inscription via Supabase Auth
            $authResult = $this->supabase->signUp($userData['email'], $userData['password'], [
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'telephone' => $userData['telephone'],
                'role' => $userData['role']
            ]);
            
            if ($authResult['success']) {
                echo "✅ Inscription Supabase réussie\n";
                $userId = $authResult['data']['user']['id'];
                
                // Test de création du profil
                $profileResult = $this->supabase->insert('profiles', [
                    'id' => $userId,
                    'email' => $userData['email'],
                    'nom' => $userData['nom'],
                    'prenom' => $userData['prenom'],
                    'telephone' => $userData['telephone'],
                    'role' => $userData['role'],
                    'date_inscription' => date('Y-m-d H:i:s'),
                    'statut' => 'en_attente_confirmation',
                    'email_confirme' => false
                ]);
                
                if ($profileResult['success']) {
                    echo "✅ Création du profil réussie\n";
                    
                    // Test d'envoi d'email de confirmation
                    $token = bin2hex(random_bytes(32));
                    $emailResult = $this->emailService->sendConfirmationEmail($userId, $userData['email'], $token);
                    
                    if ($emailResult['success']) {
                        echo "✅ Email de confirmation envoyé\n";
                    } else {
                        echo "⚠️ Échec envoi email: " . $emailResult['error'] . "\n";
                    }
                    
                    return $userId;
                } else {
                    echo "❌ Échec création profil: " . $profileResult['error'] . "\n";
                    return false;
                }
            } else {
                echo "❌ Échec inscription: " . $authResult['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur inscription: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test de connexion utilisateur
     */
    public function testUserLogin($email, $password) {
        echo "\n=== Test de connexion utilisateur ===\n";
        
        try {
            $result = $this->supabase->signIn($email, $password);
            
            if ($result['success']) {
                echo "✅ Connexion réussie\n";
                echo "   - User ID: " . $result['data']['user']['id'] . "\n";
                echo "   - Email: " . $result['data']['user']['email'] . "\n";
                echo "   - Email confirmé: " . ($result['data']['user']['email_confirmed_at'] ? 'Oui' : 'Non') . "\n";
                return $result['data']['user'];
            } else {
                echo "❌ Échec connexion: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur connexion: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test de récupération de profil
     */
    public function testGetUserProfile($userId) {
        echo "\n=== Test de récupération de profil ===\n";
        
        try {
            $result = $this->supabase->select('profiles', '*', ['id' => $userId]);
            
            if ($result['success'] && !empty($result['data'])) {
                $profile = $result['data'][0];
                echo "✅ Profil récupéré\n";
                echo "   - Nom: " . $profile['nom'] . " " . $profile['prenom'] . "\n";
                echo "   - Email: " . $profile['email'] . "\n";
                echo "   - Rôle: " . $profile['role'] . "\n";
                echo "   - Statut: " . $profile['statut'] . "\n";
                echo "   - Email confirmé: " . ($profile['email_confirme'] ? 'Oui' : 'Non') . "\n";
                return $profile;
            } else {
                echo "❌ Échec récupération profil: " . ($result['error'] ?? 'Profil non trouvé') . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur récupération profil: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test de mise à jour de profil
     */
    public function testUpdateUserProfile($userId) {
        echo "\n=== Test de mise à jour de profil ===\n";
        
        $updateData = [
            'telephone' => '+33987654321',
            'ville' => 'Paris',
            'code_postal' => '75001',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $result = $this->supabase->update('profiles', $updateData, ['id' => $userId]);
            
            if ($result['success']) {
                echo "✅ Mise à jour du profil réussie\n";
                return true;
            } else {
                echo "❌ Échec mise à jour: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur mise à jour: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test d'envoi d'email de réinitialisation
     */
    public function testPasswordResetEmail($email) {
        echo "\n=== Test d'email de réinitialisation ===\n";
        
        $token = bin2hex(random_bytes(32));
        
        try {
            $result = $this->emailService->sendPasswordResetEmail($email, $token);
            
            if ($result['success']) {
                echo "✅ Email de réinitialisation envoyé\n";
                return true;
            } else {
                echo "❌ Échec envoi email: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur envoi email: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test de la file d'attente des emails
     */
    public function testEmailQueue() {
        echo "\n=== Test de la file d'attente des emails ===\n";
        
        try {
            // Test des statistiques
            $statsResult = $this->emailService->getEmailStats();
            
            if ($statsResult['success']) {
                $stats = $statsResult['stats'];
                echo "📊 Statistiques des emails:\n";
                echo "   - Total: " . $stats['total_emails'] . "\n";
                echo "   - En attente: " . $stats['pending_emails'] . "\n";
                echo "   - Envoyés: " . $stats['sent_emails'] . "\n";
                echo "   - Échecs: " . $stats['failed_emails'] . "\n";
                echo "   - Taux de succès: " . $stats['success_rate'] . "%\n";
            }
            
            // Test de traitement de la file
            $queueResult = $this->emailService->processEmailQueue();
            
            if ($queueResult['success']) {
                echo "✅ Traitement de la file d'attente:\n";
                echo "   - Traités: " . $queueResult['processed'] . "\n";
                echo "   - Succès: " . $queueResult['success_count'] . "\n";
                echo "   - Échecs: " . $queueResult['failed_count'] . "\n";
            }
            
            return true;
        } catch (Exception $e) {
            echo "❌ Erreur file d'attente: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test de déconnexion
     */
    public function testUserLogout() {
        echo "\n=== Test de déconnexion ===\n";
        
        try {
            $result = $this->supabase->signOut();
            
            if ($result['success']) {
                echo "✅ Déconnexion réussie\n";
                return true;
            } else {
                echo "❌ Échec déconnexion: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur déconnexion: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Test complet de l'authentification
     */
    public function runFullTest() {
        echo "🚀 Démarrage des tests d'authentification LivraisonP2P\n";
        echo "================================================\n";
        
        // Test de connexion
        if (!$this->testSupabaseConnection()) {
            echo "\n❌ Test de connexion échoué. Arrêt des tests.\n";
            return false;
        }
        
        // Test d'inscription
        $userId = $this->testUserRegistration();
        if (!$userId) {
            echo "\n❌ Test d'inscription échoué. Arrêt des tests.\n";
            return false;
        }
        
        // Test de récupération de profil
        $profile = $this->testGetUserProfile($userId);
        if (!$profile) {
            echo "\n❌ Test de récupération de profil échoué.\n";
        }
        
        // Test de mise à jour de profil
        $this->testUpdateUserProfile($userId);
        
        // Test d'email de réinitialisation
        $this->testPasswordResetEmail($profile['email']);
        
        // Test de la file d'attente
        $this->testEmailQueue();
        
        // Test de connexion (peut échouer si l'email n'est pas confirmé)
        $testPassword = 'TestPassword123!';
        $user = $this->testUserLogin($profile['email'], $testPassword);
        
        // Test de déconnexion
        $this->testUserLogout();
        
        echo "\n✅ Tests terminés!\n";
        echo "================================================\n";
        
        return true;
    }
}

// Exécution des tests si le script est appelé directement
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $test = new AuthTest();
    $test->runFullTest();
}
?> 