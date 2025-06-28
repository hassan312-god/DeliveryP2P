# 🚚 DeliveryP2P - Frontend Only

Interface utilisateur pour l'application de livraison peer-to-peer.

## 📁 Structure

```
deliveryp2p/
├── frontend/         # Interface utilisateur
│   ├── index.html    # Page d'accueil
│   ├── js/           # JavaScript
│   ├── css/          # Styles
│   ├── assets/       # Images et ressources
│   ├── auth/         # Pages d'authentification
│   ├── admin/        # Interface administrateur
│   ├── client/       # Interface client
│   └── courier/      # Interface livreur
├── env.example       # Configuration d'environnement
└── README.md         # Documentation
```

## 🚀 Démarrage

### Option 1 : Serveur Python
```bash
cd frontend
python3 -m http.server 3000
```

### Option 2 : Serveur Node.js
```bash
cd frontend
npx serve .
```

### Option 3 : Extension Live Server (VS Code)
- Installez l'extension "Live Server"
- Clic droit sur `frontend/index.html`
- Sélectionnez "Open with Live Server"

## 🌐 Pages disponibles

- **Accueil:** `index.html`
- **Authentification:** `auth/login.html`, `auth/register.html`
- **Client:** `client/dashboard.html`, `client/create-request.html`
- **Livreur:** `courier/dashboard.html`, `courier/available-requests.html`
- **Admin:** `admin/dashboard.html`, `admin/users.html`
- **Utilitaires:** `chat.html`, `call.html`, `qrcode.html`

## 🛠️ Technologies

- **HTML5** - Structure
- **CSS3** - Styles et animations
- **JavaScript (Vanilla)** - Interactivité
- **API PHP** - Backend et authentification
- **Responsive Design** - Compatible mobile

## 📱 Fonctionnalités

- ✅ Interface responsive
- ✅ Navigation fluide
- ✅ Formulaires validés
- ✅ Notifications toast
- ✅ QR Code génération
- ✅ Chat et appel intégrés
- ✅ Dashboard multi-rôles
- ✅ Authentification complète
- ✅ Gestion des profils
- ✅ Suivi en temps réel

## 🔧 Configuration

### Variables d'environnement
Copiez `env.example` vers `.env` et configurez :

```bash
# Configuration Supabase
SUPABASE_URL=https://votre-projet.supabase.co
SUPABASE_ANON_KEY=votre-clé-anonyme

# Configuration de l'application
APP_NAME=LivraisonP2P
API_BASE_URL=/backend

# Configuration des prix (XOF)
BASE_PRICE_PER_KM=100
MINIMUM_PRICE=500
```

### Fichier de configuration
Le fichier `frontend/config.js` contient toutes les configurations :
- URLs des APIs
- Paramètres d'authentification
- Configuration des services

## 📞 Support

Pour toute question ou problème, consultez la documentation ou ouvrez une issue.

---

**🎉 Interface prête à l'utilisation !**

## 🔄 Migration vers API PHP

✅ **Migration terminée :** Tous les fichiers JavaScript liés à Supabase ont été supprimés et remplacés par une intégration API PHP.

### Fichiers supprimés :
- `frontend/js/supabase.js`
- `frontend/js/services/supabase.js`
- `frontend/js/app.js.supabase-backup`
- `frontend/js/services/api.js.supabase-backup`
- `frontend/config.js.supabase-backup`
- `frontend/test-supabase-*.html`

### Fichiers mis à jour :
- `frontend/js/modules/auth.js` - Authentification complète
- `frontend/js/services/api.js` - Service API PHP
- `frontend/auth/*.html` - Pages d'authentification
- `frontend/client/*.html` - Interface client
- `frontend/courier/*.html` - Interface livreur
- `frontend/admin/*.html` - Interface admin

### Fonctionnalités implémentées :
- ✅ Authentification complète (login, register, logout)
- ✅ Gestion des profils utilisateur
- ✅ API pour livraisons, QR codes, paiements
- ✅ Gestion des notifications
- ✅ Upload de fichiers
- ✅ Géolocalisation
- ✅ Suivi en temps réel

### Prochaines étapes :
1. Implémenter le backend PHP correspondant
2. Configurer les endpoints API
3. Tester l'authentification et les fonctionnalités
4. Déployer l'application

