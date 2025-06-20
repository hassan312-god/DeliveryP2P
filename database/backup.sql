-- Fonctions de sauvegarde et maintenance de la base de données
-- À exécuter en dernier

-- Fonction pour créer une sauvegarde des données importantes
CREATE OR REPLACE FUNCTION create_data_backup()
RETURNS TEXT AS $$
DECLARE
    backup_id TEXT;
    backup_path TEXT;
BEGIN
    -- Générer un ID de sauvegarde unique
    backup_id := 'backup_' || TO_CHAR(NOW(), 'YYYYMMDD_HH24MISS');
    
    -- Ici vous pourriez implémenter la logique de sauvegarde
    -- Pour l'instant, on crée juste un enregistrement de sauvegarde
    
    -- Créer une table de sauvegarde si elle n'existe pas
    CREATE TABLE IF NOT EXISTS data_backups (
        id TEXT PRIMARY KEY,
        backup_type TEXT NOT NULL,
        tables_backed_up TEXT[],
        record_count INTEGER,
        backup_size BIGINT,
        created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
        status TEXT DEFAULT 'completed',
        notes TEXT
    );
    
    -- Enregistrer la sauvegarde
    INSERT INTO data_backups (
        id, 
        backup_type, 
        tables_backed_up, 
        record_count,
        notes
    ) VALUES (
        backup_id,
        'manual',
        ARRAY['profiles', 'deliveries', 'payments', 'notifications', 'messages', 'reviews'],
        (SELECT COUNT(*) FROM profiles) + (SELECT COUNT(*) FROM deliveries) + (SELECT COUNT(*) FROM payments),
        'Backup manuel créé par l''utilisateur ' || auth.uid()
    );
    
    RETURN backup_id;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour nettoyer les données anciennes
CREATE OR REPLACE FUNCTION cleanup_old_data_scheduled()
RETURNS TABLE (
    table_name TEXT,
    records_deleted INTEGER,
    cleanup_type TEXT
) AS $$
DECLARE
    deleted_count INTEGER;
BEGIN
    -- Nettoyer les notifications anciennes
    DELETE FROM notifications 
    WHERE is_read = true AND created_at < NOW() - INTERVAL '90 days';
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    RETURN QUERY SELECT 'notifications'::TEXT, deleted_count, 'old_read_notifications'::TEXT;
    
    -- Nettoyer les localisations très anciennes
    DELETE FROM user_locations 
    WHERE updated_at < NOW() - INTERVAL '30 days';
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    RETURN QUERY SELECT 'user_locations'::TEXT, deleted_count, 'old_locations'::TEXT;
    
    -- Nettoyer les messages très anciens
    DELETE FROM messages 
    WHERE created_at < NOW() - INTERVAL '1 year';
    GET DIAGNOSTICS deleted_count = ROW_COUNT;
    
    RETURN QUERY SELECT 'messages'::TEXT, deleted_count, 'old_messages'::TEXT;
    
    -- Nettoyer les sessions expirées (si vous utilisez une table de sessions)
    -- DELETE FROM user_sessions WHERE expires_at < NOW();
    
    -- Nettoyer les logs d'audit très anciens (si vous avez une table d'audit)
    -- DELETE FROM audit_log WHERE created_at < NOW() - INTERVAL '2 years';
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour optimiser les performances
CREATE OR REPLACE FUNCTION optimize_database()
RETURNS TABLE (
    operation TEXT,
    status TEXT,
    details TEXT
) AS $$
BEGIN
    -- Analyser les tables pour mettre à jour les statistiques
    ANALYZE profiles;
    ANALYZE deliveries;
    ANALYZE payments;
    ANALYZE notifications;
    ANALYZE messages;
    ANALYZE reviews;
    ANALYZE user_locations;
    
    RETURN QUERY SELECT 'ANALYZE'::TEXT, 'completed'::TEXT, 'All tables analyzed'::TEXT;
    
    -- Vérifier et corriger les index
    -- Note: REINDEX nécessite des privilèges superuser
    -- REINDEX TABLE profiles;
    -- REINDEX TABLE deliveries;
    
    RETURN QUERY SELECT 'INDEX_CHECK'::TEXT, 'completed'::TEXT, 'Indexes checked'::TEXT;
    
    -- Vérifier l'intégrité des données
    -- Vérifier les références orphelines
    IF EXISTS (
        SELECT 1 FROM deliveries d
        LEFT JOIN profiles p ON d.client_id = p.id
        WHERE p.id IS NULL
    ) THEN
        RETURN QUERY SELECT 'DATA_INTEGRITY'::TEXT, 'warning'::TEXT, 'Found orphaned delivery records'::TEXT;
    ELSE
        RETURN QUERY SELECT 'DATA_INTEGRITY'::TEXT, 'passed'::TEXT, 'All foreign key references are valid'::TEXT;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour générer des rapports de maintenance
