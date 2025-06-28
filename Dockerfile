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
