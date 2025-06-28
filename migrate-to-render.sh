#!/bin/bash

echo "🚀 Migration vers Render - Restructuration du projet..."

# Créer la nouvelle structure
echo "📁 Création de la nouvelle structure de dossiers..."

# Créer les dossiers
mkdir -p backend frontend

# Déplacer les fichiers PHP
echo "📦 Déplacement des fichiers back-end..."
if [ -d "php" ]; then
    mv php/* backend/ 2>/dev/null || echo "Dossier php/ déjà vide ou inexistant"
    rmdir php 2>/dev/null || echo "Dossier php/ supprimé"
else
    echo "Dossier php/ n'existe pas"
fi

# Déplacer les fichiers front-end
echo "📦 Déplacement des fichiers front-end..."
[ -d "js" ] && mv js frontend/ && echo "Dossier js/ déplacé"
[ -d "css" ] && mv css frontend/ && echo "Dossier css/ déplacé"
[ -d "assets" ] && mv assets frontend/ && echo "Dossier assets/ déplacé"
[ -d "client" ] && mv client frontend/ && echo "Dossier client/ déplacé"
[ -d "admin" ] && mv admin frontend/ && echo "Dossier admin/ déplacé"
[ -d "courier" ] && mv courier frontend/ && echo "Dossier courier/ déplacé"
[ -d "auth" ] && mv auth frontend/ && echo "Dossier auth/ déplacé"

# Déplacer les fichiers PHP individuels
echo "📄 Déplacement des fichiers PHP individuels..."
for file in *.php; do
    if [ -f "$file" ]; then
        mv "$file" backend/ && echo "Fichier $file déplacé vers backend/"
    fi
done

# Déplacer les fichiers HTML
echo "📄 Déplacement des fichiers HTML..."
for file in *.html; do
    if [ -f "$file" ]; then
        mv "$file" frontend/ && echo "Fichier $file déplacé vers frontend/"
    fi
done

# Déplacer les fichiers de configuration front-end
echo "⚙️ Déplacement des fichiers de configuration..."
[ -f "config.js" ] && mv config.js frontend/ && echo "config.js déplacé"
[ -f "manifest.json" ] && mv manifest.json frontend/ && echo "manifest.json déplacé"
[ -f "sw.js" ] && mv sw.js frontend/ && echo "sw.js déplacé"

# Créer le Dockerfile principal
echo "🐳 Création du Dockerfile principal..."
cat > Dockerfile << 'EOF'
# Utilise une image PHP avec Apache
FROM php:8.2-apache

# Copier le back-end dans un sous-dossier
COPY backend/ /var/www/html/backend/

# Copier le front-end à la racine
COPY frontend/ /var/www/html/

# Activer les modules Apache nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activer mod_rewrite pour les routes propres
RUN a2enmod rewrite

# Configuration Apache pour servir le front-end
RUN echo "DocumentRoot /var/www/html" > /etc/apache2/sites-available/000-default.conf

# Créer le dossier logs s'il n'existe pas
RUN mkdir -p /var/www/html/backend/logs

# Définir le dossier de travail
WORKDIR /var/www/html

# Expose le port 80
EXPOSE 80
EOF

# Créer .dockerignore
echo "🚫 Création du .dockerignore..."
cat > .dockerignore << 'EOF'
.git
.gitignore
README.md
node_modules
*.log
.env
.DS_Store
migrate-to-render.sh
update-urls.sh
deploy-to-render.sh
sync-supabase.sh
EOF

# Créer .htaccess pour le front-end
echo "🔧 Création du .htaccess front-end..."
cat > frontend/.htaccess << 'EOF'
# Rediriger toutes les routes vers index.html (SPA)
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.html [QSA,L]

# Autoriser l'accès aux fichiers statiques
<FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>
EOF

echo "✅ Migration terminée !"
echo "📋 Prochaines étapes :"
echo "1. Vérifier la structure créée"
echo "2. Mettre à jour les URLs dans le code"
echo "3. Tester localement"
echo "4. Déployer sur Render" 