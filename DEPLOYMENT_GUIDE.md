# 🚀 Guide de Déploiement Render - LivraisonP2P

## 📋 Prérequis

1. **Compte Render** : [render.com](https://render.com)
2. **Projet Supabase** : [supabase.com](https://supabase.com)
3. **Repository Git** : GitHub ou GitLab
4. **Clés de sécurité** : Générez des clés de 32 caractères

## 🔧 Configuration Initiale

### 1. Préparation du Repository

```bash
# Vérifiez que tous les fichiers sont commités
git add .
git commit -m "Architecture complète pour Render"
git push origin main
```

### 2. Variables d'Environnement Critiques

Dans le dashboard Render, configurez ces variables **OBLIGATOIRES** :

```
# Supabase (récupérez depuis votre dashboard Supabase > Settings > API)
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Sécurité (générez des clés de 32 caractères minimum)
JWT_SECRET=your-32-character-jwt-secret-key-here
ENCRYPTION_KEY=your-32-character-encryption-key-here
QR_CODE_SECRET=your-qr-code-specific-secret-here
PASSWORD_SALT=your-password-salt-here

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app-name.onrender.com
APP_NAME=LivraisonP2P
```

## 🐳 Solutions aux Erreurs de Build

### Erreur 1: "failed to parse stage name"

**Problème** : Syntaxe Docker incompatible avec Render

**Solution** : Utilisez le Dockerfile simplifié

```bash
# Renommez le Dockerfile actuel
mv Dockerfile Dockerfile.complex

# Utilisez la version simplifiée
cp Dockerfile.simple Dockerfile
```

### Erreur 2: "composer install failed"

**Problème** : Dépendances PHP manquantes

**Solution** : Vérifiez le composer.json

```json
{
    "require": {
        "php": ">=8.2",
        "vlucas/phpdotenv": "^5.5",
        "firebase/php-jwt": "^6.0"
    }
}
```

### Erreur 3: "permission denied"

**Problème** : Permissions de fichiers

**Solution** : Ajoutez dans le Dockerfile

```dockerfile
RUN chmod -R 755 /var/www/html
RUN chown -R www-data:www-data /var/www/html
```

## 🔄 Déploiement Étape par Étape

### Étape 1: Créer le Service Web

1. **Dashboard Render** → "New +" → "Web Service"
2. **Connectez votre Git** (GitHub/GitLab)
3. **Sélectionnez le repository** : `DeliveryP2P`
4. **Branche** : `main`

### Étape 2: Configuration du Service

```yaml
Name: deliveryp2p-api
Environment: Docker
Region: Frankfurt (EU Central)
Branch: main
Root Directory: ./
Build Command: (laissé vide)
Start Command: (laissé vide)
```

### Étape 3: Variables d'Environnement

Dans l'onglet **Environment** :

1. **Ajoutez les variables critiques** (voir section ci-dessus)
2. **Vérifiez les valeurs** (pas d'espaces en début/fin)
3. **Sauvegardez** les modifications

### Étape 4: Configuration Supabase

1. **Créez un projet Supabase**
2. **Récupérez les clés API** :
   - Project URL
   - Anon Key
   - Service Role Key

3. **Créez les tables** dans l'éditeur SQL :

```sql
-- Table des utilisateurs
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des livraisons
CREATE TABLE deliveries (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    sender_id UUID REFERENCES users(id),
    courier_id UUID REFERENCES users(id),
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    pickup_latitude DECIMAL(10, 8),
    pickup_longitude DECIMAL(11, 8),
    delivery_latitude DECIMAL(10, 8),
    delivery_longitude DECIMAL(11, 8),
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des QR codes
CREATE TABLE qr_codes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    delivery_id UUID REFERENCES deliveries(id),
    type VARCHAR(50) NOT NULL,
    data JSONB NOT NULL,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Table des scans QR
CREATE TABLE qr_scans (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    qr_id UUID REFERENCES qr_codes(id),
    scanned_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    ip_address INET,
    user_agent TEXT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    device_info JSONB
);
```

## 🧪 Tests de Déploiement

### Test 1: Health Check

```bash
curl https://your-app-name.onrender.com/api/health
```

**Réponse attendue** :
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2024-01-01T12:00:00Z",
  "version": "2.0.0"
}
```

### Test 2: Test de Connexion

```bash
curl https://your-app-name.onrender.com/api/test-connection
```

### Test 3: Page d'Accueil

```bash
curl https://your-app-name.onrender.com
```

## 🔍 Dépannage

### Problème: Build échoue

1. **Vérifiez les logs** dans le dashboard Render
2. **Testez localement** :
   ```bash
   docker build -t deliveryp2p .
   docker run -p 8000:8000 deliveryp2p
   ```

### Problème: Variables d'environnement

1. **Vérifiez l'orthographe** des variables
2. **Pas d'espaces** en début/fin des valeurs
3. **Clés de 32 caractères** minimum pour la sécurité

### Problème: Connexion Supabase

1. **Vérifiez les clés API** dans Supabase
2. **Testez la connexion** :
   ```bash
   curl -H "apikey: YOUR_ANON_KEY" \
        -H "Authorization: Bearer YOUR_ANON_KEY" \
        https://your-project.supabase.co/rest/v1/
   ```

### Problème: Health Check échoue

1. **Vérifiez les logs** de l'application
2. **Testez les endpoints** individuellement
3. **Vérifiez les permissions** des fichiers

## 📊 Monitoring

### Logs Render

1. **Dashboard** → Votre service → Onglet "Logs"
2. **Filtrez par niveau** : ERROR, WARN, INFO
3. **Surveillez les erreurs** de démarrage

### Métriques

1. **Temps de réponse** API
2. **Utilisation mémoire**
3. **Taux d'erreur**
4. **Connexions Supabase**

## 🚀 URLs Finales

- **API Health** : `https://your-app-name.onrender.com/api/health`
- **Test Connexion** : `https://your-app-name.onrender.com/api/test-connection`
- **Page d'Accueil** : `https://your-app-name.onrender.com`
- **Documentation** : `https://your-app-name.onrender.com/api/docs`

## ✅ Checklist de Déploiement

- [ ] Repository Git configuré
- [ ] Variables d'environnement configurées
- [ ] Projet Supabase créé
- [ ] Tables créées dans Supabase
- [ ] Service Render créé
- [ ] Build réussi
- [ ] Health check OK
- [ ] Tests de connexion OK
- [ ] Page d'accueil accessible

## 🆘 Support

Si vous rencontrez des problèmes :

1. **Vérifiez les logs** Render
2. **Testez localement** avec Docker
3. **Vérifiez la configuration** Supabase
4. **Consultez la documentation** Render

Votre API LivraisonP2P sera maintenant accessible en ligne ! 🎉 