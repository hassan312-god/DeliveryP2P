-- =====================================================
-- CORRECTION COMPLÈTE - LivraisonP2P
-- Résolution de l'erreur "Database error saving new user"
-- =====================================================

-- 1. SUPPRESSION DES ÉLÉMENTS EXISTANTS
-- =====================================

-- Supprimer les triggers existants
DROP TRIGGER IF EXISTS on_auth_user_created ON auth.users;
DROP TRIGGER IF EXISTS on_auth_user_email_confirmed ON auth.users;
DROP TRIGGER IF EXISTS handle_new_user ON auth.users;
DROP TRIGGER IF EXISTS handle_email_confirmation ON auth.users;

-- Supprimer les fonctions existantes
DROP FUNCTION IF EXISTS handle_new_user();
DROP FUNCTION IF EXISTS handle_email_confirmation();
DROP FUNCTION IF EXISTS public.handle_new_user();
DROP FUNCTION IF EXISTS public.handle_email_confirmation();

-- Supprimer la table profiles existante
DROP TABLE IF EXISTS profiles CASCADE;
DROP TABLE IF EXISTS public.profiles CASCADE;

-- 2. CRÉATION DE LA TABLE PROFILES
-- ================================

CREATE TABLE profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    nom TEXT NOT NULL DEFAULT '',
    prenom TEXT NOT NULL DEFAULT '',
    telephone TEXT,
    role TEXT NOT NULL DEFAULT 'client' CHECK (role IN ('client', 'livreur', 'courier', 'admin')),
    email_confirme BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- 3. CRÉATION DES INDEX
-- =====================

CREATE INDEX idx_profiles_email ON profiles(email);
CREATE INDEX idx_profiles_role ON profiles(role);
CREATE INDEX idx_profiles_active ON profiles(is_active);

-- 4. ACTIVATION DE ROW LEVEL SECURITY
-- ===================================

ALTER TABLE profiles ENABLE ROW LEVEL SECURITY;

-- 5. CRÉATION DES POLITIQUES RLS
-- ==============================

-- Politique pour voir son propre profil
CREATE POLICY "Users can view own profile" ON profiles
    FOR SELECT USING (auth.uid() = id);

-- Politique pour insérer son propre profil
CREATE POLICY "Users can insert own profile" ON profiles
    FOR INSERT WITH CHECK (auth.uid() = id);

-- Politique pour mettre à jour son propre profil
CREATE POLICY "Users can update own profile" ON profiles
    FOR UPDATE USING (auth.uid() = id);

-- Politique pour les admins (voir tous les profils)
CREATE POLICY "Admins can view all profiles" ON profiles
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM profiles 
            WHERE id = auth.uid() AND role = 'admin'
        )
    );

-- 6. CRÉATION DE LA FONCTION POUR CRÉER LE PROFIL
-- ===============================================

CREATE OR REPLACE FUNCTION handle_new_user()
RETURNS TRIGGER AS $$
DECLARE
    user_email TEXT;
    user_nom TEXT;
    user_prenom TEXT;
    user_telephone TEXT;
    user_role TEXT;
BEGIN
    -- Récupérer les données de l'utilisateur
    user_email := NEW.email;
    user_nom := COALESCE(NEW.raw_user_meta_data->>'nom', '');
    user_prenom := COALESCE(NEW.raw_user_meta_data->>'prenom', '');
    user_telephone := NEW.raw_user_meta_data->>'telephone';
    user_role := COALESCE(NEW.raw_user_meta_data->>'role', 'client');
    
    -- Log pour debug
    RAISE NOTICE 'Création profil pour utilisateur: % (email: %, nom: %, prénom: %, téléphone: %, rôle: %)', 
        NEW.id, user_email, user_nom, user_prenom, user_telephone, user_role;
    
    -- Insérer le profil
    INSERT INTO profiles (id, email, nom, prenom, telephone, role, email_confirme, is_active)
    VALUES (
        NEW.id,
        user_email,
        user_nom,
        user_prenom,
        user_telephone,
        user_role,
        FALSE,
        TRUE
    );
    
    RAISE NOTICE 'Profil créé avec succès pour l\'utilisateur: %', NEW.id;
    RETURN NEW;
    
EXCEPTION
    WHEN OTHERS THEN
        -- En cas d'erreur, on log et on continue
        RAISE WARNING 'Erreur lors de la création du profil pour l''utilisateur %: %', NEW.id, SQLERRM;
        RAISE WARNING 'Détails: email=%, nom=%, prénom=%, téléphone=%, rôle=%', 
            user_email, user_nom, user_prenom, user_telephone, user_role;
        RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 7. CRÉATION DE LA FONCTION POUR EMAIL CONFIRMÉ
-- ==============================================

CREATE OR REPLACE FUNCTION handle_email_confirmation()
RETURNS TRIGGER AS $$
BEGIN
    -- Si l'email vient d'être confirmé
    IF NEW.email_confirmed_at IS NOT NULL AND OLD.email_confirmed_at IS NULL THEN
        UPDATE profiles 
        SET email_confirme = TRUE, updated_at = NOW()
        WHERE id = NEW.id;
        
        RAISE NOTICE 'Email confirmé pour l''utilisateur: %', NEW.id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 8. CRÉATION DES TRIGGERS
-- ========================

-- Trigger pour créer le profil lors de l'inscription
CREATE TRIGGER on_auth_user_created
    AFTER INSERT ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_new_user();

-- Trigger pour mettre à jour email_confirme
CREATE TRIGGER on_auth_user_email_confirmed
    AFTER UPDATE ON auth.users
    FOR EACH ROW EXECUTE FUNCTION handle_email_confirmation();

-- 9. CRÉATION D'UN UTILISATEUR ADMIN PAR DÉFAUT (OPTIONNEL)
-- =========================================================

-- Décommentez les lignes suivantes si vous voulez créer un admin par défaut
-- INSERT INTO auth.users (id, email, encrypted_password, email_confirmed_at, created_at, updated_at, raw_user_meta_data)
-- VALUES (
--     gen_random_uuid(),
--     'admin@livraisonp2p.com',
--     crypt('admin123', gen_salt('bf')),
--     NOW(),
--     NOW(),
--     NOW(),
--     '{"nom": "Admin", "prenom": "Super", "role": "admin"}'::jsonb
-- );

-- 10. VÉRIFICATIONS FINALES
-- =========================

-- Vérifier que la table existe
SELECT 'Table profiles créée avec succès' as message;

-- Vérifier les triggers
SELECT 
    trigger_name,
    event_object_table,
    action_timing,
    event_manipulation
FROM information_schema.triggers 
WHERE event_object_table = 'users';

-- Vérifier les politiques RLS
SELECT 
    schemaname,
    tablename,
    policyname,
    permissive,
    roles,
    cmd,
    qual
FROM pg_policies 
WHERE tablename = 'profiles';

-- Vérifier la structure de la table
SELECT 
    column_name,
    data_type,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'profiles'
ORDER BY ordinal_position;

-- Message de succès
SELECT 'CORRECTION TERMINÉE AVEC SUCCÈS!' as status; 