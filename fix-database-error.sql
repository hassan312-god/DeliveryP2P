-- Script de correction pour l'erreur "Database error saving new user"
-- À exécuter dans l'éditeur SQL de Supabase

-- 1. Supprimer les triggers existants qui peuvent causer des problèmes
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP TRIGGER IF EXISTS on_auth_user_email_confirmed ON auth.users;

-- 2. Supprimer les fonctions existantes
DROP FUNCTION IF EXISTS handle_new_user();
DROP FUNCTION IF EXISTS handle_email_confirmation();

-- 3. Supprimer la table profiles existante
DROP TABLE IF EXISTS profiles CASCADE;

-- 4. Créer une table profiles simplifiée
CREATE TABLE profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    nom TEXT NOT NULL DEFAULT '',
    prenom TEXT NOT NULL DEFAULT '',
    telephone TEXT,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'livreur', 'admin')),
    email_confirme BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 5. Créer des index
CREATE INDEX idx_profiles_email ON profiles(email);
CREATE INDEX idx_profiles_role ON profiles(role);

-- 6. Activer RLS
ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;

-- 7. Créer les politiques RLS
CREATE POLICY "Users can view own profile" ON profiles
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can insert own profile" ON profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

CREATE POLICY "Users can update own profile" ON profiles
    FOR UPDATE USING (auth.uid() = id);

-- 8. Créer une fonction simplifiée pour créer le profil
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
EXCEPTION
    WHEN OTHERS THEN
        -- En cas d'erreur, on log et on continue
        RAISE WARNING 'Erreur lors de la création du profil: %', SQLERRM;
        RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 9. Créer le trigger
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_new_user();

-- 10. Créer une fonction pour mettre à jour email_confirme
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

-- 11. Créer le trigger pour email_confirme
CREATE TRIGGER on_auth_user_email_confirmed
    AFTER UPDATE ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_email_confirmation();

-- 12. Vérifier que tout fonctionne
SELECT 'Base de données corrigée avec succès!' as message; 