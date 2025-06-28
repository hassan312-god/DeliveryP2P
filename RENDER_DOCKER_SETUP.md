# 🐳 Guide de Configuration Render + Docker + Supabase

## 📋 Prérequis

- Compte Render.com
- Projet Supabase : `https://syamapjohtlbjlyhlhsi.supabase.co`
- Repo GitHub connecté à Render
- Dockerfile déjà configuré

## 🚀 Étape 1 : Vérifier les variables d'environnement sur Render

Tu as déjà configuré les variables d'environnement. Vérifie qu'elles sont bien présentes :

1. **Va sur :** https://dashboard.render.com/
2. **Sélectionne ton service** (`deliveryp2p`)
3. **Clique sur "Environment"**
4. **Vérifie que tu as ces variables :**

| Variable | Valeur |
|----------|--------|
| `SUPABASE_URL` | `https://syamapjohtlbjlyhlhsi.supabase.co` |
| `SUPABASE_ANON_KEY` | `(ta clé anon public)` |
| `SUPABASE_SERVICE_ROLE_KEY` | `(ta clé service_role secret)` |
| `APP_ENV` | `production` |

## 🏗️ Étape 2 : Configuration Render avec Docker

### 2.1 Configuration du service
Ton service Render doit être configuré comme suit :

| Paramètre | Valeur |
|-----------|--------|
| **Name** | `deliveryp2p` |
| **Environment** | `Docker` |
| **Dockerfile Path** | `./Dockerfile` |
| **Build Command** | (automatique avec Docker) |
| **Start Command** | (automatique avec Docker) |

### 2.2 Vérifier le render.yaml
Ton fichier `render.yaml` doit contenir :

```yaml
services:
  - type: web
    name: deliveryp2p
    env: docker
    dockerfilePath: ./Dockerfile
    envVars:
      - key: APP_ENV
        value: production
      - key: SUPABASE_URL
        sync: false
      - key: SUPABASE_ANON_KEY
        sync: false
      - key: SUPABASE_SERVICE_ROLE_KEY
        sync: false
```

## 🔧 Étape 3 : Configuration du Frontend

### 3.1 URLs de l'API
Avec Docker, l'API est servie sur le même domaine que le frontend. Dans tes fichiers JS :

```javascript
// Au lieu de : const API_URL = 'https://deliveryp2p-api.onrender.com';
const API_URL = ''; // API servie sur le même domaine via Docker
```

### 3.2 Endpoints disponibles
- **Frontend :** `https://deliveryp2p-go4x.onrender.com`
- **API Health :** `https://deliveryp2p-go4x.onrender.com/health`
- **API Supabase :** `https://deliveryp2p-go4x.onrender.com/api/supabase/test`
- **API Profiles :** `https://deliveryp2p-go4x.onrender.com/api/supabase/profiles`

## ✅ Étape 4 : Tester la configuration

### 4.1 Tester l'API
```bash
# Test de santé
curl https://deliveryp2p-go4x.onrender.com/health

# Test Supabase
curl https://deliveryp2p-go4x.onrender.com/api/supabase/test

# Test des profils
curl https://deliveryp2p-go4x.onrender.com/api/supabase/profiles
```

### 4.2 Tester le frontend
- **Accueil :** https://deliveryp2p-go4x.onrender.com
- **Inscription :** https://deliveryp2p-go4x.onrender.com/auth/register.html
- **Connexion :** https://deliveryp2p-go4x.onrender.com/auth/login.html

## 🔍 Étape 5 : Vérification

### 5.1 Vérifier le service Render
Tu dois avoir **1 service** :
- **deliveryp2p** (Docker Web Service)

### 5.2 Vérifier les variables d'environnement
- ✅ `SUPABASE_URL` configuré
- ✅ `SUPABASE_ANON_KEY` configuré
- ✅ `SUPABASE_SERVICE_ROLE_KEY` configuré
- ✅ `APP_ENV` configuré

### 5.3 Vérifier les URLs
- **Frontend + API :** `https://deliveryp2p-go4x.onrender.com`
- **API Endpoints :** `/api/*`, `/health`, etc.

## 🚨 Dépannage

### Problème : API retourne du HTML au lieu de JSON
**Solution :** Vérifie que les routes API dans le Dockerfile sont correctes

### Problème : Variables d'environnement non trouvées
**Solution :** Redéploie le service après avoir ajouté les variables

### Problème : Erreur de connexion Supabase
**Solution :** Vérifie que les clés sont correctes dans Render

### Problème : Docker build échoue
**Solution :** Vérifie que le Dockerfile est valide et que tous les fichiers sont présents

## 📞 Support

Si tu rencontres des problèmes :
1. Vérifie les logs dans Render (Build Logs + Runtime Logs)
2. Teste les endpoints individuellement
3. Vérifie la configuration Supabase
4. Vérifie que le Dockerfile fonctionne en local

## 🎯 Résultat attendu

- ✅ Frontend accessible sur `https://deliveryp2p-go4x.onrender.com`
- ✅ API accessible sur `https://deliveryp2p-go4x.onrender.com/api/*`
- ✅ Connexion Supabase fonctionnelle
- ✅ Authentification opérationnelle
- ✅ Base de données accessible
- ✅ Tout fonctionne avec Docker

## 🔄 Redéploiement

Pour redéployer après des changements :
```bash
git add .
git commit -m "🔧 Mise à jour configuration Docker"
git push origin main
```

Render redéploiera automatiquement ton service Docker. 