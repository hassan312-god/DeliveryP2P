# 🚀 Configuration Render - DeliveryP2P

## 📋 Options de déploiement

### **Option 1 : PHP Simple (RECOMMANDÉ)**
- **Environment :** PHP
- **Root Directory :** `api`
- **Build Command :** `composer install --optimize-autoloader --no-dev --no-interaction`
- **Start Command :** `php -S 0.0.0.0:$PORT index.php`

### **Option 2 : Docker avec API**
- **Environment :** Docker
- **Dockerfile Path :** `./Dockerfile.api`
- **Docker Context :** `.`

### **Option 3 : Docker avec Frontend + API**
- **Environment :** Docker
- **Dockerfile Path :** `./Dockerfile`
- **Docker Context :** `.`

## 🔧 Configuration manuelle sur Render

### **Pour l'Option 1 (PHP Simple) :**

1. **Créer un nouveau "Web Service"**
2. **Connecter le repo GitHub**
3. **Configuration :**
   - **Name :** `deliveryp2p-api`
   - **Environment :** `PHP`
   - **Root Directory :** `api`
   - **Build Command :** `composer install --optimize-autoloader --no-dev --no-interaction`
   - **Start Command :** `php -S 0.0.0.0:$PORT index.php`

### **Pour l'Option 2 (Docker API) :**

1. **Créer un nouveau "Web Service"**
2. **Connecter le repo GitHub**
3. **Configuration :**
   - **Name :** `deliveryp2p-api-docker`
   - **Environment :** `Docker`
   - **Dockerfile Path :** `./Dockerfile.api`
   - **Docker Context :** `.`

## 🌍 Variables d'environnement

Dans **Environment Variables**, ajouter :

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

## 🧪 Tests après déploiement

### **URLs de test :**
- **Santé de l'API :** `https://ton-app.onrender.com/health-test.php`
- **Test simple :** `https://ton-app.onrender.com/test-simple.php`
- **API principale :** `https://ton-app.onrender.com/health`

### **Commandes de test :**
```bash
# Test de santé
curl https://ton-app.onrender.com/health-test.php

# Test de connexion Supabase
curl https://ton-app.onrender.com/test-connection

# Test QR code
curl -X POST https://ton-app.onrender.com/qr/generate \
  -H "Content-Type: application/json" \
  -d '{"data": "test123", "size": 200}'
```

## 🔍 Dépannage

### **Si Docker ne trouve pas le Dockerfile :**
- Vérifier que le Dockerfile est à la racine du repo
- Utiliser le bon chemin dans "Dockerfile Path"

### **Si l'API ne répond pas :**
- Vérifier les logs Render
- Vérifier que les variables d'environnement sont configurées
- Vérifier que le Root Directory pointe vers `api/`

### **Si les routes ne marchent pas :**
- Vérifier que le fichier `.htaccess` est présent dans le dossier `api/`
- Vérifier que Apache est configuré pour lire les `.htaccess`

## 📝 Résumé

- **Option 1 (PHP)** : Plus simple, plus rapide, recommandé pour commencer
- **Option 2 (Docker API)** : Plus robuste, meilleur pour la production
- **Option 3 (Docker Full)** : Pour avoir frontend + API ensemble 