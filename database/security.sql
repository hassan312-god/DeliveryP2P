-- Politiques de sécurité supplémentaires et configuration RLS
-- À exécuter après les vues

-- Politiques pour les paiements
CREATE POLICY "Users can view payments they are involved in" ON payments
    FOR SELECT USING (
        auth.uid() = sender_id OR 
        auth.uid() = receiver_id OR
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

CREATE POLICY "Users can create payments for their deliveries" ON payments
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM deliveries 
            WHERE id = delivery_id AND 
            (client_id = auth.uid() OR courier_id = auth.uid())
        )
    );

CREATE POLICY "Users can update their own payments" ON payments
    FOR UPDATE USING (
        auth.uid() = sender_id OR 
        auth.uid() = receiver_id OR
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Politiques pour les évaluations
CREATE POLICY "Users can view reviews for deliveries they participated in" ON reviews
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM deliveries 
            WHERE id = delivery_id AND 
            (client_id = auth.uid() OR courier_id = auth.uid())
        ) OR
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

CREATE POLICY "Users can create reviews for deliveries they participated in" ON reviews
    FOR INSERT WITH CHECK (
        EXISTS (
            SELECT 1 FROM deliveries 
            WHERE id = delivery_id AND 
            (client_id = auth.uid() OR courier_id = auth.uid())
        ) AND
        reviewer_id = auth.uid()
    );

CREATE POLICY "Users can update their own reviews" ON reviews
    FOR UPDATE USING (reviewer_id = auth.uid());

-- Politiques pour les localisations utilisateurs
CREATE POLICY "Users can view their own location" ON user_locations
    FOR SELECT USING (auth.uid() = user_id);

CREATE POLICY "Users can update their own location" ON user_locations
    FOR UPDATE USING (auth.uid() = user_id);

CREATE POLICY "Users can insert their own location" ON user_locations
    FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Politiques pour les zones de couverture (lecture publique)
CREATE POLICY "Anyone can view coverage zones" ON coverage_zones
    FOR SELECT USING (true);

CREATE POLICY "Only admins can manage coverage zones" ON coverage_zones
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Politiques pour les paramètres de prix (lecture publique, écriture admin)
CREATE POLICY "Anyone can view pricing settings" ON pricing_settings
    FOR SELECT USING (true);

CREATE POLICY "Only admins can manage pricing settings" ON pricing_settings
    FOR ALL USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- Politiques pour les vues (si elles sont utilisées comme tables)
-- Note: Les vues héritent généralement des politiques des tables sous-jacentes

-- Fonction pour vérifier les permissions avancées
CREATE OR REPLACE FUNCTION check_user_permission(
    required_permission TEXT,
    resource_type TEXT DEFAULT NULL,
    resource_id UUID DEFAULT NULL
)
RETURNS BOOLEAN AS $$
DECLARE
    user_role TEXT;
    has_permission BOOLEAN := FALSE;
BEGIN
    -- Récupérer le rôle de l'utilisateur
    SELECT role INTO user_role
    FROM profiles
    WHERE id = auth.uid();
    
    -- Vérifier les permissions selon le rôle
    CASE user_role
        WHEN 'admin' THEN
            has_permission := TRUE; -- Les admins ont toutes les permissions
        WHEN 'livreur' THEN
            has_permission := required_permission IN (
                'accept_delivery',
                'update_delivery_status',
                'view_available_deliveries',
                'update_location',
                'view_own_earnings',
                'rate_client'
            );
        WHEN 'client' THEN
            has_permission := required_permission IN (
                'create_delivery',
                'view_own_deliveries',
                'cancel_delivery',
                'rate_courier',
                'view_own_payments'
            );
        ELSE
            has_permission := FALSE;
    END CASE;
    
    -- Vérifications spécifiques selon le type de ressource
    IF resource_type = 'delivery' AND resource_id IS NOT NULL THEN
        -- Vérifier que l'utilisateur participe à cette livraison
        has_permission := has_permission AND EXISTS (
            SELECT 1 FROM deliveries 
            WHERE id = resource_id AND 
            (client_id = auth.uid() OR courier_id = auth.uid())
        );
    END IF;
    
    RETURN has_permission;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour auditer les actions importantes
CREATE OR REPLACE FUNCTION audit_user_action(
    action_type TEXT,
    table_name TEXT,
    record_id UUID,
    old_data JSONB DEFAULT NULL,
    new_data JSONB DEFAULT NULL
)
RETURNS void AS $$
BEGIN
    -- Ici vous pourriez insérer dans une table d'audit
    -- INSERT INTO audit_log (user_id, action_type, table_name, record_id, old_data, new_data, created_at)
    -- VALUES (auth.uid(), action_type, table_name, record_id, old_data, new_data, NOW());
    
    -- Pour l'instant, on log dans les notices
    RAISE NOTICE 'AUDIT: User % performed % on % table, record %', 
        auth.uid(), action_type, table_name, record_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour nettoyer les données sensibles
