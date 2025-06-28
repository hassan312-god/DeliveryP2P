# 🚀 Guide de Configuration Supabase pour DeliveryP2P

## 📋 **Étapes de Configuration**

### 1. **Créer un projet Supabase**

1. Allez sur [https://supabase.com](https://supabase.com)
2. Cliquez sur "Start your project"
3. Connectez-vous avec GitHub ou créez un compte
4. Cliquez sur "New Project"
5. Choisissez votre organisation
6. Donnez un nom à votre projet : `deliveryp2p`
7. Choisissez un mot de passe pour la base de données
8. Sélectionnez une région proche de vous
9. Cliquez sur "Create new project"

### 2. **Récupérer les clés API**

1. Dans votre projet Supabase, allez dans **Settings** > **API**
2. Copiez les informations suivantes :
   - **Project URL** : `https://your-project-ref.supabase.co`
   - **anon public** : `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`
   - **service_role secret** : `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...`

### 3. **Créer les tables**

1. Allez dans **SQL Editor**
2. Copiez le contenu du fichier `supabase-schema.sql`
3. Collez-le dans l'éditeur SQL
4. Cliquez sur "Run" pour exécuter le script

### 4. **Configurer les variables d'environnement**

1. Créez un fichier `.env` à la racine du projet :
```bash
cp .env.example .env
```

2. Modifiez le fichier `.env` avec vos vraies valeurs :
```env
# Configuration Supabase
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Configuration de l'application
APP_ENV=development
APP_DEBUG=true
APP_NAME=DeliveryP2P

# Configuration JWT
JWT_SECRET=your-32-character-jwt-secret-key
JWT_EXPIRATION=3600
JWT_REFRESH_EXPIRATION=604800

# Configuration de chiffrement
ENCRYPTION_KEY=your-32-character-encryption-key
QR_ENCRYPTION_KEY=your-32-character-qr-encryption-key
QR_CODE_SECRET=your-qr-code-specific-secret

# Configuration de sécurité
PASSWORD_SALT=your-password-salt-here
CORS_ALLOWED_ORIGINS=*

# Configuration des logs
LOG_LEVEL=debug
LOG_PATH=./storage/logs

# Configuration du cache
CACHE_ENABLED=true
CACHE_TTL=3600
CACHE_PATH=./storage/cache

# Configuration des QR codes
QR_CODE_SIZE=300
QR_CODE_MARGIN=10
QR_CODE_ERROR_CORRECTION=M

# Configuration des livraisons
DELIVERY_RADIUS_KM=0.5
DELIVERY_TIMEOUT_MINUTES=30
MAX_DELIVERY_WEIGHT_KG=25

# Configuration des commissions
PLATFORM_COMMISSION_PERCENT=15
MINIMUM_DELIVERY_FEE=5

# Configuration des notifications
PUSH_NOTIFICATIONS_ENABLED=true
EMAIL_NOTIFICATIONS_ENABLED=true
SMS_NOTIFICATIONS_ENABLED=false

# Configuration SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=noreply@deliveryp2p.com
SMTP_FROM_NAME=DeliveryP2P

# Configuration des paiements (Stripe)
STRIPE_PUBLIC_KEY=pk_test_your-stripe-public-key
STRIPE_SECRET_KEY=sk_test_your-stripe-secret-key
STRIPE_WEBHOOK_SECRET=whsec_your-webhook-secret

# Configuration des notifications push (Firebase)
FIREBASE_SERVER_KEY=your-firebase-server-key

# Configuration SendGrid
SENDGRID_API_KEY=your-sendgrid-api-key

# Configuration Twilio
TWILIO_ACCOUNT_SID=your-twilio-account-sid
TWILIO_AUTH_TOKEN=your-twilio-auth-token

# Configuration de géolocalisation
GOOGLE_MAPS_API_KEY=your-google-maps-api-key
MAPBOX_ACCESS_TOKEN=your-mapbox-access-token

# Configuration des timezones
TIMEZONE=Europe/Paris
LOCALE=fr_FR.UTF-8

# Configuration de la mémoire
MEMORY_LIMIT=256M
MAX_EXECUTION_TIME=30

# Configuration PHP
PHP_VERSION=8.2
COMPOSER_MEMORY_LIMIT=-1
```

### 5. **Configurer Render (Production)**

1. Allez sur [https://render.com](https://render.com)
2. Dans votre service API, allez dans **Environment**
3. Ajoutez les variables d'environnement :
   - `SUPABASE_URL`
   - `SUPABASE_ANON_KEY`
   - `SUPABASE_SERVICE_ROLE_KEY`
   - `JWT_SECRET`
   - `ENCRYPTION_KEY`
   - `QR_CODE_SECRET`

### 6. **Tester la configuration**

1. Redémarrez votre serveur local :
```bash
cd api
php -S localhost:8000 index-simple.php
```

2. Testez la connexion :
```bash
./test-supabase.sh
```

3. Ou testez manuellement :
```bash
curl -X GET "http://localhost:8000/supabase/test"
```

## 🧪 **Tests de Validation**

### **Test de connexion**
```bash
curl -X GET "http://localhost:8000/supabase/test"
```

**Réponse attendue :**
```json
{
    "success": true,
    "message": "Connexion Supabase réussie",
    "timestamp": "2025-06-28T12:00:00+02:00",
    "config": {
        "url_configured": true,
        "anon_key_configured": true,
        "service_key_configured": true
    }
}
```

### **Test de création d'utilisateur**
```bash
curl -X POST "http://localhost:8000/supabase/users" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@deliveryp2p.com",
    "password": "testpassword123",
    "first_name": "Test",
    "last_name": "User",
    "role": "client"
  }'
```

### **Test de création de QR code**
```bash
curl -X POST "http://localhost:8000/supabase/qr" \
  -H "Content-Type: application/json" \
  -d '{
    "data": "https://deliveryp2p.onrender.com/delivery/123",
    "size": 300
  }'
```

### **Test de création de livraison**
```bash
curl -X POST "http://localhost:8000/supabase/delivery" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "test-client-123",
    "pickup_address": "123 Rue de la Paix, Paris",
    "delivery_address": "456 Avenue des Champs, Paris",
    "weight": 2.5,
    "price": 15.50
  }'
```

## 🔧 **Dépannage**

### **Erreur "Could not resolve host"**
- Vérifiez que l'URL Supabase est correcte
- Assurez-vous que le projet est actif

### **Erreur "Invalid API key"**
- Vérifiez que les clés API sont correctes
- Assurez-vous que les clés sont copiées entièrement

### **Erreur "Table does not exist"**
- Exécutez le script SQL dans Supabase
- Vérifiez que les tables sont créées

### **Erreur "RLS policy"**
- Vérifiez que les politiques RLS sont configurées
- Testez avec un utilisateur authentifié

## 📊 **Monitoring**

### **Vérifier les logs Supabase**
1. Allez dans **Logs** dans votre projet Supabase
2. Surveillez les requêtes API
3. Vérifiez les erreurs

### **Vérifier les métriques**
1. Allez dans **Dashboard** > **Usage**
2. Surveillez l'utilisation de la base de données
3. Vérifiez les performances

## 🚀 **Déploiement**

### **Redéployer l'API**
```bash
./deploy-api.sh
```

### **Vérifier en production**
```bash
curl -X GET "https://deliveryp2p-api.onrender.com/supabase/test"
```

## ✅ **Validation Finale**

Une fois configuré, vous devriez voir :

1. ✅ **Connexion Supabase** : Succès
2. ✅ **Création utilisateur** : Succès
3. ✅ **Création QR code** : Succès
4. ✅ **Création livraison** : Succès
5. ✅ **Récupération données** : Succès

**🎉 Votre application DeliveryP2P est maintenant connectée à Supabase !** 