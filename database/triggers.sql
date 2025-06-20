-- Triggers supplémentaires pour automatiser les processus
-- À exécuter après les fonctions

-- Trigger pour valider les données avant insertion
CREATE OR REPLACE FUNCTION validate_delivery_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Valider les adresses
    IF NOT validate_address(NEW.pickup_address) THEN
        RAISE EXCEPTION 'Adresse de ramassage invalide';
    END IF;
    
    IF NOT validate_address(NEW.delivery_address) THEN
        RAISE EXCEPTION 'Adresse de livraison invalide';
    END IF;
    
    -- Vérifier que le client et le livreur ne sont pas la même personne
    IF NEW.client_id = NEW.courier_id THEN
        RAISE EXCEPTION 'Le client et le livreur ne peuvent pas être la même personne';
    END IF;
    
    -- Vérifier que le prix est positif
    IF NEW.base_price <= 0 THEN
        RAISE EXCEPTION 'Le prix de base doit être positif';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_delivery_data
    BEFORE INSERT OR UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION validate_delivery_data();

-- Trigger pour valider les profils
CREATE OR REPLACE FUNCTION validate_profile_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Valider l'email
    IF NEW.email !~ '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$' THEN
        RAISE EXCEPTION 'Adresse email invalide';
    END IF;
    
    -- Valider le téléphone
    IF NEW.telephone !~ '^(\+221|221)?[0-9]{9}$' THEN
        RAISE EXCEPTION 'Numéro de téléphone invalide';
    END IF;
    
    -- Formater le téléphone
    NEW.telephone := format_phone_number(NEW.telephone);
    
    -- Vérifier que le rôle est valide
    IF NEW.role NOT IN ('client', 'livreur', 'admin') THEN
        RAISE EXCEPTION 'Rôle invalide';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_profile_data
    BEFORE INSERT OR UPDATE ON profiles
    FOR EACH ROW EXECUTE FUNCTION validate_profile_data();

-- Trigger pour créer un paiement automatique quand une livraison est livrée
CREATE OR REPLACE FUNCTION create_automatic_payment()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'delivered' AND OLD.status != 'delivered' THEN
        -- Créer un paiement automatique
        INSERT INTO payments (
            delivery_id, 
            sender_id, 
            receiver_id, 
            amount, 
            status
        ) VALUES (
            NEW.id,
            NEW.client_id,
            NEW.courier_id,
            COALESCE(NEW.final_price, NEW.base_price),
            'pending'
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_create_automatic_payment
    AFTER UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION create_automatic_payment();

-- Trigger pour marquer les messages comme lus
CREATE OR REPLACE FUNCTION mark_messages_as_read()
RETURNS TRIGGER AS $$
BEGIN
    -- Marquer tous les messages de cette livraison comme lus pour l'utilisateur
    UPDATE messages 
    SET is_read = true, read_at = NOW()
    WHERE delivery_id = NEW.delivery_id 
    AND sender_id != NEW.sender_id
    AND is_read = false;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_mark_messages_as_read
    AFTER INSERT ON messages
    FOR EACH ROW EXECUTE FUNCTION mark_messages_as_read();

-- Trigger pour mettre à jour le timestamp de dernière activité
CREATE OR REPLACE FUNCTION update_last_activity()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Appliquer ce trigger à toutes les tables importantes
CREATE TRIGGER trigger_update_profiles_activity
    BEFORE UPDATE ON profiles
    FOR EACH ROW EXECUTE FUNCTION update_last_activity();

CREATE TRIGGER trigger_update_deliveries_activity
    BEFORE UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION update_last_activity();

CREATE TRIGGER trigger_update_payments_activity
    BEFORE UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION update_last_activity();

-- Trigger pour empêcher la suppression de données importantes
CREATE OR REPLACE FUNCTION prevent_critical_deletion()
RETURNS TRIGGER AS $$
BEGIN
    RAISE EXCEPTION 'Suppression non autorisée pour des raisons de sécurité';
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Empêcher la suppression de profils actifs
CREATE TRIGGER trigger_prevent_profile_deletion
    BEFORE DELETE ON profiles
    FOR EACH ROW 
    WHEN (OLD.is_active = true)
    EXECUTE FUNCTION prevent_critical_deletion();

-- Trigger pour archiver les livraisons anciennes
CREATE OR REPLACE FUNCTION archive_old_deliveries()
RETURNS TRIGGER AS $$
BEGIN
    -- Si une livraison a plus d'un an et est terminée, la marquer comme archivée
    IF OLD.status IN ('delivered', 'cancelled') 
       AND OLD.created_at < NOW() - INTERVAL '1 year' THEN
        -- Ici vous pourriez insérer dans une table d'archivage
        -- INSERT INTO deliveries_archive SELECT * FROM deliveries WHERE id = OLD.id;
        RAISE NOTICE 'Livraison % archivée automatiquement', OLD.id;
    END IF;
    
    RETURN OLD;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour la gestion des sessions
CREATE OR REPLACE FUNCTION track_user_sessions()
RETURNS TRIGGER AS $$
BEGIN
    -- Mettre à jour la dernière connexion
    IF TG_OP = 'UPDATE' THEN
        NEW.last_login = NOW();
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour la validation des paiements
CREATE OR REPLACE FUNCTION validate_payment_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Vérifier que le montant est positif
    IF NEW.amount <= 0 THEN
        RAISE EXCEPTION 'Le montant du paiement doit être positif';
    END IF;
    
    -- Vérifier que l'expéditeur et le destinataire sont différents
    IF NEW.sender_id = NEW.receiver_id THEN
        RAISE EXCEPTION 'L''expéditeur et le destinataire ne peuvent pas être identiques';
    END IF;
    
    -- Vérifier que la livraison existe et est terminée
    IF NOT EXISTS (
        SELECT 1 FROM deliveries 
        WHERE id = NEW.delivery_id 
        AND status = 'delivered'
    ) THEN
        RAISE EXCEPTION 'Le paiement ne peut être effectué que pour une livraison terminée';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_payment_data
    BEFORE INSERT OR UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION validate_payment_data();

-- Trigger pour la gestion des notifications de paiement
CREATE OR REPLACE FUNCTION notify_payment_status()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.status = 'completed' AND OLD.status != 'completed' THEN
        -- Notification pour le destinataire
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.receiver_id,
            'Paiement reçu',
            'Vous avez reçu un paiement de ' || NEW.amount || ' ' || NEW.currency,
            'success'
        );
        
        -- Notification pour l'expéditeur
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.sender_id,
            'Paiement effectué',
            'Votre paiement de ' || NEW.amount || ' ' || NEW.currency || ' a été traité',
            'success'
        );
    ELSIF NEW.status = 'failed' AND OLD.status != 'failed' THEN
        -- Notification d'échec
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.sender_id,
            'Échec du paiement',
            'Le paiement de ' || NEW.amount || ' ' || NEW.currency || ' a échoué',
            'error'
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_notify_payment_status
    AFTER UPDATE ON payments
    FOR EACH ROW EXECUTE FUNCTION notify_payment_status();

