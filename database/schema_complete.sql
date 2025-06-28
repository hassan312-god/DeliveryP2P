-- =====================================================
-- Script SQL complet pour Supabase - LivraisonP2P
-- Version 2.0 - Optimisée avec nouveaux champs
-- =====================================================

-- Extension pour UUID
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- =====================================================
-- Table: users (Utilisateurs)
-- =====================================================
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    first_name VARCHAR(255) NOT NULL,
    last_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone_number VARCHAR(50),
    role VARCHAR(50) NOT NULL DEFAULT 'expeditor' CHECK (role IN ('expeditor', 'courier', 'admin')),
    
    -- Adresse et géolocalisation
    address_street VARCHAR(255),
    address_city VARCHAR(255),
    address_zipcode VARCHAR(20),
    address_latitude DECIMAL(10, 8),
    address_longitude DECIMAL(11, 8),
    
    -- Statut et disponibilité
    is_available BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    
    -- Notes et statistiques
    rating_as_expeditor DECIMAL(2,1) DEFAULT 0.0 CHECK (rating_as_expeditor >= 0.0 AND rating_as_expeditor <= 5.0),
    rating_as_courier DECIMAL(2,1) DEFAULT 0.0 CHECK (rating_as_courier >= 0.0 AND rating_as_courier <= 5.0),
    total_deliveries_as_expeditor INTEGER DEFAULT 0,
    total_deliveries_as_courier INTEGER DEFAULT 0,
    
    -- Champs spécifiques aux livreurs (véhicule)
    vehicle_type VARCHAR(100) CHECK (vehicle_type IN ('car', 'motorcycle', 'bicycle', 'scooter', 'van', 'truck')),
    vehicle_color VARCHAR(50),
    vehicle_photo_url TEXT, -- URL vers Supabase Storage
    vehicle_license_plate VARCHAR(20),
    vehicle_model VARCHAR(100),
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: ads (Annonces)
-- =====================================================
CREATE TABLE ads (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    expeditor_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Informations du colis
    title VARCHAR(255) NOT NULL,
    description TEXT,
    parcel_weight_kg DECIMAL(5,2) CHECK (parcel_weight_kg > 0),
    parcel_dimensions_cm VARCHAR(100), -- Format: "LxlxH" (ex: "30x20x15")
    is_fragile BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    requires_signature BOOLEAN DEFAULT FALSE,
    
    -- Adresse de ramassage
    pickup_address_street VARCHAR(255) NOT NULL,
    pickup_address_city VARCHAR(255) NOT NULL,
    pickup_address_zipcode VARCHAR(20) NOT NULL,
    pickup_latitude DECIMAL(10, 8) NOT NULL,
    pickup_longitude DECIMAL(11, 8) NOT NULL,
    pickup_instructions TEXT,
    
    -- Adresse de livraison
    delivery_address_street VARCHAR(255) NOT NULL,
    delivery_address_city VARCHAR(255) NOT NULL,
    delivery_address_zipcode VARCHAR(20) NOT NULL,
    delivery_latitude DECIMAL(10, 8) NOT NULL,
    delivery_longitude DECIMAL(11, 8) NOT NULL,
    delivery_instructions TEXT,
    recipient_name VARCHAR(255),
    recipient_phone VARCHAR(50),
    
    -- Détails de la livraison
    delivery_deadline TIMESTAMP WITH TIME ZONE,
    proposed_amount DECIMAL(10,2) NOT NULL CHECK (proposed_amount > 0),
    payment_method VARCHAR(100) CHECK (payment_method IN ('cash', 'card', 'transfer', 'crypto')),
    
    -- Statut
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'in_transit', 'delivered', 'cancelled', 'expired')),
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: proposals (Propositions de livraison)
-- =====================================================
CREATE TABLE proposals (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ad_id UUID NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    courier_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Détails de la proposition
    proposed_amount DECIMAL(10,2) NOT NULL CHECK (proposed_amount > 0),
    estimated_pickup_time TIMESTAMP WITH TIME ZONE,
    estimated_delivery_time TIMESTAMP WITH TIME ZONE,
    message TEXT,
    
    -- Statut
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected', 'expired')),
    
    -- Métadonnées
    proposed_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    accepted_at TIMESTAMP WITH TIME ZONE,
    rejected_at TIMESTAMP WITH TIME ZONE,
    
    -- Contrainte unique pour éviter les doublons
    UNIQUE(ad_id, courier_id)
);

