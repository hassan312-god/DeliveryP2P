#!/bin/sh

# Script de démarrage pour LivraisonP2P sur Render
# Gestion des variables d'environnement et démarrage du serveur

set -e

echo "🚀 Démarrage de LivraisonP2P..."

# Vérification des variables d'environnement critiques
if [ -z "$SUPABASE_URL" ]; then
    echo "❌ ERREUR: SUPABASE_URL non définie"
    exit 1
fi

if [ -z "$SUPABASE_ANON_KEY" ]; then
    echo "❌ ERREUR: SUPABASE_ANON_KEY non définie"
    exit 1
fi

if [ -z "$JWT_SECRET" ]; then
    echo "❌ ERREUR: JWT_SECRET non définie"
    exit 1
fi

echo "✅ Variables d'environnement vérifiées"

# Création des répertoires de stockage si nécessaire
mkdir -p storage/{logs,cache,qr_codes,uploads}
chmod -R 755 storage

echo "✅ Répertoires de stockage créés"

# Vérification de la configuration PHP
php -m | grep -E "(pdo|zip|mbstring|gd|xml|intl|opcache|bcmath)" || {
    echo "❌ ERREUR: Extensions PHP manquantes"
    exit 1
}

echo "✅ Extensions PHP vérifiées"

# Test de connexion à Supabase (optionnel)
if [ "$APP_ENV" = "production" ]; then
    echo "🔍 Test de connexion Supabase..."
    php -r "
    require_once 'vendor/autoload.php';
    require_once 'config.php';
    try {
        \$db = \DeliveryP2P\Utils\Database::getInstance();
        \$test = \$db->testConnection();
        if (\$test['success']) {
            echo '✅ Connexion Supabase OK\n';
        } else {
            echo '⚠️  Connexion Supabase: ' . \$test['message'] . '\n';
        }
    } catch (Exception \$e) {
        echo '⚠️  Erreur connexion Supabase: ' . \$e->getMessage() . '\n';
    }
    "
fi

# Démarrage du serveur PHP
echo "🌐 Démarrage du serveur PHP sur le port $PORT..."

# Configuration du serveur PHP
exec php -S 0.0.0.0:$PORT -t public \
    -d memory_limit=256M \
    -d max_execution_time=30 \
    -d display_errors=0 \
    -d log_errors=1 \
    -d error_log=/dev/stderr 