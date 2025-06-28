# 🔧 Guide de Dépannage - Erreurs de Build Render

## 🚨 Erreur Alpine Linux

### Problème
```
WARNING: opening from cache https://dl-cdn.alpinelinux.org/alpine/v3.22/main: No such file or directory
WARNING: opening from cache https://dl-cdn.alpinelinux.org/alpine/v3.22/community: No such file or directory
```

### Solution 1: Utiliser le Dockerfile.render (Recommandé)

```bash
# Renommez le Dockerfile actuel
mv Dockerfile Dockerfile.alpine

# Utilisez la version optimisée pour Render
cp Dockerfile.render Dockerfile
```

### Solution 2: Corriger le Dockerfile Alpine

Si vous voulez garder Alpine, modifiez le Dockerfile :

```dockerfile
# Utilisez une version Alpine stable
FROM php:8.2-fpm-alpine:3.19

# Mettez à jour les dépôts avant installation
RUN apk update && apk upgrade

# Le reste de votre Dockerfile...
```

### Solution 3: Utiliser Ubuntu (Plus Stable)

```dockerfile
FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuration Apache
RUN a2enmod rewrite

# Répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers
COPY . .

# Installation des dépendances
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Exposition du port
EXPOSE 80

# Démarrage
CMD ["apache2-foreground"]
```

## 🔄 Déploiement avec Correction

### Étape 1: Appliquer la Correction

```bash
# Utilisez le Dockerfile.render
cp Dockerfile.render Dockerfile

# Commitez les changements
git add Dockerfile
git commit -m "Fix Alpine Linux issues for Render deployment"
git push origin main
```

### Étape 2: Redéployer sur Render

1. **Dashboard Render** → Votre service
2. **Manual Deploy** → "Deploy latest commit"
3. **Surveillez les logs** pour vérifier le succès

### Étape 3: Vérifier le Déploiement

```bash
# Test de santé
curl https://your-app-name.onrender.com/api/health

# Test de connexion
curl https://your-app-name.onrender.com/api/test-connection
```

## 🐛 Autres Erreurs Courantes

### Erreur: "composer install failed"

**Solution** :
```dockerfile
# Ajoutez cette ligne avant composer install
RUN composer config --global process-timeout 2000
RUN composer install --optimize-autoloader --no-dev --no-interaction --no-scripts
```

### Erreur: "permission denied"

**Solution** :
```dockerfile
# Ajoutez ces lignes après la copie des fichiers
RUN chmod -R 755 /var/www/html
RUN chown -R www-data:www-data /var/www/html
```

### Erreur: "port already in use"

**Solution** :
```dockerfile
# Utilisez le port 80 au lieu de 8000
EXPOSE 80
CMD ["apache2-foreground"]
```

## 📋 Checklist de Dépannage

- [ ] Dockerfile.render utilisé
- [ ] Variables d'environnement configurées
- [ ] Composer.json valide
- [ ] Permissions correctes
- [ ] Port configuré (80)
- [ ] Health check path correct (/api/health)

## 🚀 Configuration Optimale pour Render

### Dockerfile Final (Recommandé)

```dockerfile
FROM php:8.2-apache

# Dépendances système
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Apache
RUN a2enmod rewrite
RUN echo "DocumentRoot /var/www/html/public" > /etc/apache2/sites-available/000-default.conf

# Application
WORKDIR /var/www/html
COPY . .

# Dépendances
RUN composer install --optimize-autoloader --no-dev --no-interaction

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && mkdir -p storage/{logs,cache,qr_codes,uploads}

# Configuration PHP
RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors = Off" >> /usr/local/etc/php/conf.d/custom.ini

EXPOSE 80
CMD ["apache2-foreground"]
```

### render.yaml Simplifié

```yaml
services:
  - type: web
    name: deliveryp2p-api
    runtime: docker
    plan: starter
    healthCheckPath: /api/health
    envVars:
      - key: APP_ENV
        value: "production"
      - key: APP_DEBUG
        value: "false"
      # Ajoutez vos variables d'environnement ici
    autoDeploy: true
    branch: main
```

## 🎯 Résultat Attendu

Après correction, votre build devrait réussir et vous devriez voir :

```
✅ Build completed successfully
✅ Service deployed
✅ Health check passed
```

Votre API sera accessible sur : `https://your-app-name.onrender.com` 