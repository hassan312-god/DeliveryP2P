-- Vues SQL pour simplifier les requêtes complexes
-- À exécuter après les fonctions et triggers

-- Vue pour les livraisons avec toutes les informations
CREATE OR REPLACE VIEW deliveries_view AS
SELECT 
    d.id,
    d.pickup_address,
    d.pickup_latitude,
    d.pickup_longitude,
    d.delivery_address,
    d.delivery_latitude,
    d.delivery_longitude,
    d.package_description,
    d.package_weight,
    d.package_dimensions,
    d.estimated_distance,
    d.estimated_duration,
    d.base_price,
    d.final_price,
    d.status,
    d.priority,
    d.pickup_instructions,
    d.delivery_instructions,
    d.created_at,
    d.accepted_at,
    d.picked_up_at,
    d.in_transit_at,
    d.delivered_at,
    d.cancelled_at,
    d.updated_at,
    -- Informations du client
    c.id as client_id,
    c.prenom as client_prenom,
    c.nom as client_nom,
    c.email as client_email,
    c.telephone as client_telephone,
    c.rating as client_rating,
    -- Informations du livreur
    l.id as courier_id,
    l.prenom as courier_prenom,
    l.nom as courier_nom,
    l.email as courier_email,
    l.telephone as courier_telephone,
    l.rating as courier_rating,
    -- Calculs dérivés
    CASE 
        WHEN d.status = 'delivered' THEN 
            EXTRACT(EPOCH FROM (d.delivered_at - d.created_at)) / 3600
        ELSE NULL 
    END as delivery_hours,
    CASE 
        WHEN d.status = 'delivered' THEN 
            EXTRACT(EPOCH FROM (d.delivered_at - d.accepted_at)) / 3600
        ELSE NULL 
    END as courier_delivery_hours
FROM deliveries d
JOIN profiles c ON d.client_id = c.id
LEFT JOIN profiles l ON d.courier_id = l.id;

-- Vue pour les utilisateurs avec leurs statistiques
CREATE OR REPLACE VIEW users_stats_view AS
SELECT 
    p.id,
    p.prenom,
    p.nom,
    p.email,
    p.telephone,
    p.role,
    p.avatar_url,
    p.adresse,
    p.ville,
    p.code_postal,
    p.pays,
    p.bio,
    p.rating,
    p.total_deliveries,
    p.total_earnings,
    p.is_verified,
    p.is_active,
    p.created_at,
    p.updated_at,
    -- Statistiques dérivées
    COUNT(CASE WHEN d.status = 'delivered' THEN 1 END) as completed_deliveries,
    COUNT(CASE WHEN d.status IN ('pending', 'accepted', 'picked_up', 'in_transit') THEN 1 END) as active_deliveries,
    COUNT(CASE WHEN d.status = 'cancelled' THEN 1 END) as cancelled_deliveries,
    AVG(CASE WHEN d.status = 'delivered' THEN d.final_price END) as average_delivery_price,
    COUNT(r.id) as total_reviews_received,
    AVG(r.rating) as average_rating_received,
    -- Dernière activité
    GREATEST(
        p.updated_at,
        MAX(d.updated_at),
        MAX(ul.updated_at)
    ) as last_activity
FROM profiles p
LEFT JOIN deliveries d ON (p.id = d.client_id OR p.id = d.courier_id)
LEFT JOIN reviews r ON p.id = r.reviewed_user_id
LEFT JOIN user_locations ul ON p.id = ul.user_id
GROUP BY p.id, p.prenom, p.nom, p.email, p.telephone, p.role, p.avatar_url, 
         p.adresse, p.ville, p.code_postal, p.pays, p.bio, p.rating, 
         p.total_deliveries, p.total_earnings, p.is_verified, p.is_active, 
         p.created_at, p.updated_at;

-- Vue pour les paiements avec informations détaillées
CREATE OR REPLACE VIEW payments_view AS
SELECT 
    p.id,
    p.delivery_id,
    p.amount,
    p.currency,
    p.payment_method,
    p.transaction_id,
    p.status,
    p.created_at,
    p.completed_at,
    -- Informations de la livraison
    d.pickup_address,
    d.delivery_address,
    d.status as delivery_status,
    -- Informations de l'expéditeur
    s.id as sender_id,
    s.prenom as sender_prenom,
    s.nom as sender_nom,
    s.email as sender_email,
    -- Informations du destinataire
    r.id as receiver_id,
    r.prenom as receiver_prenom,
    r.nom as receiver_nom,
    r.email as receiver_email,
    -- Calculs dérivés
    CASE 
        WHEN p.status = 'completed' THEN 
            EXTRACT(EPOCH FROM (p.completed_at - p.created_at)) / 60
        ELSE NULL 
    END as processing_minutes
