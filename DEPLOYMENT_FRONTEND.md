# 🚀 Guide de Déploiement Render - Structure Frontend + API

## 📁 Structure du Projet

```
deliveryp2p/
├── frontend/           # Interface utilisateur (DocumentRoot)
│   ├── index.html     # Page d'accueil
│   ├── .htaccess      # Configuration Apache
│   ├── css/           # Styles
│   ├── js/            # JavaScript
│   ├── auth/          # Pages d'authentification
│   ├── client/        # Interface client
│   ├── courier/       # Interface livreur
│   └── admin/         # Interface admin
├── api/               # Backend API
│   ├── index.php      # Point d'entrée API
│   ├── health.php     # Health check
│   └── test-connection.php # Test connexion
├── src/               # Code source PHP
├── public/            # Fichiers publics (backup)
├── Dockerfile         # Configuration Docker
└── render.yaml        # Configuration Render
```

## 🔧 Configuration Docker

Le `Dockerfile` est maintenant configuré pour :

1. **DocumentRoot** : `/var/www/html/frontend`
2. **Routes API** : `/api/*` → `/api/index.php`
3. **Health Check** : `/health` → `/api/health.php`
4. **Frontend** : Toutes les autres routes → `frontend/index.html`

## 🚀 Déploiement Étape par Étape

### Étape 1: Préparation

```bash
# Vérifiez que tous les fichiers sont commités
git add .
git commit -m "Configure Dockerfile for frontend structure"
git push origin main
```

### Étape 2: Configuration Render

1. **Dashboard Render** → "New +" → "Web Service"
2. **Connectez votre Git** (GitHub/GitLab)
3. **Sélectionnez le repository** : `DeliveryP2P`
4. **Branche** : `main`

### Étape 3: Configuration du Service

```yaml
Name: deliveryp2p-api
Environment: Docker
Region: Frankfurt (EU Central)
Branch: main
Root Directory: ./
Build Command: (laissé vide)
Start Command: (laissé vide)
Health Check Path: /health
```

### Étape 4: Variables d'Environnement

Dans l'onglet **Environment**, ajoutez :

```
# Supabase (OBLIGATOIRE)
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

# Sécurité (OBLIGATOIRE)
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

## 🧪 Tests de Déploiement

### Test 1: Page d'Accueil
```bash
curl https://your-app-name.onrender.com
```
**Attendu** : Votre page `frontend/index.html`

### Test 2: Health Check
```bash
curl https://your-app-name.onrender.com/health
```
**Attendu** :
```json
{
  "success": true,
  "status": "healthy",
  "timestamp": "2024-01-01T12:00:00Z",
  "version": "2.0.0"
}
```

### Test 3: API
```bash
curl https://your-app-name.onrender.com/api/health
```
**Attendu** : Même réponse que le health check

### Test 4: Test de Connexion
```bash
curl https://your-app-name.onrender.com/test-connection
```
**Attendu** : Rapport de connexion détaillé

## 📱 URLs Finales

- **Page d'Accueil** : `https://your-app-name.onrender.com`
- **Health Check** : `https://your-app-name.onrender.com/health`
- **API Health** : `https://your-app-name.onrender.com/api/health`
- **Test Connexion** : `https://your-app-name.onrender.com/test-connection`
- **API Générale** : `https://your-app-name.onrender.com/api/*`

## 🔄 Routes Apache

Le fichier `frontend/.htaccess` gère :

1. **Routes API** : `/api/*` → `../api/index.php`
2. **Health Check** : `/health` → `../api/health.php`
3. **Test Connexion** : `/test-connection` → `../api/test-connection.php`
4. **Fichiers statiques** : CSS, JS, images
5. **SPA Routes** : Toutes les autres routes → `index.html`

## 🐛 Dépannage

### Problème: Page d'accueil ne s'affiche pas

1. **Vérifiez le DocumentRoot** dans le Dockerfile
2. **Vérifiez les permissions** : `chmod -R 755 frontend/`
3. **Vérifiez les logs** Apache dans Render

### Problème: API ne répond pas

1. **Vérifiez les variables d'environnement**
2. **Testez directement** : `/api/health`
3. **Vérifiez les logs** PHP dans Render

### Problème: Routes frontend ne fonctionnent pas

1. **Vérifiez le .htaccess** dans `frontend/`
2. **Testez les routes** : `/auth/login`, `/client/dashboard`
3. **Vérifiez la configuration** Apache

## ✅ Checklist de Déploiement

- [ ] Dockerfile configuré pour `frontend/`
- [ ] `frontend/.htaccess` créé
- [ ] Variables d'environnement configurées
- [ ] Projet Supabase créé
- [ ] Tables créées dans Supabase
- [ ] Service Render créé
- [ ] Build réussi
- [ ] Page d'accueil accessible
- [ ] Health check OK
- [ ] API fonctionnelle
- [ ] Routes frontend OK

## 🎯 Résultat Attendu

Après déploiement, vous devriez avoir :

1. **Page d'accueil** accessible sur la racine
2. **API fonctionnelle** sur `/api/*`
3. **Health check** sur `/health`
4. **Routes frontend** fonctionnelles
5. **Interface complète** LivraisonP2P

Votre application LivraisonP2P sera maintenant accessible avec une interface utilisateur complète ! 🎉 