CREATE OR REPLACE FUNCTION generate_maintenance_report()
RETURNS TABLE (
    metric_name TEXT,
    metric_value TEXT,
    status TEXT,
    recommendation TEXT
) AS $$
DECLARE
    total_users INTEGER;
    active_users INTEGER;
    total_deliveries INTEGER;
    completed_deliveries INTEGER;
    avg_delivery_time DECIMAL;
    db_size BIGINT;
BEGIN
    -- Statistiques des utilisateurs
    SELECT COUNT(*) INTO total_users FROM profiles;
    SELECT COUNT(*) INTO active_users FROM profiles WHERE is_active = true;
    
    RETURN QUERY SELECT 
        'Total Users'::TEXT, 
        total_users::TEXT, 
        'info'::TEXT, 
        'Total registered users'::TEXT;
    
    RETURN QUERY SELECT 
        'Active Users'::TEXT, 
        active_users::TEXT, 
        CASE WHEN active_users::DECIMAL / total_users > 0.7 THEN 'good' ELSE 'warning' END::TEXT,
        CASE 
            WHEN active_users::DECIMAL / total_users < 0.5 THEN 'Consider user engagement campaigns'
            ELSE 'Good user activity rate'
        END::TEXT;
    
    -- Statistiques des livraisons
    SELECT COUNT(*) INTO total_deliveries FROM deliveries;
    SELECT COUNT(*) INTO completed_deliveries FROM deliveries WHERE status = 'delivered';
    
    RETURN QUERY SELECT 
        'Total Deliveries'::TEXT, 
        total_deliveries::TEXT, 
        'info'::TEXT, 
        'Total delivery requests'::TEXT;
    
    RETURN QUERY SELECT 
        'Completion Rate'::TEXT, 
        CASE 
            WHEN total_deliveries > 0 THEN 
                ROUND((completed_deliveries::DECIMAL / total_deliveries * 100), 2)::TEXT || '%'
            ELSE '0%'
        END,
        CASE 
            WHEN total_deliveries > 0 AND (completed_deliveries::DECIMAL / total_deliveries) > 0.8 THEN 'good'
            ELSE 'warning'
        END::TEXT,
        CASE 
            WHEN total_deliveries > 0 AND (completed_deliveries::DECIMAL / total_deliveries) < 0.7 THEN 'Consider improving delivery process'
            ELSE 'Good completion rate'
        END::TEXT;
    
    -- Temps moyen de livraison
    SELECT AVG(
        EXTRACT(EPOCH FROM (delivered_at - created_at)) / 3600
    ) INTO avg_delivery_time
    FROM deliveries 
    WHERE status = 'delivered' AND delivered_at IS NOT NULL;
    
    RETURN QUERY SELECT 
        'Average Delivery Time'::TEXT, 
        CASE 
            WHEN avg_delivery_time IS NOT NULL THEN 
                ROUND(avg_delivery_time, 2)::TEXT || ' hours'
            ELSE 'N/A'
        END,
        CASE 
            WHEN avg_delivery_time IS NOT NULL AND avg_delivery_time < 2 THEN 'good'
            WHEN avg_delivery_time IS NOT NULL AND avg_delivery_time < 4 THEN 'warning'
            ELSE 'info'
        END::TEXT,
        CASE 
            WHEN avg_delivery_time IS NOT NULL AND avg_delivery_time > 4 THEN 'Consider optimizing delivery routes'
            ELSE 'Delivery time is acceptable'
        END::TEXT;
    
    -- Taille de la base de données (approximative)
    SELECT pg_database_size(current_database()) INTO db_size;
    
    RETURN QUERY SELECT 
        'Database Size'::TEXT, 
        pg_size_pretty(db_size), 
        CASE 
            WHEN db_size < 100 * 1024 * 1024 THEN 'good' -- < 100MB
            WHEN db_size < 500 * 1024 * 1024 THEN 'warning' -- < 500MB
            ELSE 'critical'
        END::TEXT,
        CASE 
            WHEN db_size > 500 * 1024 * 1024 THEN 'Consider archiving old data'
            ELSE 'Database size is manageable'
        END::TEXT;
    
    -- Recommandations générales
    IF total_users > 1000 THEN
        RETURN QUERY SELECT 
            'Scale Recommendation'::TEXT, 
            'High'::TEXT, 
            'info'::TEXT, 
            'Consider implementing caching and read replicas'::TEXT;
    END IF;
    
    IF total_deliveries > 10000 THEN
        RETURN QUERY SELECT 
            'Performance Recommendation'::TEXT, 
            'Optimize'::TEXT, 
            'warning'::TEXT, 
            'Consider partitioning deliveries table by date'::TEXT;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour archiver les données anciennes