FROM payments p
JOIN deliveries d ON p.delivery_id = d.id
JOIN profiles s ON p.sender_id = s.id
JOIN profiles r ON p.receiver_id = r.id;

-- Vue pour les notifications avec informations utilisateur
CREATE OR REPLACE VIEW notifications_view AS
SELECT 
    n.id,
    n.user_id,
    n.title,
    n.message,
    n.type,
    n.is_read,
    n.read_at,
    n.action_url,
    n.created_at,
    -- Informations utilisateur
    p.prenom,
    p.nom,
    p.email,
    p.role,
    -- Calculs dérivés
    CASE 
        WHEN n.is_read THEN 
            EXTRACT(EPOCH FROM (n.read_at - n.created_at)) / 60
        ELSE 
            EXTRACT(EPOCH FROM (NOW() - n.created_at)) / 60
    END as response_minutes
FROM notifications n
JOIN profiles p ON n.user_id = p.id;

-- Vue pour les messages avec informations détaillées
CREATE OR REPLACE VIEW messages_view AS
SELECT 
    m.id,
    m.delivery_id,
    m.sender_id,
    m.message,
    m.message_type,
    m.is_read,
    m.read_at,
    m.created_at,
    -- Informations de la livraison
    d.pickup_address,
    d.delivery_address,
    d.status as delivery_status,
    -- Informations de l'expéditeur
    s.prenom as sender_prenom,
    s.nom as sender_nom,
    s.avatar_url as sender_avatar,
    s.role as sender_role,
    -- Calculs dérivés
    CASE 
        WHEN m.is_read THEN 
            EXTRACT(EPOCH FROM (m.read_at - m.created_at)) / 60
        ELSE NULL 
    END as read_delay_minutes
FROM messages m
JOIN deliveries d ON m.delivery_id = d.id
JOIN profiles s ON m.sender_id = s.id;

-- Vue pour les évaluations avec informations détaillées
CREATE OR REPLACE VIEW reviews_view AS
SELECT 
    r.id,
    r.delivery_id,
    r.reviewer_id,
    r.reviewed_user_id,
    r.rating,
    r.comment,
    r.created_at,
    -- Informations de la livraison
    d.pickup_address,
    d.delivery_address,
    d.final_price,
    -- Informations de l'évaluateur
    reviewer.prenom as reviewer_prenom,
    reviewer.nom as reviewer_nom,
    reviewer.role as reviewer_role,
    -- Informations de l'évalué
    reviewed.prenom as reviewed_prenom,
    reviewed.nom as reviewed_nom,
    reviewed.role as reviewed_role,
    reviewed.rating as reviewed_current_rating
FROM reviews r
JOIN deliveries d ON r.delivery_id = d.id
JOIN profiles reviewer ON r.reviewer_id = reviewer.id
JOIN profiles reviewed ON r.reviewed_user_id = reviewed.id;

-- Vue pour les livreurs actifs avec localisation
CREATE OR REPLACE VIEW active_couriers_view AS
SELECT 
    p.id,
    p.prenom,
    p.nom,
    p.email,
    p.telephone,
    p.rating,
    p.total_deliveries,
    p.total_earnings,
    p.is_verified,
    p.created_at,
    -- Localisation
    ul.latitude,
    ul.longitude,
    ul.accuracy,
    ul.updated_at as location_updated_at,
    -- Livraisons actives
    COUNT(CASE WHEN d.status IN ('accepted', 'picked_up', 'in_transit') THEN 1 END) as active_deliveries,
    -- Calculs dérivés
    CASE 
        WHEN ul.updated_at > NOW() - INTERVAL '1 hour' THEN 'En ligne'
        WHEN ul.updated_at > NOW() - INTERVAL '24 hours' THEN 'Récemment actif'
        ELSE 'Inactif'
    END as online_status
