# 🚀 Guide de Déploiement DeliveryP2P sur Render

## 📋 Vue d'ensemble

Ce guide vous accompagne pour déployer votre application DeliveryP2P sur Render avec une architecture unifiée (frontend + backend dans un seul service).

## 🏗️ Structure du Projet

```
deliveryp2p/
├── backend/                 # API PHP
│   ├── config.php
│   ├── supabase-api.php
│   ├── admin-dashboard.php
│   └── ...
├── frontend/               # Interface utilisateur
│   ├── index.html
│   ├── js/
│   ├── css/
│   ├── assets/
│   └── ...
├── Dockerfile             # Configuration Docker
├── .dockerignore          # Fichiers ignorés par Docker
├── render.yaml            # Configuration Render
└── scripts/               # Scripts de déploiement
```

## 🛠️ Scripts Disponibles

### 1. Migration vers Render
```bash
./migrate-to-render.sh
```
- Restructure le projet pour Render
- Crée les dossiers `backend/` et `frontend/`
- Déplace les fichiers appropriés
- Crée le `Dockerfile` et `.dockerignore`

### 2. Mise à jour des URLs
```bash
./update-urls.sh
```
- Met à jour toutes les URLs pour utiliser `/backend`
- Crée des sauvegardes avec l'extension `.backup`

### 3. Synchronisation Supabase
```bash
./sync-supabase.sh
```
- Installe Supabase CLI si nécessaire
- Initialise le projet Supabase local
- Crée les migrations de base
- Génère les types TypeScript

### 4. Test de configuration
```bash
./test-deployment.sh
```
- Vérifie la structure du projet
- Teste les URLs
- Valide la configuration Docker

### 5. Déploiement complet
```bash
./deploy-to-render.sh
```
- Synchronise avec Supabase
- Met à jour les URLs
- Commit et push vers GitHub
- Crée la configuration Render

## 🚀 Déploiement sur Render

### Étape 1 : Préparation
```bash
# Exécuter les scripts dans l'ordre
./migrate-to-render.sh
./update-urls.sh
./test-deployment.sh
```

### Étape 2 : Configuration Render

1. **Allez sur [Render Dashboard](https://dashboard.render.com)**
2. **Cliquez sur "New Web Service"**
3. **Connectez votre repo GitHub**
4. **Configurez le service :**
   - **Name:** `deliveryp2p`
   - **Environment:** `Docker`
   - **Region:** `Oregon (US West)`
   - **Branch:** `main`
   - **Root Directory:** `.` (laisser vide)

### Étape 3 : Variables d'Environnement

Configurez ces variables dans Render :

| Variable | Description | Exemple |
|----------|-------------|---------|
| `SUPABASE_URL` | URL de votre projet Supabase | `https://syamapjohtlbjlyhlhsi.supabase.co` |
| `SUPABASE_ANON_KEY` | Clé anonyme Supabase | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` |
| `SUPABASE_SERVICE_KEY` | Clé de service Supabase | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...` |
| `JWT_SECRET` | Secret pour les JWT | `votre_secret_jwt_tres_long` |
| `PASSWORD_SALT` | Salt pour le hachage | `votre_salt_tres_long` |
| `SMTP_HOST` | Serveur SMTP | `smtp.gmail.com` |
| `SMTP_PORT` | Port SMTP | `587` |
| `SMTP_USERNAME` | Email SMTP | `votre-email@gmail.com` |
| `SMTP_PASSWORD` | Mot de passe SMTP | `votre_mot_de_passe_app` |
| `SMTP_FROM_EMAIL` | Email d'expédition | `noreply@votreapp.com` |

### Étape 4 : Déploiement
```bash
./deploy-to-render.sh
```

## 🌐 URLs Finales

Après le déploiement, vos URLs seront :

- **Application principale:** `https://ton-app.onrender.com`
- **API Backend:** `https://ton-app.onrender.com/backend`
- **Admin Dashboard:** `https://ton-app.onrender.com/backend/admin-dashboard.php`
- **QR Code Generator:** `https://ton-app.onrender.com/backend/qr-code-generator.php`

## 🔧 Développement Local

### Avec Supabase Local
```bash
# Installer Supabase CLI
curl -fsSL https://supabase.com/install.sh | sh

# Initialiser le projet
./sync-supabase.sh

# Démarrer l'environnement local
./start-dev.sh
```

### Sans Supabase Local
```bash
# Démarrer un serveur simple
cd frontend
python3 -m http.server 3000
```

## 🐛 Dépannage

### Erreur de Build Docker
```bash
# Vérifier la syntaxe du Dockerfile
docker build -t test .

# Vérifier les logs
docker logs <container_id>
```

### Erreur de Connexion Supabase
```bash
# Tester la connexion
curl -X GET "https://syamapjohtlbjlyhlhsi.supabase.co/rest/v1/" \
  -H "apikey: votre_clé_anon"
```

### Erreur de Variables d'Environnement
```bash
# Vérifier les variables dans Render
# Dashboard > Your Service > Environment
```

## 📊 Monitoring

### Logs Render
- **Dashboard Render > Your Service > Logs**
- **Logs en temps réel disponibles**

### Métriques
- **Uptime:** Surveillé automatiquement
- **Performance:** Métriques disponibles dans le dashboard
- **Erreurs:** Logs d'erreur automatiques

## 🔄 Mises à Jour

### Déploiement automatique
- Chaque push sur `main` déclenche un redéploiement
- Pas d'action manuelle nécessaire

### Déploiement manuel
```bash
# Commit et push
git add .
git commit -m "Update description"
git push origin main
```

## 🛡️ Sécurité

### Variables sensibles
- ✅ Toujours dans les variables d'environnement Render
- ❌ Jamais dans le code source
- ❌ Jamais dans les commits Git

### CORS
- Configuré pour permettre les requêtes depuis le même domaine
- Pas de configuration CORS complexe nécessaire

## 📞 Support

En cas de problème :

1. **Vérifiez les logs Render**
2. **Testez localement avec `./test-deployment.sh`**
3. **Vérifiez la configuration Supabase**
4. **Consultez la documentation Render**

---

**🎉 Votre application est maintenant prête pour la production !** 