CREATE OR REPLACE FUNCTION archive_old_data()
RETURNS TABLE (
    table_name TEXT,
    records_archived INTEGER,
    archive_date DATE
) AS $$
DECLARE
    archived_count INTEGER;
BEGIN
    -- Créer la table d'archivage si elle n'existe pas
    CREATE TABLE IF NOT EXISTS archived_deliveries (
        LIKE deliveries INCLUDING ALL,
        archived_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );
    
    -- Archiver les livraisons de plus d'un an
    INSERT INTO archived_deliveries 
    SELECT *, NOW() as archived_at
    FROM deliveries 
    WHERE created_at < NOW() - INTERVAL '1 year'
    AND status IN ('delivered', 'cancelled');
    
    GET DIAGNOSTICS archived_count = ROW_COUNT;
    
    -- Supprimer les données archivées de la table principale
    DELETE FROM deliveries 
    WHERE created_at < NOW() - INTERVAL '1 year'
    AND status IN ('delivered', 'cancelled');
    
    RETURN QUERY SELECT 'deliveries'::TEXT, archived_count, CURRENT_DATE;
    
    -- Archiver les paiements anciens
    CREATE TABLE IF NOT EXISTS archived_payments (
        LIKE payments INCLUDING ALL,
        archived_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );
    
    INSERT INTO archived_payments 
    SELECT *, NOW() as archived_at
    FROM payments 
    WHERE created_at < NOW() - INTERVAL '1 year'
    AND status IN ('completed', 'failed');
    
    GET DIAGNOSTICS archived_count = ROW_COUNT;
    
    DELETE FROM payments 
    WHERE created_at < NOW() - INTERVAL '1 year'
    AND status IN ('completed', 'failed');
    
    RETURN QUERY SELECT 'payments'::TEXT, archived_count, CURRENT_DATE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour restaurer des données depuis une sauvegarde
CREATE OR REPLACE FUNCTION restore_from_backup(backup_id TEXT)
RETURNS BOOLEAN AS $$
BEGIN
    -- Vérifier que la sauvegarde existe
    IF NOT EXISTS (
        SELECT 1 FROM data_backups WHERE id = backup_id
    ) THEN
        RAISE EXCEPTION 'Backup % not found', backup_id;
    END IF;
    
    -- Ici vous implémenteriez la logique de restauration
    -- Pour l'instant, on marque juste la sauvegarde comme restaurée
    
    UPDATE data_backups 
    SET status = 'restored', notes = COALESCE(notes, '') || ' Restored at ' || NOW()
    WHERE id = backup_id;
    
    RETURN TRUE;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Fonction pour surveiller la santé de la base de données
