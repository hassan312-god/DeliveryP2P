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
- **Responsive Design** - Compatible mobile

## 📱 Fonctionnalités

- ✅ Interface responsive
- ✅ Navigation fluide
- ✅ Formulaires validés
- ✅ Notifications toast
- ✅ QR Code génération
- ✅ Chat et appel intégrés
- ✅ Dashboard multi-rôles

## 🔧 Configuration

Le fichier `frontend/config.js` contient toutes les configurations :
- URLs des APIs
- Paramètres d'authentification
- Configuration des services

## 📞 Support

Pour toute question ou problème, consultez la documentation ou ouvrez une issue.

---

**🎉 Interface prête à l'utilisation !**

## 🔄 Migration Supabase

⚠️ **Note importante :** Tous les fichiers JavaScript liés à Supabase ont été supprimés.

### Fichiers supprimés :
- `frontend/js/supabase.js`
- `frontend/js/services/supabase.js`
- `frontend/js/modules/auth.js` (ancien)
- `frontend/test-supabase-*.html`

### Fichiers à implémenter :
- `frontend/js/modules/auth.js` (nouveau - vide, prêt pour votre implémentation)
- Configuration de connexion dans `frontend/config.js`

### Prochaines étapes :
1. Implémentez votre nouvelle logique d'authentification
2. Mettez à jour `frontend/js/modules/auth.js`
3. Configurez les URLs d'API dans `frontend/config.js`
4. Testez l'authentification