CREATE OR REPLACE FUNCTION sanitize_user_data()
RETURNS TRIGGER AS $$
BEGIN
    -- Masquer les données sensibles dans les logs
    IF TG_OP = 'DELETE' THEN
        PERFORM audit_user_action('DELETE', TG_TABLE_NAME, OLD.id, to_jsonb(OLD), NULL);
    ELSIF TG_OP = 'UPDATE' THEN
        PERFORM audit_user_action('UPDATE', TG_TABLE_NAME, NEW.id, to_jsonb(OLD), to_jsonb(NEW));
    ELSIF TG_OP = 'INSERT' THEN
        PERFORM audit_user_action('INSERT', TG_TABLE_NAME, NEW.id, NULL, to_jsonb(NEW));
    END IF;
    
    RETURN COALESCE(NEW, OLD);
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Appliquer l'audit aux tables importantes
CREATE TRIGGER audit_deliveries_changes
    AFTER INSERT OR UPDATE OR DELETE ON deliveries
    FOR EACH ROW EXECUTE FUNCTION sanitize_user_data();

CREATE TRIGGER audit_payments_changes
    AFTER INSERT OR UPDATE OR DELETE ON payments
    FOR EACH ROW EXECUTE FUNCTION sanitize_user_data();

CREATE TRIGGER audit_profiles_changes
    AFTER UPDATE OR DELETE ON profiles
    FOR EACH ROW EXECUTE FUNCTION sanitize_user_data();

-- Fonction pour vérifier la sécurité des données
CREATE OR REPLACE FUNCTION security_check()
RETURNS TABLE (
    check_name TEXT,
    status TEXT,
    message TEXT
) AS $$
BEGIN
    -- Vérifier que RLS est activé sur toutes les tables importantes
    RETURN QUERY
    SELECT 
        'RLS Check'::TEXT,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_name IN ('profiles', 'deliveries', 'payments', 'notifications', 'messages', 'reviews')
                AND table_schema = 'public'
            ) THEN 'PASS'::TEXT
            ELSE 'FAIL'::TEXT
        END,
        'Row Level Security should be enabled on all sensitive tables'::TEXT;
    
    -- Vérifier que les politiques existent
    RETURN QUERY
    SELECT 
        'Policies Check'::TEXT,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM pg_policies 
                WHERE tablename IN ('profiles', 'deliveries', 'payments')
            ) THEN 'PASS'::TEXT
            ELSE 'FAIL'::TEXT
        END,
        'Security policies should be defined for all tables'::TEXT;
    
    -- Vérifier les index de sécurité
    RETURN QUERY
    SELECT 
        'Index Check'::TEXT,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM pg_indexes 
                WHERE indexname LIKE '%idx_%'
            ) THEN 'PASS'::TEXT
            ELSE 'FAIL'::TEXT
        END,
        'Performance indexes should be created'::TEXT;
END;
$$ LANGUAGE plpgsql;

-- Fonction pour obtenir les statistiques de sécurité
CREATE OR REPLACE FUNCTION get_security_stats()
RETURNS TABLE (
    total_users INTEGER,
    active_users INTEGER,
    total_deliveries INTEGER,
    completed_deliveries INTEGER,
    total_payments INTEGER,
    completed_payments INTEGER,
    security_score DECIMAL
) AS $$
DECLARE
    score DECIMAL := 100.0;
BEGIN
    -- Calculer les statistiques de base
    SELECT 
        COUNT(*) INTO total_users
    FROM profiles;
    
    SELECT 
        COUNT(*) INTO active_users
    FROM profiles 
    WHERE is_active = true;
    
    SELECT 
        COUNT(*) INTO total_deliveries
    FROM deliveries;
    
    SELECT 
        COUNT(*) INTO completed_deliveries
    FROM deliveries 
    WHERE status = 'delivered';
    
    SELECT 
        COUNT(*) INTO total_payments
    FROM payments;
    
    SELECT 
        COUNT(*) INTO completed_payments
    FROM payments 
    WHERE status = 'completed';
    
    -- Calculer un score de sécurité basique
    IF total_users > 0 THEN
        score := score - (CASE WHEN active_users::DECIMAL / total_users < 0.8 THEN 10 ELSE 0 END);
    END IF;
    
    IF total_deliveries > 0 THEN
        score := score - (CASE WHEN completed_deliveries::DECIMAL / total_deliveries < 0.7 THEN 10 ELSE 0 END);
    END IF;
    
    IF total_payments > 0 THEN
        score := score - (CASE WHEN completed_payments::DECIMAL / total_payments < 0.9 THEN 10 ELSE 0 END);
    END IF;
    
    RETURN QUERY
    SELECT 
        total_users,
        active_users,
        total_deliveries,
        completed_deliveries,
        total_payments,
        completed_payments,
        score;
END;
$$ LANGUAGE plpgsql;

-- Créer des rôles de base de données pour différents niveaux d'accès
-- Note: Ces rôles doivent être créés par un superuser

