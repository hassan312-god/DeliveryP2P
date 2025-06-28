# 🔗 Guide de Configuration Render + Supabase

## 📋 Prérequis

- Compte Render.com
- Projet Supabase : `https://syamapjohtlbjlyhlhsi.supabase.co`
- Repo GitHub connecté à Render

## 🚀 Étape 1 : Récupérer les clés Supabase

1. **Va sur :** https://syamapjohtlbjlyhlhsi.supabase.co/project/settings/api
2. **Copie ces informations :**
   - **Project URL :** `https://syamapjohtlbjlyhlhsi.supabase.co`
   - **anon public :** (clé qui commence par `eyJ...`)
   - **service_role secret :** (clé qui commence par `eyJ...`)

## 🏗️ Étape 2 : Créer le service API sur Render

### 2.1 Créer un nouveau service
1. **Va sur :** https://dashboard.render.com/
2. **Clique sur "New +"** → **"Web Service"**
3. **Connecte ton repo GitHub** (si pas déjà fait)

### 2.2 Configuration du service API
| Paramètre | Valeur |
|-----------|--------|
| **Name** | `deliveryp2p-api` |
| **Environment** | `PHP` |
| **Build Command** | `echo "No build required for PHP"` |
| **Start Command** | `cd api && php -S 0.0.0.0:$PORT index-simple.php` |
| **Root Directory** | `/` (laisse vide) |

### 2.3 Variables d'environnement pour l'API
Dans le service API, ajoute ces variables :

| Variable | Valeur |
|----------|--------|
| `SUPABASE_URL` | `https://syamapjohtlbjlyhlhsi.supabase.co` |
| `SUPABASE_ANON_KEY` | `(ta clé anon public)` |
| `SUPABASE_SERVICE_ROLE_KEY` | `(ta clé service_role secret)` |
| `APP_ENV` | `production` |

## 🌐 Étape 3 : Configurer le service Frontend

### 3.1 Modifier le service frontend existant
1. **Va dans les paramètres de ton service frontend existant**
2. **Change le type en "Static Site"**
3. **Configure :**
   - **Build Command :** `echo "Frontend static files"`
   - **Publish Directory :** `frontend`

### 3.2 Variables d'environnement pour le frontend (optionnel)
| Variable | Valeur |
|----------|--------|
| `API_URL` | `https://deliveryp2p-api.onrender.com` |

## 🔧 Étape 4 : Mettre à jour le frontend

### 4.1 Mettre à jour l'URL de l'API
Dans tous les fichiers frontend qui appellent l'API, remplace :
```javascript
const API_URL = 'https://deliveryp2p-api.onrender.com'; // Ton URL API
```

### 4.2 Fichiers à modifier :
- `frontend/auth/register.html`
- `frontend/auth/login.html`
- Tous les autres fichiers qui font des appels API

## ✅ Étape 5 : Tester la configuration

### 5.1 Tester l'API
```bash
# Test de santé
curl https://deliveryp2p-api.onrender.com/health

# Test Supabase
curl https://deliveryp2p-api.onrender.com/supabase/test

# Test des profils
curl https://deliveryp2p-api.onrender.com/supabase/profiles
```

### 5.2 Tester le frontend
- Va sur : `https://deliveryp2p-go4x.onrender.com`
- Teste l'inscription : `https://deliveryp2p-go4x.onrender.com/auth/register.html`
- Teste la connexion : `https://deliveryp2p-go4x.onrender.com/auth/login.html`

## 🔍 Étape 6 : Vérification

### 6.1 Vérifier les services Render
Tu dois avoir **2 services** :
1. **deliveryp2p-frontend** (Static Site)
2. **deliveryp2p-api** (Web Service)

### 6.2 Vérifier les variables d'environnement
- ✅ `SUPABASE_URL` configuré
- ✅ `SUPABASE_ANON_KEY` configuré
- ✅ `SUPABASE_SERVICE_ROLE_KEY` configuré
- ✅ `APP_ENV` configuré

### 6.3 Vérifier les URLs
- **Frontend :** `https://deliveryp2p-go4x.onrender.com`
- **API :** `https://deliveryp2p-api.onrender.com`

## 🚨 Dépannage

### Problème : API retourne du HTML au lieu de JSON
**Solution :** Vérifie que tu as bien 2 services séparés sur Render

### Problème : Variables d'environnement non trouvées
**Solution :** Redéploie le service API après avoir ajouté les variables

### Problème : Erreur de connexion Supabase
**Solution :** Vérifie que les clés sont correctes et que la base de données existe

## 📞 Support

Si tu rencontres des problèmes :
1. Vérifie les logs dans Render
2. Teste les endpoints individuellement
3. Vérifie la configuration Supabase

## 🎯 Résultat attendu

- ✅ Frontend accessible sur `https://deliveryp2p-go4x.onrender.com`
- ✅ API accessible sur `https://deliveryp2p-api.onrender.com`
- ✅ Connexion Supabase fonctionnelle
- ✅ Authentification opérationnelle
- ✅ Base de données accessible 