-- =====================================================
-- Table: deliveries (Livraisons)
-- =====================================================
CREATE TABLE deliveries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ad_id UUID UNIQUE NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    courier_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- QR Code pour confirmation
    qr_code_hash VARCHAR(255) UNIQUE NOT NULL,
    qr_code_generated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    
    -- Statut de la livraison
    delivery_status VARCHAR(50) NOT NULL DEFAULT 'assigned' CHECK (delivery_status IN ('assigned', 'picked_up', 'in_transit', 'approaching_delivery', 'delivered', 'cancelled', 'failed')),
    
    -- Horodatage des étapes
    pickup_time TIMESTAMP WITH TIME ZONE,
    delivery_time TIMESTAMP WITH TIME ZONE,
    recipient_confirmation_time TIMESTAMP WITH TIME ZONE,
    
    -- Informations de suivi
    current_latitude DECIMAL(10, 8),
    current_longitude DECIMAL(11, 8),
    last_location_update TIMESTAMP WITH TIME ZONE,
    
    -- Notes et commentaires
    courier_notes TEXT,
    recipient_notes TEXT,
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: evaluations (Évaluations)
-- =====================================================
CREATE TABLE evaluations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id UUID NOT NULL REFERENCES deliveries(id) ON DELETE CASCADE,
    evaluator_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    evaluated_user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Évaluation
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0.0 AND rating <= 5.0),
    comment TEXT,
    evaluation_type VARCHAR(50) NOT NULL CHECK (evaluation_type IN ('expeditor_to_courier', 'courier_to_expeditor')),
    
    -- Critères détaillés (optionnel)
    punctuality_rating DECIMAL(2,1) CHECK (punctuality_rating >= 0.0 AND punctuality_rating <= 5.0),
    communication_rating DECIMAL(2,1) CHECK (communication_rating >= 0.0 AND communication_rating <= 5.0),
    care_rating DECIMAL(2,1) CHECK (care_rating >= 0.0 AND care_rating <= 5.0),
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    
    -- Contraintes
    UNIQUE(delivery_id, evaluator_id), -- Un utilisateur ne peut évaluer qu'une fois par livraison
    CHECK (evaluator_id != evaluated_user_id) -- Un utilisateur ne peut pas s'évaluer lui-même
);

-- =====================================================
-- Table: chat_messages (Messages de chat)
-- =====================================================
CREATE TABLE chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id UUID NOT NULL REFERENCES deliveries(id) ON DELETE CASCADE,
    sender_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Contenu du message
    message TEXT NOT NULL,
    message_type VARCHAR(50) DEFAULT 'text' CHECK (message_type IN ('text', 'image', 'location', 'file')),
    file_url TEXT, -- Pour les images/fichiers
    
    -- Statut
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP WITH TIME ZONE,
    
    -- Métadonnées
    sent_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: notifications (Notifications)
-- =====================================================
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Contenu de la notification
    type VARCHAR(50) NOT NULL CHECK (type IN ('new_ad', 'ad_accepted', 'delivery_update', 'new_message', 'evaluation_request', 'payment_received', 'delivery_confirmed', 'system_alert')),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id UUID, -- ID de l'annonce, livraison, message, etc.
    related_type VARCHAR(50), -- Type de l'entité liée
    
    -- Canal de notification
    channel VARCHAR(50) NOT NULL DEFAULT 'realtime' CHECK (channel IN ('realtime', 'email', 'webpush', 'sms')),
    
    -- Statut
    is_read BOOLEAN DEFAULT FALSE,
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP WITH TIME ZONE,
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: web_push_subscriptions (Abonnements Web Push)
-- =====================================================
CREATE TABLE web_push_subscriptions (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    
    -- Détails de l'abonnement
    endpoint TEXT NOT NULL,
    p256dh VARCHAR(255) NOT NULL,
    auth VARCHAR(255) NOT NULL,
    
    -- Informations du navigateur
    user_agent TEXT,
    browser VARCHAR(100),
    platform VARCHAR(100),
    
    -- Statut
    is_active BOOLEAN DEFAULT TRUE,
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    
    -- Contrainte unique
    UNIQUE(user_id, endpoint)
);

-- =====================================================
-- Table: parcel_photos (Photos des colis)
-- =====================================================
CREATE TABLE parcel_photos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ad_id UUID NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    
    -- Informations de l'image
    photo_url TEXT NOT NULL,
    photo_filename VARCHAR(255),
    photo_size_bytes INTEGER,
    photo_mime_type VARCHAR(100),
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: delivery_photos (Photos de la livraison)
-- =====================================================
CREATE TABLE delivery_photos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id UUID NOT NULL REFERENCES deliveries(id) ON DELETE CASCADE,
    
    -- Informations de l'image
    photo_url TEXT NOT NULL,
    photo_type VARCHAR(50) NOT NULL CHECK (photo_type IN ('pickup_proof', 'delivery_proof', 'damage_report')),
    photo_filename VARCHAR(255),
    photo_size_bytes INTEGER,
    photo_mime_type VARCHAR(100),
    
    -- Métadonnées
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Index pour optimiser les performances
-- =====================================================

