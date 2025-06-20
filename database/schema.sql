-- =====================================================
-- SCHÉMA DE BASE DE DONNÉES LIVRAISONP2P
-- =====================================================

-- Extension pour les UUIDs
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Extension pour les fonctions de cryptage
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Extension pour les fonctions JSON
CREATE EXTENSION IF NOT EXISTS "pg_trgm";

-- =====================================================
-- TABLE PROFILS UTILISATEURS
-- =====================================================
CREATE TABLE IF NOT EXISTS profiles (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email TEXT UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20),
    role VARCHAR(20) NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'livreur', 'admin')),
    date_inscription TIMESTAMPTZ DEFAULT NOW(),
    date_confirmation TIMESTAMPTZ,
    email_confirme BOOLEAN DEFAULT FALSE,
    statut VARCHAR(20) DEFAULT 'en_attente_confirmation' CHECK (statut IN ('en_attente_confirmation', 'actif', 'suspendu', 'supprime')),
    avatar_url TEXT,
    adresse TEXT,
    ville VARCHAR(100),
    code_postal VARCHAR(10),
    pays VARCHAR(100) DEFAULT 'France',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    zone_couverture INTEGER DEFAULT 10, -- en km
    note_moyenne DECIMAL(3, 2) DEFAULT 0,
    nombre_evaluations INTEGER DEFAULT 0,
    total_livraisons INTEGER DEFAULT 0,
    total_gains DECIMAL(10, 2) DEFAULT 0,
    preferences JSONB DEFAULT '{}',
    metadata JSONB DEFAULT '{}',
    provider VARCHAR(50) DEFAULT 'email',
    last_login TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE FILE D'ATTENTE DES EMAILS
-- =====================================================
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

