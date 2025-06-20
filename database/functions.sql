-- Fonctions SQL supplémentaires pour LivraisonP2P
-- À exécuter dans l'éditeur SQL de Supabase après le schéma principal

-- Fonction pour calculer automatiquement le prix d'une livraison
CREATE OR REPLACE FUNCTION calculate_delivery_price_auto()
RETURNS TRIGGER AS $$
DECLARE
    distance_km DECIMAL;
    base_price DECIMAL;
    urgent_multiplier DECIMAL;
    night_multiplier DECIMAL;
    weekend_multiplier DECIMAL;
    final_price DECIMAL;
    current_hour INTEGER;
    current_day INTEGER;
BEGIN
    -- Calculer la distance si les coordonnées sont fournies
    IF NEW.pickup_latitude IS NOT NULL AND NEW.pickup_longitude IS NOT NULL 
       AND NEW.delivery_latitude IS NOT NULL AND NEW.delivery_longitude IS NOT NULL THEN
        distance_km := calculate_distance(
            NEW.pickup_latitude, NEW.pickup_longitude,
            NEW.delivery_latitude, NEW.delivery_longitude
        );
        NEW.estimated_distance := distance_km;
    END IF;

    -- Récupérer les paramètres de prix
    SELECT base_price_per_km, urgent_multiplier, night_multiplier, weekend_multiplier 
    INTO base_price, urgent_multiplier, night_multiplier, weekend_multiplier
    FROM pricing_settings
    ORDER BY created_at DESC
    LIMIT 1;

    -- Prix de base
    final_price := COALESCE(NEW.estimated_distance, 1) * COALESCE(base_price, 100.0);

    -- Appliquer les multiplicateurs
    IF NEW.priority = 'urgent' THEN
        final_price := final_price * COALESCE(urgent_multiplier, 1.5);
    END IF;

    -- Multiplicateur de nuit (22h-6h)
    current_hour := EXTRACT(HOUR FROM NOW());
    IF current_hour >= 22 OR current_hour <= 6 THEN
        final_price := final_price * COALESCE(night_multiplier, 1.2);
    END IF;

    -- Multiplicateur de weekend
    current_day := EXTRACT(DOW FROM NOW());
    IF current_day = 0 OR current_day = 6 THEN -- Dimanche = 0, Samedi = 6
        final_price := final_price * COALESCE(weekend_multiplier, 1.1);
    END IF;

    -- Prix minimum
    IF final_price < 500.0 THEN
        final_price := 500.0;
    END IF;

    NEW.final_price := final_price;
    NEW.base_price := COALESCE(NEW.estimated_distance, 1) * COALESCE(base_price, 100.0);

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour calculer automatiquement le prix
CREATE TRIGGER trigger_calculate_delivery_price
    BEFORE INSERT OR UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION calculate_delivery_price_auto();

-- Fonction pour mettre à jour les statistiques utilisateur
CREATE OR REPLACE FUNCTION update_user_stats()
RETURNS TRIGGER AS $$
BEGIN
    IF TG_OP = 'INSERT' THEN
        -- Mettre à jour les statistiques du client
        UPDATE profiles 
        SET total_deliveries = total_deliveries + 1
        WHERE id = NEW.client_id;
    ELSIF TG_OP = 'UPDATE' THEN
        -- Si le statut passe à 'delivered', mettre à jour les gains du livreur
        IF NEW.status = 'delivered' AND OLD.status != 'delivered' AND NEW.courier_id IS NOT NULL THEN
            UPDATE profiles 
            SET total_earnings = total_earnings + COALESCE(NEW.final_price, NEW.base_price)
            WHERE id = NEW.courier_id;
        END IF;
    END IF;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql;

-- Trigger pour mettre à jour les statistiques
CREATE TRIGGER trigger_update_user_stats
    AFTER INSERT OR UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION update_user_stats();

-- Fonction pour calculer la note moyenne d'un utilisateur
CREATE OR REPLACE FUNCTION update_user_rating()
RETURNS TRIGGER AS $$
DECLARE
    avg_rating DECIMAL;
BEGIN
    -- Calculer la note moyenne
    SELECT AVG(rating) INTO avg_rating
    FROM reviews
    WHERE reviewed_user_id = NEW.reviewed_user_id;
    
    -- Mettre à jour la note dans le profil
    UPDATE profiles 
    SET rating = COALESCE(avg_rating, 0.0)
    WHERE id = NEW.reviewed_user_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour mettre à jour la note
CREATE TRIGGER trigger_update_user_rating
    AFTER INSERT OR UPDATE ON reviews
    FOR EACH ROW EXECUTE FUNCTION update_user_rating();