-- Index pour les utilisateurs
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_available ON users(is_available) WHERE is_available = TRUE;
CREATE INDEX idx_users_location ON users(address_latitude, address_longitude);
CREATE INDEX idx_users_vehicle_type ON users(vehicle_type) WHERE vehicle_type IS NOT NULL;

-- Index pour les annonces
CREATE INDEX idx_ads_expeditor_id ON ads(expeditor_id);
CREATE INDEX idx_ads_status ON ads(status);
CREATE INDEX idx_ads_created_at ON ads(created_at);
CREATE INDEX idx_ads_location ON ads(pickup_latitude, pickup_longitude, delivery_latitude, delivery_longitude);
CREATE INDEX idx_ads_deadline ON ads(delivery_deadline);
CREATE INDEX idx_ads_amount ON ads(proposed_amount);

-- Index pour les propositions
CREATE INDEX idx_proposals_ad_id ON proposals(ad_id);
CREATE INDEX idx_proposals_courier_id ON proposals(courier_id);
CREATE INDEX idx_proposals_status ON proposals(status);
CREATE INDEX idx_proposals_amount ON proposals(proposed_amount);

-- Index pour les livraisons
CREATE INDEX idx_deliveries_ad_id ON deliveries(ad_id);
CREATE INDEX idx_deliveries_courier_id ON deliveries(courier_id);
CREATE INDEX idx_deliveries_status ON deliveries(delivery_status);
CREATE INDEX idx_deliveries_qr_code ON deliveries(qr_code_hash);
CREATE INDEX idx_deliveries_location ON deliveries(current_latitude, current_longitude);

-- Index pour les évaluations
CREATE INDEX idx_evaluations_delivery_id ON evaluations(delivery_id);
CREATE INDEX idx_evaluations_evaluator_id ON evaluations(evaluator_id);
CREATE INDEX idx_evaluations_evaluated_user_id ON evaluations(evaluated_user_id);
CREATE INDEX idx_evaluations_type ON evaluations(evaluation_type);

-- Index pour les messages de chat
CREATE INDEX idx_chat_messages_delivery_id ON chat_messages(delivery_id);
CREATE INDEX idx_chat_messages_sender_id ON chat_messages(sender_id);
CREATE INDEX idx_chat_messages_sent_at ON chat_messages(sent_at);
CREATE INDEX idx_chat_messages_read ON chat_messages(is_read) WHERE is_read = FALSE;

-- Index pour les notifications
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read) WHERE is_read = FALSE;
CREATE INDEX idx_notifications_created_at ON notifications(created_at);
CREATE INDEX idx_notifications_type ON notifications(type);
CREATE INDEX idx_notifications_channel ON notifications(channel);

-- Index pour les abonnements Web Push
CREATE INDEX idx_web_push_user_id ON web_push_subscriptions(user_id);
CREATE INDEX idx_web_push_active ON web_push_subscriptions(is_active) WHERE is_active = TRUE;

-- Index pour les photos
CREATE INDEX idx_parcel_photos_ad_id ON parcel_photos(ad_id);
CREATE INDEX idx_delivery_photos_delivery_id ON delivery_photos(delivery_id);
CREATE INDEX idx_delivery_photos_type ON delivery_photos(photo_type);

-- =====================================================
-- Triggers pour updated_at
-- =====================================================

-- Fonction pour mettre à jour updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Triggers pour les tables avec updated_at
CREATE TRIGGER update_users_updated_at BEFORE UPDATE ON users FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_ads_updated_at BEFORE UPDATE ON ads FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_deliveries_updated_at BEFORE UPDATE ON deliveries FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
CREATE TRIGGER update_web_push_updated_at BEFORE UPDATE ON web_push_subscriptions FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- =====================================================
-- Fonctions utilitaires
-- =====================================================

-- Fonction pour calculer la distance entre deux points (formule Haversine)
CREATE OR REPLACE FUNCTION calculate_distance(
    lat1 DECIMAL, lon1 DECIMAL, 
    lat2 DECIMAL, lon2 DECIMAL
) RETURNS DECIMAL AS $$
BEGIN
    RETURN (
        6371 * acos(
            cos(radians(lat1)) * 
            cos(radians(lat2)) * 
            cos(radians(lon2) - radians(lon1)) + 
            sin(radians(lat1)) * 
            sin(radians(lat2))
        )
    );
