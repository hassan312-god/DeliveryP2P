<?php
/**
 * Script de configuration de la base de données LivraisonP2P
 * Initialise les tables, fonctions et politiques pour les emails de confirmation
 */

require_once 'php/config.php';
require_once 'php/supabase-api.php';

class DatabaseSetup {
    private $config;
    private $supabase;
    
    public function __construct() {
        $this->config = new Config();
        $this->supabase = new SupabaseAPI();
    }
    
    /**
     * Exécuter la configuration complète
     */
    public function setup() {
        echo "🚀 Configuration de la base de données LivraisonP2P\n";
        echo "================================================\n";
        
        // Test de connexion
        if (!$this->testConnection()) {
            echo "❌ Impossible de se connecter à Supabase\n";
            return false;
        }
        
        // Créer les tables
        if (!$this->createTables()) {
            echo "❌ Erreur lors de la création des tables\n";
            return false;
        }
        
        // Créer les fonctions
        if (!$this->createFunctions()) {
            echo "❌ Erreur lors de la création des fonctions\n";
            return false;
        }
        
        // Créer les triggers
        if (!$this->createTriggers()) {
            echo "❌ Erreur lors de la création des triggers\n";
            return false;
        }
        
        // Créer les politiques RLS
        if (!$this->createRLSPolicies()) {
            echo "❌ Erreur lors de la création des politiques RLS\n";
            return false;
        }
        
        // Insérer les données initiales
        if (!$this->insertInitialData()) {
            echo "❌ Erreur lors de l'insertion des données initiales\n";
            return false;
        }
        
        echo "\n✅ Configuration terminée avec succès!\n";
        return true;
    }
    
