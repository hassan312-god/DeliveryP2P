# 🚀 LivraisonP2P - Plateforme de Livraison P2P Moderne

**Application web de livraison entre particuliers** avec système de sécurité avancé basé sur des **QR codes cryptographiques** pour garantir l'intégrité et la traçabilité des livraisons.

## 🏗️ Architecture Moderne

### Stack Technique
- **Backend :** PHP 8.2+ (Architecture Hexagonale)
- **Base de données :** Supabase (PostgreSQL + Auth + Storage)
- **Déploiement :** Render
- **Sécurité :** QR codes AES-256 + Signatures numériques
- **Frontend :** PWA moderne avec scanner QR natif

### Architecture Hexagonale
```
deliveryp2p/
├── src/
│   ├── core/              # Cœur de l'application
│   │   ├── Router.php     # Routeur moderne
│   │   ├── Middleware/    # Middlewares sécurité
│   │   └── Exceptions/    # Gestion d'erreurs
│   ├── services/          # Logique métier
│   │   ├── QRCodeService.php    # Système QR sécurisé
│   │   ├── DeliveryService.php  # Orchestration livraisons
│   │   └── NotificationService.php # Notifications temps réel
│   ├── controllers/       # Contrôleurs API
│   ├── models/           # Modèles de données
│   └── utils/            # Utilitaires
├── api/                  # Point d'entrée API
├── frontend/             # Interface utilisateur
└── storage/              # Stockage local
```

## 🔐 Système QR Code Sécurisé

### Fonctionnalités Avancées
- **Chiffrement AES-256** des données sensibles
- **Signatures numériques HMAC-SHA256** pour anti-fraude
- **Validation géolocalisée** (rayon autorisé)
- **Expiration temporelle** configurable
- **Historique complet** des scans
- **Révocation en temps réel**

### Format QR Code v2.0
```json
{
  "data": "AES-256-encrypted-data",
  "sig": "HMAC-SHA256-signature",
  "v": "2.0"
}
```

## 🚀 Déploiement Render

### Prérequis
1. Compte Render.com
2. Projet Supabase configuré
3. Repository GitHub/GitLab

### Configuration Render Dashboard

