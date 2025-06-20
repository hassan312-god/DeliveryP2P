# LivraisonP2P - Application de Livraison Peer-to-Peer

Une application web moderne de livraison peer-to-peer construite avec HTML, CSS (Tailwind), JavaScript et Supabase.

## 🚀 Fonctionnalités

### 🔐 Authentification
- Inscription et connexion sécurisées
- Authentification sociale (Google, Facebook)
- Gestion des profils utilisateurs
- Rôles utilisateur (Client, Livreur, Admin)

### 📦 Livraisons
- Création de demandes de livraison
- Suivi en temps réel
- Géolocalisation et calcul de prix automatique
- Système de notation et avis
- Notifications push

### 💰 Paiements
- Intégration Mobile Money
- Paiements par carte bancaire
- Paiements en espèces
- Historique des transactions

### 💬 Communication
- Chat en temps réel
- Appels vocaux
- Notifications push
- Messages avec images et localisation

### 📱 QR Codes (Nouveau!)
- **Génération de QR codes** pour :
  - Livraisons (suivi et identification)
  - Profils utilisateurs (partage de contact)
  - Paiements (transferts rapides)
  - Localisations (partage de position)
  - Contenu personnalisé
- **Scanner de QR codes** avec caméra
- **Historique et gestion** des QR codes
- **Partage et téléchargement** des QR codes
- **Favoris et organisation**

### 📊 Tableau de bord
- Statistiques en temps réel
- Graphiques et analyses
- Gestion des utilisateurs
- Rapports de performance

## 🛠️ Technologies

- **Frontend**: HTML5, CSS3 (Tailwind CSS), JavaScript ES6+
- **Backend**: Supabase (PostgreSQL, Auth, Real-time)
- **QR Codes**: QRCode.js, HTML5-QRCode
- **Maps**: Google Maps API
- **Paiements**: Intégration Mobile Money
- **Notifications**: Push API, WebSockets

## 📁 Structure du Projet

```
static-demo/
├── auth/                    # Pages d'authentification
├── client/                  # Interface client
├── courier/                 # Interface livreur
├── admin/                   # Interface administrateur
├── assets/                  # Images et icônes
├── css/                     # Styles CSS
├── js/                      # JavaScript
│   ├── components/          # Composants réutilisables
│   ├── modules/             # Modules fonctionnels
│   └── services/            # Services API
├── database/                # Schémas et fonctions SQL
├── qrcode.html             # Page QR codes
├── chat.html               # Chat en temps réel
├── call.html               # Appels vocaux
└── index.html              # Page d'accueil
```

## 🚀 Installation

### Prérequis
- Node.js (optionnel pour le développement)
- Compte Supabase
- Navigateur moderne avec support WebRTC

### Configuration

1. **Cloner le repository**
```bash
git clone <repository-url>
cd static-demo
```

2. **Configurer Supabase**
- Créer un projet Supabase
- Copier l'URL et la clé anon dans `config.js`
- Exécuter les scripts SQL dans l'ordre :
  ```sql
  -- 1. Schéma de base
  \i database/schema.sql
  
  -- 2. Fonctions
  \i database/functions.sql
  
  -- 3. Triggers
  \i database/triggers.sql
  
  -- 4. Vues
  \i database/views.sql
  
  -- 5. Sécurité
  \i database/security.sql
  ```

3. **Démarrer l'application**
```bash
# Avec un serveur local (recommandé)
python -m http.server 8000
# ou
npx serve .

# Ouvrir http://localhost:8000
```

## 📱 Fonctionnalités QR Code

### Génération de QR Codes

L'application permet de générer différents types de QR codes :

#### 🚚 QR Code de Livraison
- Contient l'ID de livraison et les adresses
- Permet le suivi rapide d'une livraison
- Redirige vers la page de suivi

#### 👤 QR Code Utilisateur
- Contient les informations du profil
- Permet de partager facilement son contact
- Redirige vers le chat avec l'utilisateur

#### 💳 QR Code de Paiement
- Contient le montant et la description
- Permet des transferts rapides
- Redirige vers la page de paiement

#### 📍 QR Code de Localisation
- Contient les coordonnées GPS
- Permet de partager sa position
- Ouvre Google Maps