    /**
     * Test de connexion
     */
    private function testConnection() {
        echo "🔗 Test de connexion à Supabase...\n";
        
        try {
            $result = $this->supabase->testConnection();
            
            if ($result['success']) {
                echo "✅ Connexion réussie\n";
                return true;
            } else {
                echo "❌ Échec de connexion: " . $result['error'] . "\n";
                return false;
            }
        } catch (Exception $e) {
            echo "❌ Erreur de connexion: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Créer les tables
     */
    private function createTables() {
        echo "\n📋 Création des tables...\n";
        
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
            echo "   Création de la table $tableName...\n";
            
            try {
                $result = $this->supabase->executeQuery($sql);
                
                if ($result['success']) {
                    echo "   ✅ Table $tableName créée\n";
                } else {
                    echo "   ❌ Erreur création $tableName: " . $result['error'] . "\n";
                    return false;
                }
            } catch (Exception $e) {
                echo "   ❌ Erreur création $tableName: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Créer les fonctions SQL
     */
    private function createFunctions() {
        echo "\n🔧 Création des fonctions SQL...\n";
        
        $functions = [
            'send_confirmation_email' => "
                CREATE OR REPLACE FUNCTION send_confirmation_email(
                    p_user_id UUID,
                    p_email TEXT,
                    p_token TEXT
                )
                RETURNS BOOLEAN
                LANGUAGE plpgsql
                SECURITY DEFINER
                AS $$
                DECLARE
                    v_user_name TEXT;
                    v_confirmation_url TEXT;
                    v_email_content TEXT;
                    v_email_subject TEXT;
                BEGIN
                    -- Récupérer le nom de l'utilisateur
                    SELECT CONCAT(prenom, ' ', nom) INTO v_user_name
                    FROM profiles
                    WHERE id = p_user_id;
                    
                    IF v_user_name IS NULL THEN
                        v_user_name := 'Utilisateur';
                    END IF;
                    
                    -- Construire l'URL de confirmation
                    v_confirmation_url := CONCAT(
                        'https://livraisonp2p.com/auth/email-confirmation.html?',
                        'token=', p_token,
                        '&type=signup',
                        '&email=', p_email
                    );
                    
                    -- Construire le contenu de l'email
                    v_email_subject := 'Confirmez votre compte LivraisonP2P';
                    v_email_content := CONCAT(
                        '<!DOCTYPE html>',
                        '<html>',
                        '<head>',
                        '<meta charset=\"UTF-8\">',
                        '<title>Confirmation de compte</title>',
                        '</head>',
                        '<body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">',
                        '<div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">',
                        '<div style=\"text-align: center; margin-bottom: 30px;\">',
                        '<h1 style=\"color: #3B82F6;\">LivraisonP2P</h1>',
                        '</div>',
                        '<div style=\"background: #f8f9fa; padding: 30px; border-radius: 10px;\">',
                        '<h2 style=\"color: #2d3748; margin-bottom: 20px;\">Bonjour ', v_user_name, ' !</h2>',
                        '<p>Merci de vous être inscrit sur LivraisonP2P. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>',
                        '<div style=\"text-align: center; margin: 30px 0;\">',
                        '<a href=\"', v_confirmation_url, '\" style=\"background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;\">Confirmer mon compte</a>',
                        '</div>',
                        '<p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>',
                        '<p style=\"word-break: break-all; color: #3B82F6;\">', v_confirmation_url, '</p>',
                        '<p>Ce lien expirera dans 24 heures.</p>',
                        '<p>Si vous n''avez pas créé de compte sur LivraisonP2P, vous pouvez ignorer cet email.</p>',
                        '</div>',
                        '<div style=\"text-align: center; margin-top: 30px; color: #666; font-size: 14px;\">',
                        '<p>© 2024 LivraisonP2P. Tous droits réservés.</p>',
                        '</div>',
                        '</div>',
                        '</body>',
                        '</html>'
                    );
                    
                    -- Insérer l'email dans la table des emails
                    INSERT INTO email_queue (
                        recipient_email,
                        subject,
                        content,
                        user_id,
                        email_type,
                        status,
                        created_at
                    ) VALUES (
                        p_email,
                        v_email_subject,
                        v_email_content,
                        p_user_id,
                        'confirmation',
                        'pending',
                        NOW()
                    );
                    
                    -- Créer une notification
                    INSERT INTO notifications (
                        user_id,
                        type,
                        title,
                        message,
                        data,
                        created_at
                    ) VALUES (
                        p_user_id,
                        'email_confirmation_sent',
                        'Email de confirmation envoyé',
                        'Un email de confirmation a été envoyé à votre adresse email.',
                        jsonb_build_object('email', p_email, 'token', p_token),
                        NOW()
                    );
                    
                    RETURN TRUE;
                EXCEPTION
                    WHEN OTHERS THEN
                        RAISE LOG 'Erreur lors de l''envoi de l''email de confirmation: %', SQLERRM;
                        RETURN FALSE;
                END;
                $$;
            ",
            'send_password_reset_email' => "
                CREATE OR REPLACE FUNCTION send_password_reset_email(
                    p_email TEXT,
                    p_token TEXT
                )
                RETURNS BOOLEAN
                LANGUAGE plpgsql
                SECURITY DEFINER
                AS $$
                DECLARE
                    v_user_id UUID;
                    v_user_name TEXT;
                    v_reset_url TEXT;
                    v_email_content TEXT;
                    v_email_subject TEXT;
                BEGIN
                    -- Récupérer l'utilisateur
                    SELECT id, CONCAT(prenom, ' ', nom) INTO v_user_id, v_user_name
                    FROM profiles
                    WHERE email = p_email;
                    
                    IF v_user_id IS NULL THEN
                        RETURN FALSE;
                    END IF;
                    
                    IF v_user_name IS NULL THEN
                        v_user_name := 'Utilisateur';
                    END IF;
                    
                    -- Construire l'URL de réinitialisation
                    v_reset_url := CONCAT(
                        'https://livraisonp2p.com/auth/reset-password.html?',
                        'token=', p_token,
                        '&type=recovery',
                        '&email=', p_email
                    );
                    
                    -- Construire le contenu de l'email
                    v_email_subject := 'Réinitialisation de votre mot de passe LivraisonP2P';
                    v_email_content := CONCAT(
                        '<!DOCTYPE html>',
                        '<html>',
                        '<head>',
                        '<meta charset=\"UTF-8\">',
                        '<title>Réinitialisation de mot de passe</title>',
                        '</head>',
                        '<body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">',
                        '<div style=\"max-width: 600px; margin: 0 auto; padding: 20px;\">',
                        '<div style=\"text-align: center; margin-bottom: 30px;\">',
                        '<h1 style=\"color: #3B82F6;\">LivraisonP2P</h1>',
                        '</div>',
                        '<div style=\"background: #f8f9fa; padding: 30px; border-radius: 10px;\">',
                        '<h2 style=\"color: #2d3748; margin-bottom: 20px;\">Bonjour ', v_user_name, ' !</h2>',
                        '<p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>',
                        '<div style=\"text-align: center; margin: 30px 0;\">',
                        '<a href=\"', v_reset_url, '\" style=\"background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;\">Réinitialiser mon mot de passe</a>',
                        '</div>',
                        '<p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>',
                        '<p style=\"word-break: break-all; color: #3B82F6;\">', v_reset_url, '</p>',
                        '<p>Ce lien expirera dans 1 heure.</p>',
                        '<p>Si vous n''avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>',
                        '</div>',
                        '<div style=\"text-align: center; margin-top: 30px; color: #666; font-size: 14px;\">',
                        '<p>© 2024 LivraisonP2P. Tous droits réservés.</p>',
                        '</div>',
                        '</div>',
                        '</body>',
                        '</html>'
                    );
                    
                    -- Insérer l'email dans la table des emails
                    INSERT INTO email_queue (
                        recipient_email,
                        subject,
                        content,
                        user_id,
                        email_type,
                        status,
                        created_at
                    ) VALUES (
                        p_email,
                        v_email_subject,
                        v_email_content,
                        v_user_id,
                        'password_reset',
                        'pending',
                        NOW()
                    );
                    
                    -- Créer une notification
                    INSERT INTO notifications (
                        user_id,
                        type,
                        title,
                        message,
                        data,
                        created_at
                    ) VALUES (
                        v_user_id,
                        'password_reset_sent',
                        'Email de réinitialisation envoyé',
                        'Un email de réinitialisation de mot de passe a été envoyé.',
                        jsonb_build_object('email', p_email, 'token', p_token),
                        NOW()
                    );
                    
                    RETURN TRUE;
                EXCEPTION
                    WHEN OTHERS THEN
                        RAISE LOG 'Erreur lors de l''envoi de l''email de réinitialisation: %', SQLERRM;
                        RETURN FALSE;
                END;
                $$;
            ",
            'confirm_user_email' => "
                CREATE OR REPLACE FUNCTION confirm_user_email(
                    p_user_id UUID,
                    p_token TEXT
                )
                RETURNS BOOLEAN
                LANGUAGE plpgsql
                SECURITY DEFINER
                AS $$
                DECLARE
                    v_user_exists BOOLEAN;
                BEGIN
                    -- Vérifier que l'utilisateur existe
                    SELECT EXISTS(SELECT 1 FROM profiles WHERE id = p_user_id) INTO v_user_exists;
                    
                    IF NOT v_user_exists THEN
                        RETURN FALSE;
                    END IF;
                    
                    -- Mettre à jour le profil utilisateur
                    UPDATE profiles
                    SET 
                        email_confirme = TRUE,
                        statut = 'actif',
                        date_confirmation = NOW(),
                        updated_at = NOW()
                    WHERE id = p_user_id;
                    
                    -- Créer une notification de confirmation
                    INSERT INTO notifications (
                        user_id,
                        type,
                        title,
                        message,
                        data,
                        created_at
                    ) VALUES (
                        p_user_id,
                        'email_confirmed',
                        'Email confirmé',
                        'Votre adresse email a été confirmée avec succès.',
                        jsonb_build_object('confirmed_at', NOW()),
                        NOW()
                    );
                    
                    -- Marquer l'email comme envoyé
                    UPDATE email_queue
                    SET 
                        status = 'sent',
                        sent_at = NOW()
                    WHERE user_id = p_user_id 
                    AND email_type = 'confirmation'
                    AND status = 'pending';
                    
                    RETURN TRUE;
                EXCEPTION
                    WHEN OTHERS THEN
                        RAISE LOG 'Erreur lors de la confirmation de l''email: %', SQLERRM;
                        RETURN FALSE;
                END;
                $$;
            "
        ];
        
        foreach ($functions as $functionName => $sql) {
            echo "   Création de la fonction $functionName...\n";
            
            try {
                $result = $this->supabase->executeQuery($sql);
                
                if ($result['success']) {
                    echo "   ✅ Fonction $functionName créée\n";
                } else {
                    echo "   ❌ Erreur création $functionName: " . $result['error'] . "\n";
                    return false;
                }
            } catch (Exception $e) {
                echo "   ❌ Erreur création $functionName: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Créer les triggers
     */
    private function createTriggers() {
        echo "\n🔗 Création des triggers...\n";
        
        $triggers = [
            'trigger_send_confirmation_email' => "
                CREATE OR REPLACE FUNCTION trigger_send_confirmation_email()
                RETURNS TRIGGER
                LANGUAGE plpgsql
                SECURITY DEFINER
                AS $$
                DECLARE
                    v_token TEXT;
                BEGIN
                    -- Générer un token de confirmation
                    v_token := encode(gen_random_bytes(32), 'hex');
                    
                    -- Envoyer l'email de confirmation
                    PERFORM send_confirmation_email(NEW.id, NEW.email, v_token);
                    
                    RETURN NEW;
                END;
                $$;
            ",
            'send_confirmation_email_trigger' => "
                DROP TRIGGER IF EXISTS send_confirmation_email_trigger ON profiles;
                CREATE TRIGGER send_confirmation_email_trigger
                    AFTER INSERT ON profiles
                    FOR EACH ROW
                    WHEN (NEW.email_confirme = FALSE)
                    EXECUTE FUNCTION trigger_send_confirmation_email();
            "
        ];
        
        foreach ($triggers as $triggerName => $sql) {
            echo "   Création du trigger $triggerName...\n";
            
            try {
                $result = $this->supabase->executeQuery($sql);
                
                if ($result['success']) {
                    echo "   ✅ Trigger $triggerName créé\n";
                } else {
                    echo "   ❌ Erreur création $triggerName: " . $result['error'] . "\n";
                    return false;
                }
            } catch (Exception $e) {
                echo "   ❌ Erreur création $triggerName: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Créer les politiques RLS
     */
    private function createRLSPolicies() {
        echo "\n🔒 Création des politiques RLS...\n";
        
        $policies = [
            'email_queue_policies' => "
                -- Activer RLS sur email_queue
                ALTER TABLE email_queue ENABLE ROW LEVEL SECURITY;
                
                -- Politique pour les utilisateurs (voir leurs propres emails)
                CREATE POLICY email_queue_user_policy ON email_queue
                    FOR SELECT USING (auth.uid() = user_id);
                
                -- Politique pour les admins (voir tous les emails)
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
                -- Activer RLS sur user_sessions
                ALTER TABLE user_sessions ENABLE ROW LEVEL SECURITY;
                
                -- Politique pour les utilisateurs (voir leurs propres sessions)
                CREATE POLICY user_sessions_user_policy ON user_sessions
                    FOR ALL USING (auth.uid() = user_id);
            ",
            'activity_logs_policies' => "
                -- Activer RLS sur activity_logs
                ALTER TABLE activity_logs ENABLE ROW LEVEL SECURITY;
                
                -- Politique pour les utilisateurs (voir leurs propres logs)
                CREATE POLICY activity_logs_user_policy ON activity_logs
                    FOR SELECT USING (auth.uid() = user_id);
                
                -- Politique pour les admins (voir tous les logs)
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
            echo "   Création des politiques $policyName...\n";
            
            try {
                $result = $this->supabase->executeQuery($sql);
                
                if ($result['success']) {
                    echo "   ✅ Politiques $policyName créées\n";
                } else {
                    echo "   ❌ Erreur création $policyName: " . $result['error'] . "\n";
                    return false;
                }
            } catch (Exception $e) {
                echo "   ❌ Erreur création $policyName: " . $e->getMessage() . "\n";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Insérer les données initiales
     */
    private function insertInitialData() {
        echo "\n📝 Insertion des données initiales...\n";
        
        $initialData = [
            'pricing_settings' => [
                [
                    'nom' => 'Tarification Standard',
                    'description' => 'Tarification par défaut pour les livraisons',
                    'prix_base' => 5.00,
                    'prix_km' => 1.50,
                    'prix_kg' => 0.50,
                    'commission_pourcentage' => 10.00,
                    'frais_fixes' => 0.00,
                    'multiplicateur_urgence' => 1.50,
                    'multiplicateur_express' => 2.00,
                    'actif' => true
                ]
            ],
            'coverage_zones' => [
                [
                    'nom' => 'Paris Centre',
                    'description' => 'Zone de couverture du centre de Paris',
                    'centre_latitude' => 48.8566,
                    'centre_longitude' => 2.3522,
                    'rayon_km' => 10,
                    'actif' => true
                ]
            ]
        ];
        
        foreach ($initialData as $tableName => $data) {
            echo "   Insertion dans $tableName...\n";
            
            foreach ($data as $row) {
                try {
                    $result = $this->supabase->insert($tableName, $row);
                    
                    if ($result['success']) {
                        echo "   ✅ Donnée insérée dans $tableName\n";
                    } else {
                        echo "   ❌ Erreur insertion $tableName: " . $result['error'] . "\n";
                        return false;
                    }
                } catch (Exception $e) {
                    echo "   ❌ Erreur insertion $tableName: " . $e->getMessage() . "\n";
                    return false;
                }
            }
        }
        
        return true;
    }
}

// Exécution du script si appelé directement
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $setup = new DatabaseSetup();
    $setup->setup();
}
?> 