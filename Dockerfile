# Dockerfile optimisé pour Render - LivraisonP2P
# Adapté à la structure frontend/ + api/

FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache - Activation des modules nécessaires
RUN a2enmod rewrite && a2enmod headers && a2enmod expires && a2enmod deflate

# Configuration du DocumentRoot pour servir depuis frontend/
RUN echo "DocumentRoot /var/www/html/frontend" > /etc/apache2/sites-available/000-default.conf

# Définition du répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de l'application
COPY . /var/www/html/

# Installation des dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Configuration PHP pour la production
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/custom.ini

# Configuration Apache pour les routes API
RUN echo '<Directory /var/www/html>' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    RewriteEngine On' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    RewriteCond %{REQUEST_URI} ^/api/' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    RewriteRule ^api/(.*)$ /api/index.php [QSA,L]' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    RewriteCond %{REQUEST_URI} ^/health$' >> /etc/apache2/sites-available/000-default.conf \
    && echo '    RewriteRule ^health$ /api/health.php [QSA,L]' >> /etc/apache2/sites-available/000-default.conf \
    && echo '</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Rendre le script de démarrage exécutable
RUN chmod +x /var/www/html/start.sh

# Exposition du port
EXPOSE 80

# Commande de démarrage
CMD ["/var/www/html/start.sh"] 