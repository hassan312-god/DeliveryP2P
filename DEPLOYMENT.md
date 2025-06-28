# 🚀 Guide de déploiement DeliveryP2P

## 📋 Configuration Render

### **Option 1 : Configuration manuelle**

1. **Créer un nouveau "Web Service" sur Render**
2. **Connecter le repo GitHub :** `hassan312-god/DeliveryP2P`
3. **Configuration :**
   - **Name :** `deliveryp2p`
   - **Language :** `Docker`
   - **Dockerfile Path :** `./Dockerfile`
   - **Docker Context :** `.`

### **Option 2 : Utiliser le Blueprint**

1. **Créer un nouveau "Blueprint" sur Render**
2. **Connecter le repo GitHub**
3. **Render détectera automatiquement le fichier `render.yaml`**
4. **Cliquer sur "Apply"**

## 🌍 Variables d'environnement

Dans **Environment Variables**, ajouter :

```
APP_ENV=production
APP_DEBUG=false
SUPABASE_URL=https://ton-projet.supabase.co
SUPABASE_ANON_KEY=ta_cle_anon_supabase
SUPABASE_SERVICE_ROLE_KEY=ta_cle_service_supabase
STRIPE_PUBLIC_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
JWT_SECRET=ton_jwt_secret
ENCRYPTION_KEY=ta_cle_encryption
QR_CODE_SECRET=ton_qr_secret
```

## 🧪 Tests après déploiement

### **URLs de test :**
- **Page d'accueil :** `https://ton-app.onrender.com/`
- **Test de santé :** `https://ton-app.onrender.com/health`
- **Test de connexion :** `https://ton-app.onrender.com/test-connection`
- **API QR :** `https://ton-app.onrender.com/api/qr/generate`

### **Commandes de test :**
```bash
# Test de santé
curl https://ton-app.onrender.com/health

# Test de connexion Supabase
curl https://ton-app.onrender.com/test-connection

# Test QR code
curl -X POST https://ton-app.onrender.com/api/qr/generate \
  -H "Content-Type: application/json" \
  -d '{"data": "test123", "size": 200}'
```

## 🔧 Test local

Pour tester en local :

```bash
# Démarrer l'API
./start-local.sh

# Ou manuellement
cd api
php -S localhost:8000
```

## 📝 Structure du projet

```
deliveryp2p/
├── frontend/          # Interface utilisateur
├── api/              # API backend
├── src/              # Code source PHP
├── Dockerfile        # Configuration Docker
├── render.yaml       # Configuration Render
└── start-local.sh    # Script de démarrage local
```

## 🎯 Fonctionnalités

- ✅ **Frontend complet** avec toutes les pages
- ✅ **API backend** avec authentification
- ✅ **Connexion Supabase** pour la base de données
- ✅ **Système QR code** sécurisé
- ✅ **Paiements Stripe** intégrés
- ✅ **Interface admin** et client

## 🔍 Dépannage

### **Si l'application ne démarre pas :**
1. Vérifier les variables d'environnement
2. Consulter les logs Render
3. Tester l'endpoint `/health`

### **Si l'API ne répond pas :**
1. Vérifier la configuration Supabase
2. Tester l'endpoint `/test-connection`
3. Consulter les logs d'erreur

### **Si le frontend ne s'affiche pas :**
1. Vérifier que l'URL pointe vers la racine
2. Consulter les logs Apache
3. Tester les fichiers statiques

## 📞 Support

En cas de problème, vérifier :
- Les logs Render dans l'interface
- La configuration des variables d'environnement
- La connexion à Supabase
- Les permissions des fichiers 