-- Fonction pour créer une notification automatique
CREATE OR REPLACE FUNCTION create_delivery_notification()
RETURNS TRIGGER AS $$
BEGIN
    -- Notification pour le client quand une livraison est acceptée
    IF NEW.status = 'accepted' AND OLD.status = 'pending' THEN
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.client_id,
            'Livraison acceptée',
            'Un livreur a accepté votre livraison. Vous recevrez bientôt un message.',
            'success'
        );
        
        -- Notification pour le livreur
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.courier_id,
            'Nouvelle livraison',
            'Vous avez accepté une nouvelle livraison. Contactez le client pour les détails.',
            'info'
        );
    END IF;
    
    -- Notification quand le statut change
    IF NEW.status != OLD.status AND NEW.status IN ('picked_up', 'in_transit', 'delivered') THEN
        INSERT INTO notifications (user_id, title, message, type)
        VALUES (
            NEW.client_id,
            'Statut mis à jour',
            'Votre livraison est maintenant ' || 
            CASE NEW.status 
                WHEN 'picked_up' THEN 'récupérée'
                WHEN 'in_transit' THEN 'en cours de livraison'
                WHEN 'delivered' THEN 'livrée'
            END,
            'info'
        );
    END IF;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour les notifications automatiques
CREATE TRIGGER trigger_create_delivery_notification
    AFTER UPDATE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION create_delivery_notification();