FROM profiles p
LEFT JOIN user_locations ul ON p.id = ul.user_id
LEFT JOIN deliveries d ON p.id = d.courier_id AND d.status IN ('accepted', 'picked_up', 'in_transit')
WHERE p.role = 'livreur' AND p.is_active = true
GROUP BY p.id, p.prenom, p.nom, p.email, p.telephone, p.rating, 
         p.total_deliveries, p.total_earnings, p.is_verified, p.created_at,
         ul.latitude, ul.longitude, ul.accuracy, ul.updated_at;

-- Vue pour les statistiques quotidiennes
CREATE OR REPLACE VIEW daily_stats_view AS
SELECT 
    DATE(d.created_at) as date,
    COUNT(*) as total_deliveries,
    COUNT(CASE WHEN d.status = 'delivered' THEN 1 END) as completed_deliveries,
    COUNT(CASE WHEN d.status = 'cancelled' THEN 1 END) as cancelled_deliveries,
    SUM(CASE WHEN d.status = 'delivered' THEN d.final_price ELSE 0 END) as total_earnings,
    AVG(CASE WHEN d.status = 'delivered' THEN d.final_price END) as average_delivery_price,
    COUNT(DISTINCT d.client_id) as unique_clients,
    COUNT(DISTINCT d.courier_id) as unique_couriers,
    -- Temps moyen de livraison (en heures)
    AVG(CASE 
        WHEN d.status = 'delivered' THEN 
            EXTRACT(EPOCH FROM (d.delivered_at - d.created_at)) / 3600
        ELSE NULL 
    END) as average_delivery_time_hours
FROM deliveries d
GROUP BY DATE(d.created_at)
ORDER BY date DESC;

-- Vue pour les zones les plus actives
CREATE OR REPLACE VIEW active_zones_view AS
SELECT 
    cz.name as zone_name,
    cz.city,
    COUNT(d.id) as total_deliveries,
    COUNT(CASE WHEN d.status = 'delivered' THEN 1 END) as completed_deliveries,
    COUNT(CASE WHEN d.status IN ('pending', 'accepted') THEN 1 END) as pending_deliveries,
    AVG(d.final_price) as average_price,
    COUNT(DISTINCT d.client_id) as unique_clients,
    COUNT(DISTINCT d.courier_id) as unique_couriers,
    -- Calculer la zone approximative basée sur les adresses
    CASE 
        WHEN COUNT(d.id) > 0 THEN 'Active'
        ELSE 'Inactive'
    END as activity_status
FROM coverage_zones cz
LEFT JOIN deliveries d ON (
    LOWER(d.pickup_address) LIKE '%' || LOWER(cz.name) || '%' OR
    LOWER(d.delivery_address) LIKE '%' || LOWER(cz.name) || '%'
)
WHERE cz.is_active = true
GROUP BY cz.id, cz.name, cz.city
ORDER BY total_deliveries DESC;

-- Vue pour les performances des livreurs
CREATE OR REPLACE VIEW courier_performance_view AS
SELECT 
    p.id,
    p.prenom,
    p.nom,
    p.rating,
    p.total_deliveries,
    p.total_earnings,
    p.is_verified,
    -- Statistiques dérivées
    COUNT(d.id) as total_deliveries_handled,
    COUNT(CASE WHEN d.status = 'delivered' THEN 1 END) as successful_deliveries,
    COUNT(CASE WHEN d.status = 'cancelled' THEN 1 END) as cancelled_deliveries,
    AVG(CASE WHEN d.status = 'delivered' THEN d.final_price END) as average_earnings_per_delivery,
    -- Temps moyen de livraison
    AVG(CASE 
        WHEN d.status = 'delivered' THEN 
            EXTRACT(EPOCH FROM (d.delivered_at - d.accepted_at)) / 3600
        ELSE NULL 
    END) as average_delivery_time_hours,
    -- Taux de réussite
    CASE 
        WHEN COUNT(d.id) > 0 THEN 
            (COUNT(CASE WHEN d.status = 'delivered' THEN 1 END)::DECIMAL / COUNT(d.id) * 100)
        ELSE 0 
    END as success_rate,
    -- Dernière activité
    MAX(d.updated_at) as last_delivery_activity
