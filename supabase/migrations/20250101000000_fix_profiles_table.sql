-- Migration pour corriger la table profiles de LivraisonP2P
-- Date: 2025-01-01

-- 1. Supprimer la table profiles existante si elle existe
DROP TABLE IF EXISTS profiles CASCADE;

-- 2. Créer la table profiles avec la structure correcte
CREATE TABLE profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    nom TEXT NOT NULL,
    prenom TEXT NOT NULL,
    telephone TEXT,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'livreur', 'admin')),
    email_confirme BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 3. Créer des index pour les performances
CREATE INDEX idx_profiles_email ON profiles(email);
CREATE INDEX idx_profiles_role ON profiles(role);
CREATE INDEX idx_profiles_is_active ON profiles(is_active);

-- 4. Activer Row Level Security (RLS)
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;

-- 5. Créer les politiques RLS pour la sécurité
CREATE POLICY "Users can view own profile" ON profiles
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can insert own profile" ON profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can update own profile" ON profiles
    FOR UPDATE USING (auth.uid() = id);

CREATE POLICY "Admins can view all profiles" ON profiles
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

CREATE POLICY "Admins can update all profiles" ON profiles
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- 6. Créer une fonction pour mettre à jour automatiquement updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- 7. Créer un trigger pour mettre à jour automatiquement updated_at
CREATE TRIGGER update_profiles_updated_at 
    BEFORE UPDATE ON profiles 
    FOR EACH ROW 
    EXECUTE FUNCTION update_updated_at_column();

-- 8. Créer une fonction pour créer automatiquement un profil lors de l'inscription
CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
BEGIN
    INSERT INTO profiles (id, email, nom, prenom, telephone, role, email_confirme, is_active)
    VALUES (
        NEW.id,
        NEW.email,
        COALESCE(NEW.raw_user_meta_data->>'nom', ''),
        COALESCE(NEW.raw_user_meta_data->>'prenom', ''),
        NEW.raw_user_meta_data->>'telephone',
        COALESCE(NEW.raw_user_meta_data->>'role', 'client'),
        FALSE,
        TRUE
    );
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 9. Créer un trigger pour créer automatiquement un profil lors de l'inscription
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_new_user();

-- 10. Créer une fonction pour mettre à jour email_confirme quand l'email est confirmé
CREATE OR REPLACE FUNCTION handle_email_confirmation()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.email_confirmed_at IS NOT NULL AND OLD.email_confirmed_at IS NULL THEN
        UPDATE profiles 
        SET email_confirme = TRUE 
        WHERE id = NEW.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 11. Créer un trigger pour mettre à jour email_confirme
DROP TRIGGER IF EXISTS on_auth_user_email_confirmed ON auth.users;
CREATE TRIGGER on_auth_user_email_confirmed
    AFTER UPDATE ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_email_confirmation();

-- 12. Créer une vue pour les profils avec informations d'authentification
CREATE OR REPLACE VIEW profiles_with_auth AS
SELECT 
    p.*,
    u.email_confirmed_at,
    u.created_at as auth_created_at,
    u.last_sign_in_at
FROM profiles p
JOIN auth.users u ON p.id = u.id;

-- 13. Créer des fonctions utilitaires
CREATE OR REPLACE FUNCTION get_current_user_profile()
RETURNS TABLE (
    id UUID,
    email TEXT,
    nom TEXT,
    prenom TEXT,
    telephone TEXT,
    role TEXT,
    email_confirme BOOLEAN,
    is_active BOOLEAN,
    created_at TIMESTAMP WITH TIME ZONE,
    updated_at TIMESTAMP WITH TIME ZONE
) AS $$
BEGIN
    RETURN QUERY
    SELECT 
        p.id,
        p.email,
        p.nom,
        p.prenom,
        p.telephone,
        p.role,
        p.email_confirme,
        p.is_active,
        p.created_at,
        p.updated_at
    FROM profiles p
    WHERE p.id = auth.uid();
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 14. Ajouter des contraintes de validation
ALTER TABLE profiles 
ADD CONSTRAINT check_telephone_format 
CHECK (telephone IS NULL OR telephone ~ '^[\+]?[0-9\s\-\(\)]{8,15}$');

ALTER TABLE profiles 
ADD CONSTRAINT check_email_format 
CHECK (email ~ '^[^\s@]+@[^\s@]+\.[^\s@]+$');

-- 15. Créer des commentaires pour la documentation
COMMENT ON TABLE profiles IS 'Table des profils utilisateurs de LivraisonP2P';
COMMENT ON COLUMN profiles.id IS 'ID de l''utilisateur (référence vers auth.users)';
COMMENT ON COLUMN profiles.email IS 'Adresse email de l''utilisateur';
COMMENT ON COLUMN profiles.nom IS 'Nom de famille de l''utilisateur';
COMMENT ON COLUMN profiles.prenom IS 'Prénom de l''utilisateur';
COMMENT ON COLUMN profiles.telephone IS 'Numéro de téléphone (optionnel)';
COMMENT ON COLUMN profiles.role IS 'Rôle de l''utilisateur (client, livreur, admin)';
COMMENT ON COLUMN profiles.email_confirme IS 'Indique si l''email a été confirmé';
COMMENT ON COLUMN profiles.is_active IS 'Indique si le compte est actif';
COMMENT ON COLUMN profiles.created_at IS 'Date de création du profil';
COMMENT ON COLUMN profiles.updated_at IS 'Date de dernière modification du profil'; 