-- Fonction pour nettoyer les anciennes données
CREATE OR REPLACE FUNCTION cleanup_old_data()
RETURNS void AS $$
BEGIN
    -- Supprimer les notifications lues de plus de 30 jours
    DELETE FROM notifications 
    WHERE is_read = true AND created_at < NOW() - INTERVAL '30 days';
    
    -- Supprimer les localisations de plus de 7 jours
    DELETE FROM user_locations 
    WHERE updated_at < NOW() - INTERVAL '7 days';
    
    -- Archiver les livraisons terminées de plus de 1 an
    -- (Vous pouvez créer une table d'archivage si nécessaire)
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir les statistiques d'un utilisateur
CREATE OR REPLACE FUNCTION get_user_stats(user_uuid UUID)
RETURNS TABLE (
    total_deliveries INTEGER,
    total_earnings DECIMAL,
    average_rating DECIMAL,
    completed_deliveries INTEGER,
    pending_deliveries INTEGER,
    total_reviews INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.total_deliveries,
        p.total_earnings,
        p.rating,
        COUNT(CASE WHEN d.status = 'delivered' THEN 1 END)::INTEGER as completed_deliveries,
        COUNT(CASE WHEN d.status IN ('pending', 'accepted', 'picked_up', 'in_transit') THEN 1 END)::INTEGER as pending_deliveries,
        COUNT(r.id)::INTEGER as total_reviews
    FROM profiles p
    LEFT JOIN deliveries d ON (p.id = d.client_id OR p.id = d.courier_id)
    LEFT JOIN reviews r ON p.id = r.reviewed_user_id
    WHERE p.id = user_uuid
    GROUP BY p.total_deliveries, p.total_earnings, p.rating;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour rechercher des livreurs disponibles
CREATE OR REPLACE FUNCTION find_available_couriers(
    pickup_lat DECIMAL,
    pickup_lon DECIMAL,
    max_distance DECIMAL DEFAULT 10.0
)
RETURNS TABLE (
    courier_id UUID,
    courier_name TEXT,
    distance DECIMAL,
    rating DECIMAL,
    total_deliveries INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id as courier_id,
        p.prenom || ' ' || p.nom as courier_name,
        calculate_distance(pickup_lat, pickup_lon, ul.latitude, ul.longitude) as distance,
        p.rating,
        p.total_deliveries
    FROM profiles p
    JOIN user_locations ul ON p.id = ul.user_id
    WHERE p.role = 'livreur'
    AND p.is_active = true
    AND calculate_distance(pickup_lat, pickup_lon, ul.latitude, ul.longitude) <= max_distance
    AND ul.updated_at > NOW() - INTERVAL '1 hour'
    ORDER BY distance, p.rating DESC;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir l'historique des livraisons
CREATE OR REPLACE FUNCTION get_delivery_history(
    user_uuid UUID,
    limit_count INTEGER DEFAULT 20,
    offset_count INTEGER DEFAULT 0
)
RETURNS TABLE (
    delivery_id UUID,
    pickup_address TEXT,
    delivery_address TEXT,
    status TEXT,
    final_price DECIMAL,
    created_at TIMESTAMP WITH TIME ZONE,
    delivered_at TIMESTAMP WITH TIME ZONE,
    courier_name TEXT,
    client_name TEXT
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        d.id,
        d.pickup_address,
        d.delivery_address,
        d.status,
        d.final_price,
        d.created_at,
        d.delivered_at,
        CASE 
            WHEN d.courier_id IS NOT NULL THEN c.prenom || ' ' || c.nom
            ELSE 'Non assigné'
        END as courier_name,
        cl.prenom || ' ' || cl.nom as client_name
    FROM deliveries d
    JOIN profiles cl ON d.client_id = cl.id
    LEFT JOIN profiles c ON d.courier_id = c.id
    WHERE d.client_id = user_uuid OR d.courier_id = user_uuid
    ORDER BY d.created_at DESC
    LIMIT limit_count
    OFFSET offset_count;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour calculer les revenus par période
CREATE OR REPLACE FUNCTION get_earnings_by_period(
    user_uuid UUID,
    period_start DATE,
    period_end DATE
)
RETURNS TABLE (
    period_date DATE,
    total_earnings DECIMAL,
    delivery_count INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        DATE(d.delivered_at) as period_date,
        SUM(d.final_price) as total_earnings,
        COUNT(*) as delivery_count
    FROM deliveries d
    WHERE d.courier_id = user_uuid
    AND d.status = 'delivered'
    AND DATE(d.delivered_at) BETWEEN period_start AND period_end
    GROUP BY DATE(d.delivered_at)
    ORDER BY period_date;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour valider une adresse
CREATE OR REPLACE FUNCTION validate_address(address_text TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    -- Validation basique : l'adresse doit contenir au moins 10 caractères
    -- et ne pas être vide
    RETURN LENGTH(TRIM(address_text)) >= 10 AND address_text IS NOT NULL;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour formater un numéro de téléphone
CREATE OR REPLACE FUNCTION format_phone_number(phone TEXT)
RETURNS TEXT AS $$
DECLARE
    cleaned_phone TEXT;
BEGIN
    -- Supprimer tous les caractères non numériques
    cleaned_phone := REGEXP_REPLACE(phone, '[^0-9]', '', 'g');
    
    -- Formater selon le format sénégalais
    IF LENGTH(cleaned_phone) = 9 THEN
        RETURN '+221 ' || SUBSTRING(cleaned_phone, 1, 2) || ' ' || 
               SUBSTRING(cleaned_phone, 3, 3) || ' ' || 
               SUBSTRING(cleaned_phone, 6, 2) || ' ' || 
               SUBSTRING(cleaned_phone, 8, 2);
    ELSIF LENGTH(cleaned_phone) = 12 AND SUBSTRING(cleaned_phone, 1, 3) = '221' THEN
        RETURN '+221 ' || SUBSTRING(cleaned_phone, 4, 2) || ' ' || 
               SUBSTRING(cleaned_phone, 6, 3) || ' ' || 
               SUBSTRING(cleaned_phone, 9, 2) || ' ' || 
               SUBSTRING(cleaned_phone, 11, 2);
    ELSE
        RETURN phone; -- Retourner tel quel si le format n'est pas reconnu
    END IF;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir les statistiques globales (admin)
CREATE OR REPLACE FUNCTION get_global_stats()
RETURNS TABLE (
    total_users INTEGER,
    total_deliveries INTEGER,
    total_earnings DECIMAL,
    active_couriers INTEGER,
    pending_deliveries INTEGER,
    completed_deliveries INTEGER
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        COUNT(p.id)::INTEGER as total_users,
        COUNT(d.id)::INTEGER as total_deliveries,
        SUM(COALESCE(d.final_price, 0)) as total_earnings,
        COUNT(CASE WHEN p.role = 'livreur' AND p.is_active = true THEN 1 END)::INTEGER as active_couriers,
        COUNT(CASE WHEN d.status IN ('pending', 'accepted') THEN 1 END)::INTEGER as pending_deliveries,
        COUNT(CASE WHEN d.status = 'delivered' THEN 1 END)::INTEGER as completed_deliveries
    FROM profiles p
    LEFT JOIN deliveries d ON p.id = d.client_id OR p.id = d.courier_id;
END;
$$ LANGUAGE plpgsql;

-- Créer un index pour améliorer les performances des recherches géographiques
CREATE INDEX IF NOT EXISTS idx_user_locations_coordinates 
ON user_locations USING GIST (ll_to_earth(latitude, longitude));

-- Index pour les recherches temporelles
CREATE INDEX IF NOT EXISTS idx_deliveries_delivered_at 
ON deliveries(delivered_at) WHERE status = 'delivered';

CREATE INDEX IF NOT EXISTS idx_notifications_created_at 
ON notifications(created_at);

-- Index pour les recherches de texte
CREATE INDEX IF NOT EXISTS idx_profiles_search 
ON profiles USING GIN (to_tsvector('french', prenom || ' ' || nom || ' ' || COALESCE(bio, '')));

-- Fonction pour créer un job de nettoyage automatique (à exécuter périodiquement)
CREATE OR REPLACE FUNCTION schedule_cleanup()
RETURNS void AS $$
BEGIN
    -- Cette fonction peut être appelée par un cron job ou un trigger
    PERFORM cleanup_old_data();
END;
$$ LANGUAGE plpgsql;

-- ========================================
-- FONCTIONS POUR LES QR CODES
-- ========================================

-- Fonction pour créer un QR code
CREATE OR REPLACE FUNCTION create_qr_code(
    p_user_id UUID,
    p_content TEXT,
    p_qr_code_data TEXT,
    p_type VARCHAR(50) DEFAULT 'custom',
    p_title VARCHAR(255),
    p_description TEXT DEFAULT NULL,
    p_metadata JSONB DEFAULT NULL
) RETURNS qr_codes AS $$
DECLARE
    v_qr_code qr_codes;
BEGIN
    INSERT INTO qr_codes (
        user_id, content, qr_code_data, type, title, description, metadata
    ) VALUES (
        p_user_id, p_content, p_qr_code_data, p_type, p_title, p_description, p_metadata
    ) RETURNING * INTO v_qr_code;
    
    -- Créer une notification
    PERFORM create_notification(
        p_user_id,
        'qr_code_created',
        'QR Code créé',
        'Votre QR code "' || p_title || '" a été créé avec succès'
    );
    
    RETURN v_qr_code;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour incrémenter le compteur de scan
CREATE OR REPLACE FUNCTION increment_qr_scan_count(p_qr_id UUID) RETURNS VOID AS $$
BEGIN
    UPDATE qr_codes 
    SET scan_count = scan_count + 1,
        last_scanned_at = NOW(),
        updated_at = NOW()
    WHERE id = p_qr_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour marquer un QR code comme favori
CREATE OR REPLACE FUNCTION toggle_qr_favorite(p_qr_id UUID, p_user_id UUID) RETURNS BOOLEAN AS $$
DECLARE
    v_is_favorite BOOLEAN;
BEGIN
    UPDATE qr_codes 
    SET is_favorite = NOT is_favorite,
        updated_at = NOW()
    WHERE id = p_qr_id AND user_id = p_user_id
    RETURNING is_favorite INTO v_is_favorite;
    
    RETURN COALESCE(v_is_favorite, FALSE);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour obtenir les statistiques des QR codes
CREATE OR REPLACE FUNCTION get_qr_code_stats(p_user_id UUID) RETURNS JSON AS $$
DECLARE
    v_stats JSON;
BEGIN
    SELECT json_build_object(
        'total_qr_codes', COUNT(*),
        'favorite_qr_codes', COUNT(*) FILTER (WHERE is_favorite = TRUE),
        'total_scans', COALESCE(SUM(scan_count), 0),
        'types_distribution', json_object_agg(type, count) FILTER (WHERE type IS NOT NULL),
        'recent_activity', COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '7 days')
    ) INTO v_stats
    FROM qr_codes 
    WHERE user_id = p_user_id;
    
    RETURN v_stats;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour nettoyer les anciens QR codes
CREATE OR REPLACE FUNCTION cleanup_old_qr_codes(p_days INTEGER DEFAULT 90) RETURNS INTEGER AS $$
DECLARE
    v_deleted_count INTEGER;
BEGIN
    DELETE FROM qr_codes 
    WHERE created_at < NOW() - (p_days || ' days')::INTERVAL
    AND is_favorite = FALSE
    AND scan_count = 0;
    
    GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
    
    RETURN v_deleted_count;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour exporter les QR codes d'un utilisateur
CREATE OR REPLACE FUNCTION export_user_qr_codes(p_user_id UUID) RETURNS JSON AS $$
DECLARE
    v_qr_codes JSON;
BEGIN
    SELECT json_agg(
        json_build_object(
            'id', id,
            'type', type,
            'title', title,
            'description', description,
            'content', content,
            'qr_code_data', qr_code_data,
            'is_favorite', is_favorite,
            'scan_count', scan_count,
            'created_at', created_at,
            'metadata', metadata
        )
    ) INTO v_qr_codes
    FROM qr_codes 
    WHERE user_id = p_user_id
    ORDER BY created_at DESC;
    
    RETURN v_qr_codes;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour rechercher dans les QR codes
CREATE OR REPLACE FUNCTION search_qr_codes(
    p_user_id UUID,
    p_search_term TEXT,
    p_type VARCHAR(50) DEFAULT NULL
) RETURNS SETOF qr_codes AS $$
BEGIN
    RETURN QUERY
    SELECT * FROM qr_codes 
    WHERE user_id = p_user_id
    AND (
        title ILIKE '%' || p_search_term || '%' OR
        description ILIKE '%' || p_search_term || '%' OR
        content ILIKE '%' || p_search_term || '%'
    )
    AND (p_type IS NULL OR type = p_type)
    ORDER BY 
        CASE WHEN title ILIKE p_search_term THEN 1
             WHEN title ILIKE '%' || p_search_term || '%' THEN 2
             ELSE 3
        END,
        created_at DESC;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER; 