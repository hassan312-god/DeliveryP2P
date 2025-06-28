#!/bin/bash

echo "🔄 Synchronisation avec Supabase..."

# Vérifier si Supabase CLI est installé
if ! command -v supabase &> /dev/null; then
    echo "❌ Supabase CLI n'est pas installé"
    echo "📥 Installation de Supabase CLI..."
    
    # Détecter le système d'exploitation
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        # Linux
        curl -fsSL https://supabase.com/install.sh | sh
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        # macOS
        brew install supabase/tap/supabase
    elif [[ "$OSTYPE" == "msys" ]] || [[ "$OSTYPE" == "cygwin" ]]; then
        # Windows
        echo "⚠️ Sur Windows, installez Supabase CLI manuellement :"
        echo "https://supabase.com/docs/guides/cli/getting-started"
        exit 1
    else
        echo "❌ Système d'exploitation non supporté"
        exit 1
    fi
fi

# Initialiser Supabase si pas déjà fait
if [ ! -f "supabase/config.toml" ]; then
    echo "🔧 Initialisation de Supabase..."
    supabase init
fi

# Se connecter à Supabase (si pas déjà connecté)
echo "🔐 Connexion à Supabase..."
if ! supabase status &> /dev/null; then
    echo "⚠️ Veuillez vous connecter à Supabase :"
    supabase login
fi

# Lier au projet Supabase existant
echo "🔗 Liaison au projet Supabase..."
if [ ! -f "supabase/.env" ]; then
    echo "📝 Création du fichier .env Supabase..."
    cat > supabase/.env << 'EOF'
# Copiez vos variables d'environnement Supabase ici
# Vous pouvez les trouver dans votre dashboard Supabase
# Project Settings > API
SUPABASE_URL=https://syamapjohtlbjlyhlhsi.supabase.co
SUPABASE_ANON_KEY=votre_clé_anon
SUPABASE_SERVICE_ROLE_KEY=votre_clé_service
EOF
    
    echo "⚠️ Veuillez éditer supabase/.env avec vos vraies clés Supabase"
    echo "Puis relancez ce script"
    exit 1
fi

# Pousser les migrations vers Supabase
echo "📤 Pousser les migrations..."
if [ -d "supabase/migrations" ]; then
    supabase db push
else
    echo "📁 Création du dossier migrations..."
    mkdir -p supabase/migrations
    
    # Créer une migration initiale basique
    cat > supabase/migrations/20240101000000_initial_schema.sql << 'EOF'
-- Migration initiale
-- Ajoutez ici vos tables et fonctions

-- Exemple de table profiles
CREATE TABLE IF NOT EXISTS profiles (
    id UUID REFERENCES auth.users ON DELETE CASCADE,
    email TEXT,
    full_name TEXT,
    avatar_url TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT TIMEZONE('utc'::text, NOW()) NOT NULL,
    PRIMARY KEY (id)
);

-- Fonction pour mettre à jour updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Trigger pour profiles
CREATE TRIGGER update_profiles_updated_at BEFORE UPDATE ON profiles FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
EOF
    
    echo "✅ Migration initiale créée"
    supabase db push
fi

# Générer les types TypeScript (optionnel)
echo "📝 Génération des types TypeScript..."
if command -v npx &> /dev/null; then
    supabase gen types typescript --local > frontend/types/supabase.ts 2>/dev/null || echo "⚠️ Impossible de générer les types"
else
    echo "⚠️ npx non disponible, types non générés"
fi

# Créer un script de développement local
echo "🚀 Création du script de développement local..."
cat > start-dev.sh << 'EOF'
#!/bin/bash

echo "🔄 Démarrage de l'environnement de développement..."

# Démarrer Supabase local
echo "🚀 Démarrage de Supabase local..."
supabase start

# Afficher les URLs
echo "✅ Environnement de développement prêt !"
echo "Frontend: http://localhost:3000"
echo "Supabase Studio: http://localhost:54323"
echo "API Supabase: http://localhost:54321"

# Démarrer un serveur de développement simple
echo "🌐 Démarrage du serveur de développement..."
cd frontend
python3 -m http.server 3000 2>/dev/null || python -m http.server 3000 2>/dev/null || echo "⚠️ Python non disponible, servez les fichiers manuellement"
EOF

chmod +x start-dev.sh

echo "✅ Synchronisation terminée !"
echo "📋 Prochaines étapes :"
echo "1. Éditer supabase/.env avec vos vraies clés"
echo "2. Lancer ./start-dev.sh pour le développement local"
echo "3. Vérifier la connexion avec ./test-supabase-connection.sh" 