END;
$$ LANGUAGE plpgsql;

-- Fonction pour mettre à jour les notes moyennes d'un utilisateur
CREATE OR REPLACE FUNCTION update_user_ratings()
RETURNS TRIGGER AS $$
BEGIN
    -- Mettre à jour la note moyenne en tant qu'expéditeur
    UPDATE users 
    SET 
        rating_as_expeditor = (
            SELECT COALESCE(AVG(e.rating), 0.0)
            FROM evaluations e
            JOIN deliveries d ON e.delivery_id = d.id
            JOIN ads a ON d.ad_id = a.id
            WHERE a.expeditor_id = NEW.evaluated_user_id
            AND e.evaluation_type = 'courier_to_expeditor'
        ),
        total_deliveries_as_expeditor = (
            SELECT COUNT(*)
            FROM deliveries d
            JOIN ads a ON d.ad_id = a.id
            WHERE a.expeditor_id = NEW.evaluated_user_id
            AND d.delivery_status = 'delivered'
        )
    WHERE id = NEW.evaluated_user_id;
    
    -- Mettre à jour la note moyenne en tant que livreur
    UPDATE users 
    SET 
        rating_as_courier = (
            SELECT COALESCE(AVG(e.rating), 0.0)
            FROM evaluations e
            JOIN deliveries d ON e.delivery_id = d.id
            WHERE d.courier_id = NEW.evaluated_user_id
            AND e.evaluation_type = 'expeditor_to_courier'
        ),
        total_deliveries_as_courier = (
            SELECT COUNT(*)
            FROM deliveries
            WHERE courier_id = NEW.evaluated_user_id
            AND delivery_status = 'delivered'
        )
    WHERE id = NEW.evaluated_user_id;
    
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger pour mettre à jour les notes lors d'une évaluation
CREATE TRIGGER update_ratings_after_evaluation
    AFTER INSERT OR UPDATE ON evaluations
    FOR EACH ROW
    EXECUTE FUNCTION update_user_ratings();

-- Fonction pour générer un QR code hash unique
CREATE OR REPLACE FUNCTION generate_qr_code_hash()
RETURNS VARCHAR AS $$
BEGIN
    RETURN 'QR_' || gen_random_uuid()::text || '_' || extract(epoch from now())::text;
END;
$$ LANGUAGE plpgsql;

-- =====================================================
-- Politiques RLS (Row Level Security) - Optionnel
-- =====================================================

-- Activer RLS sur toutes les tables
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE ads ENABLE ROW LEVEL SECURITY;
ALTER TABLE proposals ENABLE ROW LEVEL SECURITY;
ALTER TABLE deliveries ENABLE ROW LEVEL SECURITY;
ALTER TABLE evaluations ENABLE ROW LEVEL SECURITY;
ALTER TABLE chat_messages ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE web_push_subscriptions ENABLE ROW LEVEL SECURITY;
ALTER TABLE parcel_photos ENABLE ROW LEVEL SECURITY;
ALTER TABLE delivery_photos ENABLE ROW LEVEL SECURITY;

-- Politiques pour les utilisateurs
CREATE POLICY "Users can view their own profile" ON users
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update their own profile" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Politiques pour les annonces
CREATE POLICY "Anyone can view pending ads" ON ads
    FOR SELECT USING (status = 'pending');

CREATE POLICY "Users can view their own ads" ON ads
    FOR SELECT USING (expeditor_id = auth.uid());

CREATE POLICY "Users can create their own ads" ON ads
    FOR INSERT WITH CHECK (expeditor_id = auth.uid());

-- Politiques pour les livraisons
CREATE POLICY "Users can view their own deliveries" ON deliveries
    FOR SELECT USING (
        courier_id = auth.uid() OR 
        ad_id IN (SELECT id FROM ads WHERE expeditor_id = auth.uid())
    );

-- Note: Ces politiques RLS sont des exemples. 
-- Elles doivent être adaptées selon vos besoins de sécurité spécifiques.

-- =====================================================
-- Données de test (optionnel)
-- =====================================================

-- Insérer un utilisateur administrateur de test
-- INSERT INTO users (first_name, last_name, email, password_hash, role) 
-- VALUES ('Admin', 'System', 'admin@livraisonp2p.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- =====================================================
-- Fin du script
-- ===================================================== 