#### ✏️ QR Code Personnalisé
- Contenu libre défini par l'utilisateur
- Texte, URL, ou données JSON
- Affichage dans un modal

### Scanner de QR Codes

- **Caméra en temps réel** pour scanner les QR codes
- **Changement de caméra** (avant/arrière)
- **Traitement automatique** selon le type de QR code
- **Interface intuitive** avec guide visuel

### Gestion des QR Codes

- **Historique complet** des QR codes générés
- **Filtres par type** et date
- **Recherche** dans les titres et descriptions
- **Favoris** pour un accès rapide
- **Export** des données
- **Nettoyage automatique** des anciens QR codes

## 🔧 Configuration Avancée

### Variables d'environnement
```javascript
// config.js
window.AppConfig = {
    supabase: {
        url: 'VOTRE_URL_SUPABASE',
        anonKey: 'VOTRE_CLE_ANON'
    },
    qrCodes: {
        generation: {
            width: 256,
            height: 256,
            margin: 2,
            color: { dark: '#000000', light: '#FFFFFF' }
        },
        scanner: {
            fps: 10,
            qrbox: { width: 250, height: 250 }
        }
    }
};
```

### Permissions utilisateur
```sql
-- Ajouter les permissions QR code aux rôles
UPDATE profiles SET permissions = array_append(permissions, 'generate_qr_codes')
WHERE role IN ('client', 'livreur');

UPDATE profiles SET permissions = array_append(permissions, 'manage_qr_codes')
WHERE role = 'admin';
```

## 📊 Base de Données

### Table QR Codes
```sql
CREATE TABLE qr_codes (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID NOT NULL REFERENCES auth.users(id),
    content TEXT NOT NULL,
    qr_code_data TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'custom',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    metadata JSONB,
    is_favorite BOOLEAN DEFAULT FALSE,
    scan_count INTEGER DEFAULT 0,
    last_scanned_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);
```

### Fonctions principales
- `create_qr_code()` - Créer un nouveau QR code
- `increment_qr_scan_count()` - Incrémenter le compteur de scans
- `get_qr_code_stats()` - Obtenir les statistiques
- `search_qr_codes()` - Rechercher dans les QR codes
- `export_user_qr_codes()` - Exporter les QR codes d'un utilisateur

## 🔒 Sécurité

- **Row Level Security (RLS)** activé sur toutes les tables
- **Authentification** requise pour toutes les opérations
- **Validation** des données côté client et serveur
- **Audit trail** complet des modifications
- **Nettoyage automatique** des données sensibles

## 📈 Performance

- **Index optimisés** pour les requêtes fréquentes
- **Pagination** pour les grandes listes
- **Cache** des QR codes générés
- **Compression** des images
- **Lazy loading** des composants

## 🧪 Tests

### Tests manuels
1. **Génération QR codes** : Tester tous les types
2. **Scanner QR codes** : Tester avec différents appareils
3. **Partage** : Tester sur mobile et desktop
4. **Historique** : Vérifier la persistance des données

### Tests automatisés
```bash
# Tests unitaires (à implémenter)
npm test

# Tests d'intégration
npm run test:integration
```

## 🚀 Déploiement

### Production
```bash
# Build de production
npm run build

# Déploiement sur Vercel/Netlify
vercel --prod
```

### Variables d'environnement
```bash
SUPABASE_URL=votre_url_supabase
SUPABASE_ANON_KEY=votre_cle_anon
GOOGLE_MAPS_API_KEY=votre_cle_google_maps
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

- **Email**: support@livraisonp2p.com
- **Documentation**: [Wiki du projet]
- **Issues**: [GitHub Issues]

## 🔄 Changelog

### Version 1.1.0 (Actuelle)
- ✨ Ajout du système de QR codes complet
- 🔧 Amélioration de la sécurité
- 📱 Support mobile amélioré
- 🐛 Corrections de bugs

### Version 1.0.0
- 🎉 Version initiale
- 🔐 Système d'authentification
- 📦 Gestion des livraisons
- 💰 Système de paiement
- 💬 Chat en temps réel

---

**LivraisonP2P** - Simplifiez vos livraisons avec la puissance du peer-to-peer ! 🚀 