# Utilise une image PHP avec Apache
FROM php:8.2-apache

# Copie tout le code du dossier php dans le dossier web d'Apache
COPY . /var/www/html/

# Active les modules Apache nécessaires
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Active mod_rewrite pour les routes propres (optionnel)
RUN a2enmod rewrite

# (Optionnel) Pour les logs, crée le dossier s'il n'existe pas
RUN mkdir -p /var/www/html/logs

# Définit le dossier de travail
WORKDIR /var/www/html

# Expose le port 80
EXPOSE 80 