-- Trigger pour la gestion des évaluations
CREATE OR REPLACE FUNCTION validate_review_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Vérifier que la note est entre 1 et 5
    IF NEW.rating < 1 OR NEW.rating > 5 THEN
        RAISE EXCEPTION 'La note doit être comprise entre 1 et 5';
    END IF;
    
    -- Vérifier que l'évaluateur et l'évalué sont différents
    IF NEW.reviewer_id = NEW.reviewed_user_id THEN
        RAISE EXCEPTION 'Un utilisateur ne peut pas s''évaluer lui-même';
    END IF;
    
    -- Vérifier que l'évaluateur a participé à la livraison
    IF NOT EXISTS (
        SELECT 1 FROM deliveries 
        WHERE id = NEW.delivery_id 
        AND (client_id = NEW.reviewer_id OR courier_id = NEW.reviewer_id)
    ) THEN
        RAISE EXCEPTION 'Vous ne pouvez évaluer que les participants à cette livraison';
    END IF;
    
    -- Vérifier qu'il n'y a qu'une seule évaluation par livraison par utilisateur
    IF EXISTS (
        SELECT 1 FROM reviews 
        WHERE delivery_id = NEW.delivery_id 
        AND reviewer_id = NEW.reviewer_id
        AND id != COALESCE(NEW.id, '00000000-0000-0000-0000-000000000000'::UUID)
    ) THEN
        RAISE EXCEPTION 'Vous avez déjà évalué cette livraison';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_review_data
    BEFORE INSERT OR UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION validate_review_data();

-- Trigger pour la gestion des zones de couverture
CREATE OR REPLACE FUNCTION validate_coverage_zone()
RETURNS TRIGGER AS $$
BEGIN
    -- Vérifier que le nom de la zone n'est pas vide
    IF LENGTH(TRIM(NEW.name)) < 2 THEN
        RAISE EXCEPTION 'Le nom de la zone doit contenir au moins 2 caractères';
    END IF;
    
    -- Vérifier que la ville n'est pas vide
    IF LENGTH(TRIM(NEW.city)) < 2 THEN
        RAISE EXCEPTION 'Le nom de la ville doit contenir au moins 2 caractères';
    END IF;
    
    -- Vérifier l'unicité du nom dans la même ville
    IF EXISTS (
        SELECT 1 FROM coverage_zones 
        WHERE name = NEW.name 
        AND city = NEW.city
        AND id != COALESCE(NEW.id, '00000000-0000-0000-0000-000000000000'::UUID)
    ) THEN
        RAISE EXCEPTION 'Une zone avec ce nom existe déjà dans cette ville';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_coverage_zone
    BEFORE INSERT OR UPDATE ON coverage_zones
    FOR EACH ROW EXECUTE FUNCTION validate_coverage_zone();

