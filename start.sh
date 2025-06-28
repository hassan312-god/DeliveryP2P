#!/bin/bash

# Script de démarrage pour LivraisonP2P
echo "🚀 Démarrage de LivraisonP2P..."

# Création des répertoires de stockage s'ils n'existent pas
echo "📁 Création des répertoires de stockage..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/cache
mkdir -p /var/www/html/storage/qr_codes
mkdir -p /var/www/html/storage/uploads

# Configuration des permissions
echo "🔐 Configuration des permissions..."
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 755 /var/www/html/storage
chmod -R 755 /var/www/html/frontend

# Vérification de la configuration
echo "✅ Configuration terminée"
echo "🌐 Démarrage d'Apache..."

# Démarrage d'Apache en premier plan
exec apache2-foreground 