FROM profiles p
LEFT JOIN deliveries d ON p.id = d.courier_id
WHERE p.role = 'livreur' AND p.is_active = true
GROUP BY p.id, p.prenom, p.nom, p.rating, p.total_deliveries, p.total_earnings, p.is_verified
ORDER BY success_rate DESC, total_deliveries_handled DESC;

-- Vue pour les clients les plus actifs
CREATE OR REPLACE VIEW top_clients_view AS
SELECT 
    p.id,
    p.prenom,
    p.nom,
    p.email,
    p.rating,
    -- Statistiques dérivées
    COUNT(d.id) as total_deliveries_ordered,
    COUNT(CASE WHEN d.status = 'delivered' THEN 1 END) as completed_deliveries,
    COUNT(CASE WHEN d.status = 'cancelled' THEN 1 END) as cancelled_deliveries,
    SUM(CASE WHEN d.status = 'delivered' THEN d.final_price ELSE 0 END) as total_spent,
    AVG(CASE WHEN d.status = 'delivered' THEN d.final_price END) as average_order_value,
    -- Dernière commande
    MAX(d.created_at) as last_order_date,
    -- Fréquence de commande (commandes par mois)
    CASE 
        WHEN EXTRACT(EPOCH FROM (MAX(d.created_at) - MIN(d.created_at))) / 2592000 > 0 THEN
            COUNT(d.id)::DECIMAL / (EXTRACT(EPOCH FROM (MAX(d.created_at) - MIN(d.created_at))) / 2592000)
        ELSE 0 
    END as orders_per_month
FROM profiles p
LEFT JOIN deliveries d ON p.id = d.client_id
WHERE p.role = 'client' AND p.is_active = true
GROUP BY p.id, p.prenom, p.nom, p.email, p.rating
ORDER BY total_spent DESC, total_deliveries_ordered DESC;

-- ========================================
-- VUES POUR LES QR CODES
-- ========================================

-- Vue pour les QR codes avec informations utilisateur
CREATE OR REPLACE VIEW qr_codes_with_user AS
SELECT 
    qc.*,
    p.prenom,
    p.nom,
    p.email,
    p.role as user_role
FROM qr_codes qc
JOIN profiles p ON qc.user_id = p.id;

-- Vue pour les statistiques des QR codes par utilisateur
CREATE OR REPLACE VIEW qr_code_user_stats AS
SELECT 
    user_id,
    COUNT(*) as total_qr_codes,
    COUNT(*) FILTER (WHERE is_favorite = TRUE) as favorite_qr_codes,
    SUM(scan_count) as total_scans,
    COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '7 days') as recent_qr_codes,
    COUNT(*) FILTER (WHERE created_at > NOW() - INTERVAL '30 days') as monthly_qr_codes,
    AVG(scan_count) as avg_scans_per_qr,
    MAX(created_at) as last_qr_created
FROM qr_codes
GROUP BY user_id;

-- Vue pour les QR codes les plus populaires
CREATE OR REPLACE VIEW popular_qr_codes AS
SELECT 
    qc.*,
    p.prenom,
    p.nom,
    p.role as user_role
FROM qr_codes qc
JOIN profiles p ON qc.user_id = p.id
WHERE qc.scan_count > 0
ORDER BY qc.scan_count DESC, qc.created_at DESC;

-- Vue pour les QR codes par type
CREATE OR REPLACE VIEW qr_codes_by_type AS
SELECT 
    type,
    COUNT(*) as count,
    AVG(scan_count) as avg_scans,
    SUM(scan_count) as total_scans,
    COUNT(*) FILTER (WHERE is_favorite = TRUE) as favorite_count
FROM qr_codes
GROUP BY type
ORDER BY count DESC;

-- Vue pour l'activité récente des QR codes
CREATE OR REPLACE VIEW recent_qr_activity AS
SELECT 
    qc.id,
    qc.title,
    qc.type,
    qc.scan_count,
    qc.last_scanned_at,
    qc.created_at,
    p.prenom,
    p.nom,
    p.role as user_role
FROM qr_codes qc
JOIN profiles p ON qc.user_id = p.id
WHERE qc.created_at > NOW() - INTERVAL '30 days'
   OR qc.last_scanned_at > NOW() - INTERVAL '7 days'
ORDER BY COALESCE(qc.last_scanned_at, qc.created_at) DESC; 