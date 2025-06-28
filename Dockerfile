# Dockerfile optimisé pour Render
# PHP 8.2 avec extensions requises pour LivraisonP2P

FROM php:8.2-fpm-alpine

# Variables d'environnement
ENV PHP_VERSION=8.2
ENV APP_ENV=production

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    curl \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libxml2-dev \
    icu-dev \
    autoconf \
    g++ \
    make \
    linux-headers \
    && rm -rf /var/cache/apk/*

# Installation des extensions PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        zip \
        mbstring \
        gd \
        xml \
        intl \
        opcache \
        bcmath

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur non-root
RUN addgroup -g 1000 appuser && \
    adduser -D -s /bin/sh -u 1000 -G appuser appuser

# Configuration PHP pour la production
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers de l'application
COPY . .

# Installation des dépendances PHP
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts

# Création des répertoires de stockage
RUN mkdir -p storage/{logs,cache,qr_codes,uploads} && \
    chmod -R 755 storage && \
    chown -R appuser:appuser storage

# Permissions pour les fichiers
RUN chown -R appuser:appuser /var/www/html

# Utilisateur non-root
USER appuser

# Exposition du port
EXPOSE 8000

# Script de démarrage
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Point d'entrée
ENTRYPOINT ["/start.sh"] 