#### 1. Création du Service Web
1. Connectez-vous à [Render Dashboard](https://dashboard.render.com)
2. Cliquez sur "New +" → "Web Service"
3. Connectez votre repository GitHub/GitLab
4. Configurez le service :

```yaml
Name: deliveryp2p-api
Environment: PHP
Build Command: composer install --optimize-autoloader --no-dev --no-interaction
Start Command: php -S 0.0.0.0:$PORT -t public
Health Check Path: /api/health
```

#### 2. Variables d'Environnement Requises

**Configuration Supabase (depuis API Settings) :**
```
SUPABASE_URL=https://[your-project-ref].supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

**Configuration Application :**
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://[your-app-name].onrender.com
JWT_SECRET=your-32-character-jwt-secret-key
ENCRYPTION_KEY=your-32-character-encryption-key
QR_CODE_SECRET=your-qr-specific-secret-key
```

**Configuration Sécurité :**
```
PASSWORD_SALT=your-password-salt
QR_ENCRYPTION_KEY=your-qr-encryption-key
```

**Configuration Paiements (optionnel) :**
```
STRIPE_PUBLIC_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
```

**Configuration Notifications (optionnel) :**
```
FIREBASE_SERVER_KEY=AAAA...
SENDGRID_API_KEY=SG...
TWILIO_ACCOUNT_SID=AC...
TWILIO_AUTH_TOKEN=...
```

### 3. Configuration Supabase

#### Tables Requises
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

#### Row Level Security (RLS)
```sql
-- Activation RLS
ALTER TABLE users ENABLE ROW LEVEL SECURITY;
ALTER TABLE deliveries ENABLE ROW LEVEL SECURITY;
ALTER TABLE qr_codes ENABLE ROW LEVEL SECURITY;
ALTER TABLE qr_scans ENABLE ROW LEVEL SECURITY;

-- Policies pour les utilisateurs
CREATE POLICY "Users can view own profile" ON users
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Users can update own profile" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Policies pour les livraisons
CREATE POLICY "Users can view own deliveries" ON deliveries
    FOR SELECT USING (auth.uid() = sender_id OR auth.uid() = courier_id);

CREATE POLICY "Users can create deliveries" ON deliveries
    FOR INSERT WITH CHECK (auth.uid() = sender_id);
```

### 4. Tests de Connexion

#### Endpoint de Santé
```bash
curl https://your-app-name.onrender.com/api/health
```

#### Test de Connexion Supabase
```bash
curl https://your-app-name.onrender.com/api/test-connection
```

### 5. Monitoring et Logs

#### Logs Render
- Accédez aux logs via le dashboard Render
- Logs structurés en JSON
- Rotation automatique des logs

#### Métriques de Performance
- Temps de réponse API
- Utilisation mémoire
- Taux d'erreur
- Connexions Supabase

## 🔧 Développement Local

### Installation
```bash
# Cloner le repository
git clone https://github.com/your-username/deliveryp2p.git
cd deliveryp2p

# Installer les dépendances
composer install

# Copier la configuration
cp .env.example .env

# Configurer les variables d'environnement
# (voir section Variables d'Environnement Requises)

# Créer les répertoires de stockage
mkdir -p storage/{logs,cache,qr_codes,uploads}

# Lancer le serveur de développement
php -S localhost:8000 -t public
```

### Tests
```bash
# Tests unitaires
composer test

# Tests avec couverture
composer test-coverage

# Analyse statique
composer stan

# Vérification du code
composer cs-check
```

## 📱 API Endpoints

### Authentification
```
POST   /api/auth/register     # Inscription
POST   /api/auth/login        # Connexion
POST   /api/auth/refresh      # Refresh token
POST   /api/auth/logout       # Déconnexion
GET    /api/auth/profile      # Profil utilisateur
```

### QR Codes Sécurisés
```
POST   /api/qr/generate       # Génération QR
POST   /api/qr/validate       # Validation QR
GET    /api/qr/{code}/info    # Informations QR
POST   /api/qr/{code}/scan    # Enregistrement scan
GET    /api/qr/{code}/history # Historique scans
DELETE /api/qr/{code}/revoke  # Révocation QR
```

### Livraisons
```
GET    /api/deliveries        # Liste livraisons
POST   /api/deliveries        # Création livraison
GET    /api/deliveries/{id}   # Détails livraison
PUT    /api/deliveries/{id}   # Modification livraison
DELETE /api/deliveries/{id}   # Annulation livraison
```

### Tracking Temps Réel
```
GET    /api/tracking/{id}     # Position livraison
POST   /api/tracking/update   # Mise à jour position
GET    /api/tracking/history  # Historique déplacements
```

## 🔒 Sécurité

### Headers de Sécurité
- Content Security Policy (CSP)
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- Strict-Transport-Security
- Referrer-Policy

### Rate Limiting
- 100 requêtes/heure par IP
- 1000 requêtes/heure par API key
- 500 requêtes/heure par utilisateur authentifié

### Validation des Données
- Validation côté serveur stricte
- Sanitisation des entrées
- Protection CSRF/XSS
- Validation géolocalisée

## 📊 Performance

### Optimisations
- Cache intelligent avec TTL
- Compression gzip
- Optimisation autoloader Composer
- Logs structurés JSON
- Health checks automatisés

### Métriques Cibles
- Temps de réponse API < 200ms
- Uptime 99.9%
- Lighthouse Score 95+
- Core Web Vitals Excellent

## 🚀 Déploiement Automatique

### GitHub Actions (optionnel)
```yaml
name: Deploy to Render
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Deploy to Render
        uses: johnbeynon/render-deploy-action@v1.0.0
        with:
          service-id: ${{ secrets.RENDER_SERVICE_ID }}
          api-key: ${{ secrets.RENDER_API_KEY }}
```

## 📞 Support

### Documentation API
- Swagger/OpenAPI 3.0 disponible sur `/api/docs`
- Exemples de requêtes inclus
- Codes d'erreur standardisés

### Monitoring
- Health checks automatiques
- Alertes en cas de défaillance
- Métriques temps réel
- Logs structurés

### Contact
- Email: support@livraisonp2p.com
- Documentation: https://docs.livraisonp2p.com
- Issues: https://github.com/your-username/deliveryp2p/issues

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

**LivraisonP2P** - Plateforme de livraison P2P de classe mondiale avec sécurité QR code cryptographique. 🚀