-- ========================================
-- TRIGGERS POUR LES QR CODES
-- ========================================

-- Trigger pour mettre à jour updated_at automatiquement
CREATE OR REPLACE FUNCTION update_qr_codes_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_qr_codes_updated_at
    BEFORE UPDATE ON qr_codes
    FOR EACH ROW
    EXECUTE FUNCTION update_qr_codes_updated_at();

-- Trigger pour valider les données des QR codes
CREATE OR REPLACE FUNCTION validate_qr_code_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Vérifier que le contenu n'est pas vide
    IF NEW.content IS NULL OR LENGTH(TRIM(NEW.content)) = 0 THEN
        RAISE EXCEPTION 'Le contenu du QR code ne peut pas être vide';
    END IF;
    
    -- Vérifier que le titre n'est pas vide
    IF NEW.title IS NULL OR LENGTH(TRIM(NEW.title)) = 0 THEN
        RAISE EXCEPTION 'Le titre du QR code ne peut pas être vide';
    END IF;
    
    -- Vérifier que le type est valide
    IF NEW.type NOT IN ('delivery', 'user', 'payment', 'location', 'custom') THEN
        RAISE EXCEPTION 'Type de QR code invalide: %', NEW.type;
    END IF;
    
    -- Vérifier que le scan_count n'est pas négatif
    IF NEW.scan_count < 0 THEN
        RAISE EXCEPTION 'Le nombre de scans ne peut pas être négatif';
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_validate_qr_code_data
    BEFORE INSERT OR UPDATE ON qr_codes
    FOR EACH ROW
    EXECUTE FUNCTION validate_qr_code_data();

-- Trigger pour créer un audit trail des QR codes
CREATE OR REPLACE FUNCTION audit_qr_code_changes()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        INSERT INTO audit_logs (
            table_name, record_id, action, old_data, new_data, user_id
        ) VALUES (
            'qr_codes', NEW.id, 'INSERT', NULL, to_jsonb(NEW), NEW.user_id
        );
        RETURN NEW;
    ELSIF TG_OP = 'UPDATE' THEN
        INSERT INTO audit_logs (
            table_name, record_id, action, old_data, new_data, user_id
        ) VALUES (
            'qr_codes', NEW.id, 'UPDATE', to_jsonb(OLD), to_jsonb(NEW), NEW.user_id
        );
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        INSERT INTO audit_logs (
            table_name, record_id, action, old_data, new_data, user_id
        ) VALUES (
            'qr_codes', OLD.id, 'DELETE', to_jsonb(OLD), NULL, OLD.user_id
        );
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_audit_qr_code_changes
    AFTER INSERT OR UPDATE OR DELETE ON qr_codes
    FOR EACH ROW
    EXECUTE FUNCTION audit_qr_code_changes();

-- Trigger pour mettre à jour les statistiques utilisateur lors de la création/suppression de QR codes
CREATE OR REPLACE FUNCTION update_user_qr_stats()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Incrémenter le compteur de QR codes de l'utilisateur
        UPDATE profiles 
        SET qr_codes_count = COALESCE(qr_codes_count, 0) + 1
        WHERE id = NEW.user_id;
        RETURN NEW;
    ELSIF TG_OP = 'DELETE' THEN
        -- Décrémenter le compteur de QR codes de l'utilisateur
        UPDATE profiles 
        SET qr_codes_count = GREATEST(COALESCE(qr_codes_count, 0) - 1, 0)
        WHERE id = OLD.user_id;
        RETURN OLD;
    END IF;
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_update_user_qr_stats
    AFTER INSERT OR DELETE ON qr_codes
    FOR EACH ROW
    EXECUTE FUNCTION update_user_qr_stats();

-- Trigger pour nettoyer automatiquement les anciens QR codes
CREATE OR REPLACE FUNCTION auto_cleanup_old_qr_codes()
RETURNS TRIGGER AS $$
BEGIN
    -- Nettoyer les QR codes de plus de 90 jours qui ne sont pas favoris et n'ont jamais été scannés
    DELETE FROM qr_codes 
    WHERE created_at < NOW() - INTERVAL '90 days'
    AND is_favorite = FALSE
    AND scan_count = 0;
    
    RETURN NULL;
END;
$$ LANGUAGE plpgsql;

-- Ce trigger s'exécute tous les jours à 2h du matin
-- Note: Dans un environnement de production, il est préférable d'utiliser un cron job
CREATE TRIGGER trigger_auto_cleanup_old_qr_codes
    AFTER INSERT ON qr_codes
    FOR EACH ROW
    WHEN (EXTRACT(HOUR FROM NOW()) = 2)
    EXECUTE FUNCTION auto_cleanup_old_qr_codes(); 