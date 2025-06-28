-- =====================================================
-- Script SQL pour Supabase - LivraisonP2P
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
    address_latitude DECIMAL(10, 8),
    address_longitude DECIMAL(11, 8),
    is_available BOOLEAN DEFAULT TRUE,
    rating_as_expeditor DECIMAL(2,1) DEFAULT 0.0,
    rating_as_courier DECIMAL(2,1) DEFAULT 0.0,
    total_deliveries_as_expeditor INTEGER DEFAULT 0,
    total_deliveries_as_courier INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: ads (Annonces)
-- =====================================================
CREATE TABLE ads (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    expeditor_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    parcel_weight_kg DECIMAL(5,2),
    parcel_dimensions_cm VARCHAR(100),
    pickup_address_street VARCHAR(255) NOT NULL,
    pickup_address_city VARCHAR(255) NOT NULL,
    pickup_address_zipcode VARCHAR(20) NOT NULL,
    pickup_latitude DECIMAL(10, 8) NOT NULL,
    pickup_longitude DECIMAL(11, 8) NOT NULL,
    delivery_address_street VARCHAR(255) NOT NULL,
    delivery_address_city VARCHAR(255) NOT NULL,
    delivery_address_zipcode VARCHAR(20) NOT NULL,
    delivery_latitude DECIMAL(10, 8) NOT NULL,
    delivery_longitude DECIMAL(11, 8) NOT NULL,
    delivery_deadline TIMESTAMP WITH TIME ZONE,
    payment_method VARCHAR(100) CHECK (payment_method IN ('cash', 'card', 'transfer')),
    proposed_amount DECIMAL(10,2) NOT NULL,
    is_fragile BOOLEAN DEFAULT FALSE,
    is_urgent BOOLEAN DEFAULT FALSE,
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'in_transit', 'delivered', 'cancelled')),
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
    status VARCHAR(50) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending', 'accepted', 'rejected')),
    proposed_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    accepted_at TIMESTAMP WITH TIME
);

-- =====================================================
-- Table: deliveries (Livraisons)
-- =====================================================
CREATE TABLE deliveries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ad_id UUID UNIQUE NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    courier_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    qr_code_hash VARCHAR(255) UNIQUE NOT NULL,
    delivery_status VARCHAR(50) NOT NULL DEFAULT 'assigned' CHECK (delivery_status IN ('assigned', 'picked_up', 'in_transit', 'delivered', 'cancelled')),
    pickup_time TIMESTAMP WITH TIME ZONE,
    delivery_time TIMESTAMP WITH TIME ZONE,
    recipient_confirmation_time TIMESTAMP WITH TIME ZONE,
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
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 0.0 AND rating <= 5.0),
    comment TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now(),
    UNIQUE(delivery_id, evaluator_id) -- Un utilisateur ne peut évaluer qu'une fois par livraison
);

-- =====================================================
-- Table: chat_messages (Messages de chat)
-- =====================================================
CREATE TABLE chat_messages (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id UUID NOT NULL REFERENCES deliveries(id) ON DELETE CASCADE,
    sender_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: notifications (Notifications en temps réel)
-- =====================================================
CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    user_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('new_ad', 'ad_accepted', 'delivery_update', 'new_message', 'evaluation_request')),
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_id UUID, -- ID de l'annonce, livraison, message, etc.
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT now()
);

-- =====================================================
-- Table: parcel_photos (Photos des colis)
-- =====================================================
CREATE TABLE parcel_photos (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    ad_id UUID NOT NULL REFERENCES ads(id) ON DELETE CASCADE,
    photo_url TEXT NOT NULL,
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

-- Index pour les annonces
CREATE INDEX idx_ads_expeditor_id ON ads(expeditor_id);
CREATE INDEX idx_ads_status ON ads(status);
CREATE INDEX idx_ads_created_at ON ads(created_at);
CREATE INDEX idx_ads_location ON ads(pickup_latitude, pickup_longitude, delivery_latitude, delivery_longitude);
CREATE INDEX idx_ads_deadline ON ads(delivery_deadline);

-- Index pour les propositions
CREATE INDEX idx_proposals_ad_id ON proposals(ad_id);
CREATE INDEX idx_proposals_courier_id ON proposals(courier_id);
CREATE INDEX idx_proposals_status ON proposals(status);

-- Index pour les livraisons
CREATE INDEX idx_deliveries_ad_id ON deliveries(ad_id);
CREATE INDEX idx_deliveries_courier_id ON deliveries(courier_id);
CREATE INDEX idx_deliveries_status ON deliveries(delivery_status);
CREATE INDEX idx_deliveries_qr_code ON deliveries(qr_code_hash);

-- Index pour les évaluations
CREATE INDEX idx_evaluations_delivery_id ON evaluations(delivery_id);
CREATE INDEX idx_evaluations_evaluator_id ON evaluations(evaluator_id);
CREATE INDEX idx_evaluations_evaluated_user_id ON evaluations(evaluated_user_id);

-- Index pour les messages de chat
CREATE INDEX idx_chat_messages_delivery_id ON chat_messages(delivery_id);
CREATE INDEX idx_chat_messages_sender_id ON chat_messages(sender_id);
CREATE INDEX idx_chat_messages_sent_at ON chat_messages(sent_at);

-- Index pour les notifications
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read) WHERE is_read = FALSE;
CREATE INDEX idx_notifications_created_at ON notifications(created_at);

-- Index pour les photos
CREATE INDEX idx_parcel_photos_ad_id ON parcel_photos(ad_id);

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
ALTER TABLE parcel_photos ENABLE ROW LEVEL SECURITY;

-- Politiques pour les utilisateurs (exemple)
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
        expeditor_id IN (SELECT expeditor_id FROM ads WHERE id = ad_id)
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