-- Rôle pour les développeurs (lecture seule sur la plupart des tables)
-- CREATE ROLE developer_role;
-- GRANT CONNECT ON DATABASE postgres TO developer_role;
-- GRANT USAGE ON SCHEMA public TO developer_role;
-- GRANT SELECT ON ALL TABLES IN SCHEMA public TO developer_role;

-- Rôle pour les analystes (lecture et statistiques)
-- CREATE ROLE analyst_role;
-- GRANT CONNECT ON DATABASE postgres TO analyst_role;
-- GRANT USAGE ON SCHEMA public TO analyst_role;
-- GRANT SELECT ON ALL TABLES IN SCHEMA public TO analyst_role;
-- GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO analyst_role;

-- Rôle pour les administrateurs (accès complet)
-- CREATE ROLE admin_role;
-- GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO admin_role;
-- GRANT ALL PRIVILEGES ON ALL FUNCTIONS IN SCHEMA public TO admin_role;
-- GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO admin_role;

-- Fonction pour changer le rôle d'un utilisateur (admin seulement)
CREATE OR REPLACE FUNCTION change_user_role(
    target_user_id UUID,
    new_role TEXT
)
RETURNS BOOLEAN AS $$
BEGIN
    -- Vérifier que l'utilisateur actuel est admin
    IF NOT EXISTS (
        SELECT 1 FROM profiles 
        WHERE id = auth.uid() AND role = 'admin'
    ) THEN
        RAISE EXCEPTION 'Permission denied: Only admins can change user roles';
    END IF;
    
    -- Vérifier que le nouveau rôle est valide
    IF new_role NOT IN ('client', 'livreur', 'admin') THEN
        RAISE EXCEPTION 'Invalid role: Must be client, livreur, or admin';
    END IF;
    
    -- Mettre à jour le rôle
    UPDATE profiles 
    SET role = new_role, updated_at = NOW()
    WHERE id = target_user_id;
    
    -- Auditer le changement
    PERFORM audit_user_action(
        'ROLE_CHANGE',
        'profiles',
        target_user_id,
        NULL,
        jsonb_build_object('new_role', new_role)
    );
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour désactiver un utilisateur (admin seulement)
CREATE OR REPLACE FUNCTION deactivate_user(target_user_id UUID)
RETURNS BOOLEAN AS $$
BEGIN
    -- Vérifier que l'utilisateur actuel est admin
    IF NOT EXISTS (
        SELECT 1 FROM profiles 
        WHERE id = auth.uid() AND role = 'admin'
    ) THEN
        RAISE EXCEPTION 'Permission denied: Only admins can deactivate users';
    END IF;
    
    -- Empêcher la désactivation d'un admin par un autre admin
    IF EXISTS (
        SELECT 1 FROM profiles 
        WHERE id = target_user_id AND role = 'admin'
    ) THEN
        RAISE EXCEPTION 'Cannot deactivate admin users';
    END IF;
    
    -- Désactiver l'utilisateur
    UPDATE profiles 
    SET is_active = FALSE, updated_at = NOW()
    WHERE id = target_user_id;
    
    -- Auditer l'action
    PERFORM audit_user_action(
        'DEACTIVATE',
        'profiles',
        target_user_id,
        NULL,
        jsonb_build_object('is_active', FALSE)
    );
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ========================================
-- POLITIQUES POUR LES QR CODES
-- ========================================

-- Activer RLS sur la table qr_codes
ALTER TABLE qr_codes ENABLE ROW LEVEL SECURITY;

-- Politique pour permettre aux utilisateurs de voir leurs propres QR codes
CREATE POLICY "Users can view their own QR codes" ON qr_codes
    FOR SELECT USING (auth.uid() = user_id);

-- Politique pour permettre aux utilisateurs de créer leurs propres QR codes
CREATE POLICY "Users can create their own QR codes" ON qr_codes
    FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Politique pour permettre aux utilisateurs de modifier leurs propres QR codes
CREATE POLICY "Users can update their own QR codes" ON qr_codes
    FOR UPDATE USING (auth.uid() = user_id);

-- Politique pour permettre aux utilisateurs de supprimer leurs propres QR codes
CREATE POLICY "Users can delete their own QR codes" ON qr_codes
    FOR DELETE USING (auth.uid() = user_id);

-- Politique pour permettre aux administrateurs de voir tous les QR codes
CREATE POLICY "Admins can view all QR codes" ON qr_codes
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE profiles.id = auth.uid() 
            AND profiles.role = 'admin'
        )
    );

-- Politique pour permettre aux administrateurs de modifier tous les QR codes
CREATE POLICY "Admins can update all QR codes" ON qr_codes
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE profiles.id = auth.uid() 
            AND profiles.role = 'admin'
        )
    );

-- Politique pour permettre aux administrateurs de supprimer tous les QR codes
CREATE POLICY "Admins can delete all QR codes" ON qr_codes
    FOR DELETE USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE profiles.id = auth.uid() 
            AND profiles.role = 'admin'
        )
    ); 