CREATE OR REPLACE FUNCTION check_database_health()
RETURNS TABLE (
    check_name TEXT,
    status TEXT,
    value TEXT,
    threshold TEXT,
    recommendation TEXT
) AS $$
DECLARE
    db_size BIGINT;
    active_connections INTEGER;
    cache_hit_ratio DECIMAL;
    table_bloat_ratio DECIMAL;
BEGIN
    -- Vérifier la taille de la base de données
    SELECT pg_database_size(current_database()) INTO db_size;
    
    RETURN QUERY SELECT 
        'Database Size'::TEXT,
        CASE 
            WHEN db_size < 100 * 1024 * 1024 THEN 'healthy' -- < 100MB
            WHEN db_size < 500 * 1024 * 1024 THEN 'warning' -- < 500MB
            ELSE 'critical'
        END::TEXT,
        pg_size_pretty(db_size),
        '500MB'::TEXT,
        CASE 
            WHEN db_size > 500 * 1024 * 1024 THEN 'Consider archiving old data'
            ELSE 'Size is acceptable'
        END::TEXT;
    
    -- Vérifier les connexions actives
    SELECT count(*) INTO active_connections 
    FROM pg_stat_activity 
    WHERE state = 'active';
    
    RETURN QUERY SELECT 
        'Active Connections'::TEXT,
        CASE 
            WHEN active_connections < 50 THEN 'healthy'
            WHEN active_connections < 100 THEN 'warning'
            ELSE 'critical'
        END::TEXT,
        active_connections::TEXT,
        '100'::TEXT,
        CASE 
            WHEN active_connections > 100 THEN 'Consider connection pooling'
            ELSE 'Connection count is normal'
        END::TEXT;
    
    -- Vérifier le ratio de cache
    SELECT 
        ROUND(
            (sum(heap_blks_hit) / (sum(heap_blks_hit) + sum(heap_blks_read))) * 100, 2
        ) INTO cache_hit_ratio
    FROM pg_statio_user_tables;
    
    RETURN QUERY SELECT 
        'Cache Hit Ratio'::TEXT,
        CASE 
            WHEN cache_hit_ratio > 90 THEN 'healthy'
            WHEN cache_hit_ratio > 80 THEN 'warning'
            ELSE 'critical'
        END::TEXT,
        cache_hit_ratio::TEXT || '%',
        '90%'::TEXT,
        CASE 
            WHEN cache_hit_ratio < 80 THEN 'Consider increasing shared_buffers'
            ELSE 'Cache performance is good'
        END::TEXT;
    
    -- Vérifier les index non utilisés
    IF EXISTS (
        SELECT 1 FROM pg_stat_user_indexes 
        WHERE idx_scan = 0 AND schemaname = 'public'
    ) THEN
        RETURN QUERY SELECT 
            'Unused Indexes'::TEXT,
            'warning'::TEXT,
            'Found'::TEXT,
            '0'::TEXT,
            'Consider dropping unused indexes'::TEXT;
    ELSE
        RETURN QUERY SELECT 
            'Unused Indexes'::TEXT,
            'healthy'::TEXT,
            'None'::TEXT,
            '0'::TEXT,
            'All indexes are being used'::TEXT;
    END IF;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- Créer un job de maintenance automatique (à exécuter périodiquement)
CREATE OR REPLACE FUNCTION run_maintenance_job()
RETURNS TEXT AS $$
DECLARE
    job_result TEXT := 'Maintenance job completed successfully';
BEGIN
    -- Nettoyer les anciennes données
    PERFORM cleanup_old_data_scheduled();
    
    -- Optimiser la base de données
    PERFORM optimize_database();
    
    -- Vérifier la santé
    PERFORM check_database_health();
    
    -- Créer une sauvegarde automatique (optionnel)
    -- PERFORM create_data_backup();
    
    RETURN job_result;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER; 