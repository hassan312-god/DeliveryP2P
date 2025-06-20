-- =====================================================
-- FONCTIONS POUR LA GESTION DES EMAILS ET NOTIFICATIONS
-- =====================================================

-- Fonction pour envoyer un email de confirmation
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
        '<meta charset="UTF-8">',
        '<title>Confirmation de compte</title>',
        '</head>',
        '<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">',
        '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">',
        '<div style="text-align: center; margin-bottom: 30px;">',
        '<h1 style="color: #3B82F6;">LivraisonP2P</h1>',
        '</div>',
        '<div style="background: #f8f9fa; padding: 30px; border-radius: 10px;">',
        '<h2 style="color: #2d3748; margin-bottom: 20px;">Bonjour ', v_user_name, ' !</h2>',
        '<p>Merci de vous être inscrit sur LivraisonP2P. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>',
        '<div style="text-align: center; margin: 30px 0;">',
        '<a href="', v_confirmation_url, '" style="background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Confirmer mon compte</a>',
        '</div>',
        '<p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>',
        '<p style="word-break: break-all; color: #3B82F6;">', v_confirmation_url, '</p>',
        '<p>Ce lien expirera dans 24 heures.</p>',
        '<p>Si vous n\'avez pas créé de compte sur LivraisonP2P, vous pouvez ignorer cet email.</p>',
        '</div>',
        '<div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">',
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

-- Fonction pour envoyer un email de réinitialisation de mot de passe
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
        '<meta charset="UTF-8">',
        '<title>Réinitialisation de mot de passe</title>',
        '</head>',
        '<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">',
        '<div style="max-width: 600px; margin: 0 auto; padding: 20px;">',
        '<div style="text-align: center; margin-bottom: 30px;">',
        '<h1 style="color: #3B82F6;">LivraisonP2P</h1>',
        '</div>',
        '<div style="background: #f8f9fa; padding: 30px; border-radius: 10px;">',
        '<h2 style="color: #2d3748; margin-bottom: 20px;">Bonjour ', v_user_name, ' !</h2>',
        '<p>Vous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe :</p>',
        '<div style="text-align: center; margin: 30px 0;">',
        '<a href="', v_reset_url, '" style="background: #3B82F6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Réinitialiser mon mot de passe</a>',
        '</div>',
        '<p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>',
        '<p style="word-break: break-all; color: #3B82F6;">', v_reset_url, '</p>',
        '<p>Ce lien expirera dans 1 heure.</p>',
        '<p>Si vous n\'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>',
        '</div>',
        '<div style="text-align: center; margin-top: 30px; color: #666; font-size: 14px;">',
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

-- Fonction pour confirmer un email
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

-- Fonction pour traiter la file d'attente des emails
CREATE OR REPLACE FUNCTION process_email_queue()
RETURNS INTEGER
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_email_record RECORD;
    v_processed_count INTEGER := 0;
    v_success BOOLEAN;
BEGIN
    -- Traiter les emails en attente
    FOR v_email_record IN 
        SELECT * FROM email_queue 
        WHERE status = 'pending' 
        AND created_at > NOW() - INTERVAL '24 hours'
        ORDER BY created_at ASC
        LIMIT 50
    LOOP
        BEGIN
            -- Ici, vous intégreriez votre service d'envoi d'email
            -- Pour l'instant, on simule l'envoi
            v_success := TRUE;
            
            IF v_success THEN
                -- Marquer comme envoyé
                UPDATE email_queue
                SET 
                    status = 'sent',
                    sent_at = NOW(),
                    attempts = attempts + 1
                WHERE id = v_email_record.id;
                
                v_processed_count := v_processed_count + 1;
            ELSE
                -- Marquer comme échec
                UPDATE email_queue
                SET 
                    status = 'failed',
                    attempts = attempts + 1,
                    error_message = 'Échec de l''envoi'
                WHERE id = v_email_record.id;
            END IF;
            
        EXCEPTION
            WHEN OTHERS THEN
                -- Marquer comme échec en cas d'erreur
                UPDATE email_queue
                SET 
                    status = 'failed',
                    attempts = attempts + 1,
                    error_message = SQLERRM
                WHERE id = v_email_record.id;
        END;
    END LOOP;
    
    RETURN v_processed_count;
END;
$$;

-- Fonction pour nettoyer les anciens emails
CREATE OR REPLACE FUNCTION cleanup_old_emails()
RETURNS INTEGER
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_deleted_count INTEGER;
BEGIN
    -- Supprimer les emails envoyés de plus de 30 jours
    DELETE FROM email_queue
    WHERE status = 'sent' 
    AND sent_at < NOW() - INTERVAL '30 days';
    
    GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
    
    -- Supprimer les emails en échec de plus de 7 jours
    DELETE FROM email_queue
    WHERE status = 'failed' 
    AND created_at < NOW() - INTERVAL '7 days';
    
    GET DIAGNOSTICS v_deleted_count = v_deleted_count + ROW_COUNT;
    
    RETURN v_deleted_count;
END;
$$;