-- =====================================================
-- TABLE LIVRAISONS
-- =====================================================
CREATE TABLE IF NOT EXISTS livraisons (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    client_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    livreur_id UUID REFERENCES profiles(id) ON DELETE SET NULL,
    titre VARCHAR(200) NOT NULL,
    description TEXT,
    adresse_depart TEXT NOT NULL,
    adresse_arrivee TEXT NOT NULL,
    latitude_depart DECIMAL(10, 8),
    longitude_depart DECIMAL(11, 8),
    latitude_arrivee DECIMAL(10, 8),
    longitude_arrivee DECIMAL(11, 8),
    distance_km DECIMAL(8, 2),
    duree_estimee INTEGER, -- en minutes
    poids DECIMAL(8, 2), -- en kg
    dimensions JSONB, -- {longueur, largeur, hauteur}
    type_colis VARCHAR(50) CHECK (type_colis IN ('document', 'colis', 'fragile', 'alimentaire', 'autre')),
    urgence VARCHAR(20) DEFAULT 'normale' CHECK (urgence IN ('normale', 'urgente', 'express')),
    statut VARCHAR(20) DEFAULT 'en_attente' CHECK (statut IN ('en_attente', 'acceptee', 'en_cours', 'livree', 'annulee', 'probleme')),
    prix_propose DECIMAL(10, 2),
    prix_final DECIMAL(10, 2),
    commission DECIMAL(10, 2) DEFAULT 0,
    date_creation TIMESTAMPTZ DEFAULT NOW(),
    date_acceptation TIMESTAMPTZ,
    date_debut TIMESTAMPTZ,
    date_fin TIMESTAMPTZ,
    date_limite TIMESTAMPTZ,
    instructions_speciales TEXT,
    photos_avant JSONB,
    photos_apres JSONB,
    signature_livreur TEXT,
    signature_client TEXT,
    qr_code_id UUID,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE PAIEMENTS
-- =====================================================
CREATE TABLE IF NOT EXISTS paiements (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    livraison_id UUID NOT NULL REFERENCES livraisons(id) ON DELETE CASCADE,
    client_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    livreur_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    montant DECIMAL(10, 2) NOT NULL,
    commission DECIMAL(10, 2) DEFAULT 0,
    montant_livreur DECIMAL(10, 2) NOT NULL,
    methode_paiement VARCHAR(50) NOT NULL CHECK (methode_paiement IN ('carte', 'especes', 'virement', 'paypal', 'stripe')),
    statut VARCHAR(20) DEFAULT 'en_attente' CHECK (statut IN ('en_attente', 'traite', 'annule', 'rembourse', 'echoue')),
    transaction_id TEXT,
    reference_paiement TEXT,
    date_paiement TIMESTAMPTZ,
    date_creation TIMESTAMPTZ DEFAULT NOW(),
    date_traitement TIMESTAMPTZ,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE NOTIFICATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS notifications (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    data JSONB DEFAULT '{}',
    date_creation TIMESTAMPTZ DEFAULT NOW(),
    date_lecture TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE MESSAGES
-- =====================================================
CREATE TABLE IF NOT EXISTS messages (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    livraison_id UUID NOT NULL REFERENCES livraisons(id) ON DELETE CASCADE,
    expediteur_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    destinataire_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    contenu TEXT NOT NULL,
    type VARCHAR(20) DEFAULT 'texte' CHECK (type IN ('texte', 'image', 'fichier', 'localisation')),
    lu BOOLEAN DEFAULT FALSE,
    date_envoi TIMESTAMPTZ DEFAULT NOW(),
    date_lecture TIMESTAMPTZ,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE ÉVALUATIONS
-- =====================================================
CREATE TABLE IF NOT EXISTS evaluations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    livraison_id UUID NOT NULL REFERENCES livraisons(id) ON DELETE CASCADE,
    evaluateur_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    evalue_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    note INTEGER NOT NULL CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    date_evaluation TIMESTAMPTZ DEFAULT NOW(),
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW(),
    UNIQUE(livraison_id, evaluateur_id, evalue_id)
);

-- =====================================================
-- TABLE LOCALISATIONS UTILISATEURS
-- =====================================================
CREATE TABLE IF NOT EXISTS user_locations (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID NOT NULL REFERENCES profiles(id) ON DELETE CASCADE,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    precision INTEGER, -- en mètres
    vitesse DECIMAL(5, 2), -- en km/h
    direction INTEGER, -- en degrés
    altitude DECIMAL(8, 2), -- en mètres
    timestamp TIMESTAMPTZ DEFAULT NOW(),
    created_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE PARAMÈTRES DE PRIX
-- =====================================================
CREATE TABLE IF NOT EXISTS pricing_settings (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    prix_base DECIMAL(10, 2) NOT NULL,
    prix_km DECIMAL(10, 2) NOT NULL,
    prix_kg DECIMAL(10, 2) DEFAULT 0,
    commission_pourcentage DECIMAL(5, 2) DEFAULT 10,
    frais_fixes DECIMAL(10, 2) DEFAULT 0,
    multiplicateur_urgence DECIMAL(3, 2) DEFAULT 1.5,
    multiplicateur_express DECIMAL(3, 2) DEFAULT 2.0,
    actif BOOLEAN DEFAULT TRUE,
    date_debut TIMESTAMPTZ DEFAULT NOW(),
    date_fin TIMESTAMPTZ,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE ZONES DE COUVERTURE
-- =====================================================
CREATE TABLE IF NOT EXISTS coverage_zones (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    centre_latitude DECIMAL(10, 8) NOT NULL,
    centre_longitude DECIMAL(11, 8) NOT NULL,
    rayon_km INTEGER NOT NULL,
    actif BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE CODES QR
-- =====================================================
CREATE TABLE IF NOT EXISTS qr_codes (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES profiles(id) ON DELETE CASCADE,
    livraison_id UUID REFERENCES livraisons(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL CHECK (type IN ('livraison', 'paiement', 'identification', 'partage')),
    contenu TEXT NOT NULL,
    url_qr TEXT,
    statut VARCHAR(20) DEFAULT 'actif' CHECK (statut IN ('actif', 'utilise', 'expire', 'supprime')),
    date_creation TIMESTAMPTZ DEFAULT NOW(),
    date_expiration TIMESTAMPTZ,
    date_utilisation TIMESTAMPTZ,
    scans_count INTEGER DEFAULT 0,
    metadata JSONB DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT NOW(),
    updated_at TIMESTAMPTZ DEFAULT NOW()
);

-- =====================================================
-- TABLE SESSIONS UTILISATEURS
-- =====================================================
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

-- =====================================================
-- TABLE LOGS D'ACTIVITÉ
-- =====================================================
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

-- =====================================================
-- INDEX POUR LES PERFORMANCES
-- =====================================================

-- Index pour les profils
CREATE INDEX IF NOT EXISTS idx_profiles_email ON profiles(email);
CREATE INDEX IF NOT EXISTS idx_profiles_role ON profiles(role);
CREATE INDEX IF NOT EXISTS idx_profiles_statut ON profiles(statut);
CREATE INDEX IF NOT EXISTS idx_profiles_location ON profiles(latitude, longitude);
CREATE INDEX IF NOT EXISTS idx_profiles_created_at ON profiles(created_at);

-- Index pour la file d'attente des emails
CREATE INDEX IF NOT EXISTS idx_email_queue_status ON email_queue(status);
CREATE INDEX IF NOT EXISTS idx_email_queue_user_id ON email_queue(user_id);
CREATE INDEX IF NOT EXISTS idx_email_queue_type ON email_queue(email_type);
CREATE INDEX IF NOT EXISTS idx_email_queue_created_at ON email_queue(created_at);
CREATE INDEX IF NOT EXISTS idx_email_queue_recipient ON email_queue(recipient_email);

-- Index pour les livraisons
CREATE INDEX IF NOT EXISTS idx_livraisons_client_id ON livraisons(client_id);
CREATE INDEX IF NOT EXISTS idx_livraisons_livreur_id ON livraisons(livreur_id);
CREATE INDEX IF NOT EXISTS idx_livraisons_statut ON livraisons(statut);
CREATE INDEX IF NOT EXISTS idx_livraisons_date_creation ON livraisons(date_creation);
CREATE INDEX IF NOT EXISTS idx_livraisons_location ON livraisons(latitude_depart, longitude_depart, latitude_arrivee, longitude_arrivee);

-- Index pour les paiements
CREATE INDEX IF NOT EXISTS idx_paiements_livraison_id ON paiements(livraison_id);
CREATE INDEX IF NOT EXISTS idx_paiements_client_id ON paiements(client_id);
CREATE INDEX IF NOT EXISTS idx_paiements_livreur_id ON paiements(livreur_id);
CREATE INDEX IF NOT EXISTS idx_paiements_statut ON paiements(statut);
CREATE INDEX IF NOT EXISTS idx_paiements_date_creation ON paiements(date_creation);

-- Index pour les notifications
CREATE INDEX IF NOT EXISTS idx_notifications_user_id ON notifications(user_id);
CREATE INDEX IF NOT EXISTS idx_notifications_lu ON notifications(lu);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
CREATE INDEX IF NOT EXISTS idx_notifications_date_creation ON notifications(date_creation);

-- Index pour les messages
CREATE INDEX IF NOT EXISTS idx_messages_livraison_id ON messages(livraison_id);
CREATE INDEX IF NOT EXISTS idx_messages_expediteur_id ON messages(expediteur_id);
CREATE INDEX IF NOT EXISTS idx_messages_destinataire_id ON messages(destinataire_id);
CREATE INDEX IF NOT EXISTS idx_messages_lu ON messages(lu);
CREATE INDEX IF NOT EXISTS idx_messages_date_envoi ON messages(date_envoi);

-- Index pour les évaluations
CREATE INDEX IF NOT EXISTS idx_evaluations_livraison_id ON evaluations(livraison_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_evaluateur_id ON evaluations(evaluateur_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_evalue_id ON evaluations(evalue_id);
CREATE INDEX IF NOT EXISTS idx_evaluations_note ON evaluations(note);

-- Index pour les localisations
CREATE INDEX IF NOT EXISTS idx_user_locations_user_id ON user_locations(user_id);
CREATE INDEX IF NOT EXISTS idx_user_locations_timestamp ON user_locations(timestamp);
CREATE INDEX IF NOT EXISTS idx_user_locations_location ON user_locations(latitude, longitude);

-- Index pour les codes QR
CREATE INDEX IF NOT EXISTS idx_qr_codes_user_id ON qr_codes(user_id);
CREATE INDEX IF NOT EXISTS idx_qr_codes_livraison_id ON qr_codes(livraison_id);
CREATE INDEX IF NOT EXISTS idx_qr_codes_type ON qr_codes(type);
CREATE INDEX IF NOT EXISTS idx_qr_codes_statut ON qr_codes(statut);

-- Index pour les sessions
CREATE INDEX IF NOT EXISTS idx_user_sessions_user_id ON user_sessions(user_id);
CREATE INDEX IF NOT EXISTS idx_user_sessions_token ON user_sessions(session_token);
CREATE INDEX IF NOT EXISTS idx_user_sessions_expires_at ON user_sessions(expires_at);

-- Index pour les logs d'activité
CREATE INDEX IF NOT EXISTS idx_activity_logs_user_id ON activity_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_activity_logs_action ON activity_logs(action);
CREATE INDEX IF NOT EXISTS idx_activity_logs_created_at ON activity_logs(created_at);

-- Index de recherche plein texte
CREATE INDEX IF NOT EXISTS idx_profiles_search ON profiles USING gin(to_tsvector('french', nom || ' ' || prenom || ' ' || email));
CREATE INDEX IF NOT EXISTS idx_livraisons_search ON livraisons USING gin(to_tsvector('french', titre || ' ' || description));

-- =====================================================
-- CONTRAINTES ET VÉRIFICATIONS
-- =====================================================

-- Contrainte pour s'assurer qu'un livreur ne peut pas être client sur la même livraison
ALTER TABLE livraisons ADD CONSTRAINT check_client_livreur_different 
    CHECK (client_id != livreur_id);

-- Contrainte pour les coordonnées géographiques
ALTER TABLE profiles ADD CONSTRAINT check_valid_coordinates 
    CHECK (latitude IS NULL OR (latitude >= -90 AND latitude <= 90));
ALTER TABLE profiles ADD CONSTRAINT check_valid_coordinates_long 
    CHECK (longitude IS NULL OR (longitude >= -180 AND longitude <= 180));

-- Contrainte pour les prix
ALTER TABLE livraisons ADD CONSTRAINT check_positive_prices 
    CHECK (prix_propose >= 0 AND prix_final >= 0);
ALTER TABLE paiements ADD CONSTRAINT check_positive_amounts 
    CHECK (montant > 0 AND montant_livreur >= 0);

-- Contrainte pour les notes d'évaluation
ALTER TABLE evaluations ADD CONSTRAINT check_valid_rating 
    CHECK (note >= 1 AND note <= 5);

-- Contrainte pour les distances
ALTER TABLE livraisons ADD CONSTRAINT check_positive_distance 
    CHECK (distance_km >= 0);

-- =====================================================
-- COMMENTAIRES
-- =====================================================

COMMENT ON TABLE profiles IS 'Profils des utilisateurs de la plateforme';
COMMENT ON TABLE email_queue IS 'File d''attente des emails à envoyer';
COMMENT ON TABLE livraisons IS 'Livraisons effectuées sur la plateforme';
COMMENT ON TABLE paiements IS 'Paiements associés aux livraisons';
COMMENT ON TABLE notifications IS 'Notifications envoyées aux utilisateurs';
COMMENT ON TABLE messages IS 'Messages échangés entre utilisateurs';
COMMENT ON TABLE evaluations IS 'Évaluations des utilisateurs';
COMMENT ON TABLE user_locations IS 'Localisations en temps réel des utilisateurs';
COMMENT ON TABLE pricing_settings IS 'Paramètres de tarification';
COMMENT ON TABLE coverage_zones IS 'Zones de couverture de la plateforme';
COMMENT ON TABLE qr_codes IS 'Codes QR générés par la plateforme';
COMMENT ON TABLE user_sessions IS 'Sessions actives des utilisateurs';
COMMENT ON TABLE activity_logs IS 'Logs d''activité de la plateforme'; 