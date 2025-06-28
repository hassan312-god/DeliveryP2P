# 🚀 Guide de déploiement - Projet de test Render

## 📋 Étapes pour créer un nouveau projet Render

### 1. **Créer un nouveau service**
- Va sur [render.com](https://render.com)
- Clique sur **"New"** → **"Web Service"**
- Connecte le repo : `hassan312-god/DeliveryP2P`

### 2. **Configuration du service**
- **Name :** `deliveryp2p-test`
- **Environment :** `PHP`
- **Root Directory :** `api`
- **Build Command :** `composer install --optimize-autoloader --no-dev --no-interaction`
- **Start Command :** `php -S 0.0.0.0:$PORT index.php`

### 3. **Variables d'environnement**
Dans **Environment Variables**, ajoute :
```
APP_ENV=production
APP_DEBUG=false
SUPABASE_URL=https://ton-projet.supabase.co
SUPABASE_ANON_KEY=ta_cle_anon
SUPABASE_SERVICE_ROLE_KEY=ta_cle_service
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### 4. **Déploiement**
- Clique sur **"Create Web Service"**
- Render va automatiquement déployer l'application

## 🧪 Tests après déploiement

Une fois déployé, tu auras une URL du type :
`https://deliveryp2p-test.onrender.com`

### Tests à effectuer :

```bash
# Test de santé de l'API
curl https://deliveryp2p-test.onrender.com/health

# Test de connexion Supabase
curl https://deliveryp2p-test.onrender.com/test-connection

# Test de génération QR
curl -X POST https://deliveryp2p-test.onrender.com/qr/generate \
  -H "Content-Type: application/json" \
  -d '{"data": "test123", "size": 200}'

# Test des variables d'environnement
curl https://deliveryp2p-test.onrender.com/env-test.php
```

### Réponses attendues :

**✅ Succès :**
```json
{
  "status": "healthy",
  "database": {
    "status": "connected"
  }
}
```

**❌ Erreur :**
```json
{
  "success": false,
  "error": "Endpoint not found"
}
```

## 🔧 Dépannage

### Si l'API ne répond pas :
1. Vérifie les logs dans Render
2. Vérifie que les variables d'environnement sont configurées
3. Vérifie que le Root Directory pointe vers `api/`

### Si la connexion Supabase échoue :
1. Vérifie les clés Supabase dans les variables d'environnement
2. Vérifie que l'URL Supabase est correcte
3. Vérifie les permissions de la base de données

## 📞 Support

En cas de problème, vérifie :
- Les logs Render dans l'interface
- La configuration des variables d'environnement
- La connexion à Supabase 