-- Fonction pour obtenir les statistiques des emails
CREATE OR REPLACE FUNCTION get_email_stats()
RETURNS TABLE(
    total_emails BIGINT,
    pending_emails BIGINT,
    sent_emails BIGINT,
    failed_emails BIGINT,
    success_rate NUMERIC
)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(*) as total_emails,
        COUNT(*) FILTER (WHERE status = 'pending') as pending_emails,
        COUNT(*) FILTER (WHERE status = 'sent') as sent_emails,
        COUNT(*) FILTER (WHERE status = 'failed') as failed_emails,
        CASE 
            WHEN COUNT(*) > 0 THEN 
                ROUND(
                    (COUNT(*) FILTER (WHERE status = 'sent')::NUMERIC / COUNT(*)::NUMERIC) * 100, 
                    2
                )
            ELSE 0 
        END as success_rate
    FROM email_queue
    WHERE created_at > NOW() - INTERVAL '30 days';
END;
$$;

-- Fonction pour renvoyer un email de confirmation
CREATE OR REPLACE FUNCTION resend_confirmation_email(
    p_user_id UUID
)
RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_email TEXT;
    v_token TEXT;
    v_user_name TEXT;
BEGIN
    -- Récupérer les informations de l'utilisateur
    SELECT email, CONCAT(prenom, ' ', nom) INTO v_email, v_user_name
    FROM profiles
    WHERE id = p_user_id;
    
    IF v_email IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- Générer un nouveau token
    v_token := encode(gen_random_bytes(32), 'hex');
    
    -- Mettre à jour le token dans la table auth
    UPDATE auth.users
    SET raw_app_meta_data = raw_app_meta_data || 
        jsonb_build_object('confirmation_token', v_token)
    WHERE id = p_user_id;
    
    -- Envoyer l'email de confirmation
    RETURN send_confirmation_email(p_user_id, v_email, v_token);
EXCEPTION
    WHEN OTHERS THEN
        RAISE LOG 'Erreur lors du renvoi de l''email de confirmation: %', SQLERRM;
        RETURN FALSE;
END;
$$;

-- Fonction pour vérifier le statut de confirmation d'un utilisateur
CREATE OR REPLACE FUNCTION check_email_confirmation_status(
    p_user_id UUID
)
RETURNS TABLE(
    is_confirmed BOOLEAN,
    confirmation_date TIMESTAMPTZ,
    email TEXT,
    status TEXT
)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.email_confirme as is_confirmed,
        p.date_confirmation as confirmation_date,
        p.email,
        p.statut as status
    FROM profiles p
    WHERE p.id = p_user_id;
END;
$$;

-- Créer un trigger pour envoyer automatiquement l'email de confirmation
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

-- Créer le trigger
DROP TRIGGER IF EXISTS send_confirmation_email_trigger ON profiles;
CREATE TRIGGER send_confirmation_email_trigger
    AFTER INSERT ON profiles
    FOR EACH ROW
    WHEN (NEW.email_confirme = FALSE)
    EXECUTE FUNCTION trigger_send_confirmation_email();

-- Fonction pour obtenir les emails en attente d'un utilisateur
CREATE OR REPLACE FUNCTION get_user_pending_emails(
    p_user_id UUID
)
RETURNS TABLE(
    id BIGINT,
    email_type TEXT,
    subject TEXT,
    created_at TIMESTAMPTZ,
    status TEXT,
    attempts INTEGER
)
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    RETURN QUERY
    SELECT 
        eq.id,
        eq.email_type,
        eq.subject,
        eq.created_at,
        eq.status,
        eq.attempts
    FROM email_queue eq
    WHERE eq.user_id = p_user_id
    AND eq.status IN ('pending', 'failed')
    ORDER BY eq.created_at DESC;
END;
$$;

-- Fonction pour marquer un email comme lu
CREATE OR REPLACE FUNCTION mark_email_as_read(
    p_email_id BIGINT
)
RETURNS BOOLEAN
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
BEGIN
    UPDATE email_queue
    SET 
        status = 'read',
        read_at = NOW()
    WHERE id = p_email_id;
    
    RETURN FOUND;
END;
$$;

-- Commentaires sur les fonctions
COMMENT ON FUNCTION send_confirmation_email IS 'Envoie un email de confirmation à un utilisateur';
COMMENT ON FUNCTION send_password_reset_email IS 'Envoie un email de réinitialisation de mot de passe';
COMMENT ON FUNCTION confirm_user_email IS 'Confirme l''email d''un utilisateur';
COMMENT ON FUNCTION process_email_queue IS 'Traite la file d''attente des emails';
COMMENT ON FUNCTION cleanup_old_emails IS 'Nettoie les anciens emails de la base de données';
COMMENT ON FUNCTION get_email_stats IS 'Retourne les statistiques des emails';
COMMENT ON FUNCTION resend_confirmation_email IS 'Renvoye un email de confirmation';
COMMENT ON FUNCTION check_email_confirmation_status IS 'Vérifie le statut de confirmation d''un utilisateur';
COMMENT ON FUNCTION get_user_pending_emails IS 'Récupère les emails en attente d''un utilisateur';
COMMENT ON FUNCTION mark_email_as_read IS 'Marque un email comme lu'; 