# LivraisonP2P - Application de Livraison Entre Particuliers

## Description

LivraisonP2P est une application web moderne qui connecte des expéditeurs ayant des colis à envoyer avec des livreurs particuliers disponibles. L'application automatise le processus de notification, d'acceptation de mission, et de confirmation de livraison via QR code.

## Fonctionnalités

- **Gestion des utilisateurs** : Inscription, connexion, profils (expéditeurs, livreurs, administrateurs)
- **Création d'annonces** : Formulaire complet avec géolocalisation et autocomplétion d'adresses
- **Système de livraison** : Attribution automatique au premier livreur acceptant
- **Confirmation QR Code** : Génération et scan de QR codes pour confirmer les livraisons
- **Chat en temps réel** : Communication entre expéditeurs et livreurs
- **Système d'évaluation** : Notation bidirectionnelle après livraison
- **Notifications temps réel** : Via Supabase Realtime
- **Interface responsive** : Compatible mobile, tablette, desktop

## Technologies

- **Backend** : PHP 8.1+ (architecture MVC)
- **Base de données** : Supabase (PostgreSQL)
- **Frontend** : HTML5, CSS3, JavaScript, Bootstrap 5
- **Authentification** : Supabase Auth
- **Temps réel** : Supabase Realtime
- **Cartographie** : Google Maps API
- **Gestion de version** : GitHub

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- Serveur web (Apache/Nginx) ou serveur de développement PHP
- Compte Supabase
- Clé API Google Maps

### Étapes d'installation

1. **Cloner le repository**
   ```bash
   git clone https://github.com/votre-username/livraisonp2p.git
   cd livraisonp2p
   ```

2. **Installer les dépendances PHP**
   ```bash
   composer install
   ```

3. **Configurer l'environnement**
   ```bash
   cp .env.example .env
   ```
   Éditer le fichier `.env` avec vos clés API :
   - `SUPABASE_URL` : URL de votre projet Supabase
   - `SUPABASE_ANON_KEY` : Clé anonyme Supabase
   - `SUPABASE_SERVICE_ROLE_KEY` : Clé service Supabase
   - `GOOGLE_MAPS_API_KEY` : Clé API Google Maps

4. **Créer la base de données Supabase**
   Exécuter le script SQL fourni dans `database/schema.sql` dans votre projet Supabase.

5. **Configurer le serveur web**
   - Pointer le document root vers le dossier `public/`
   - Activer la réécriture d'URL (mod_rewrite pour Apache)

6. **Démarrer l'application**
   ```bash
   # Avec le serveur de développement PHP
   php -S localhost:8000 -t public/
   ```

## Structure du projet

```
/
├── public/                 # Point d'entrée de l'application
│   ├── index.php          # Routeur principal
│   ├── assets/            # Ressources statiques
│   │   ├── css/
│   │   ├── js/
│   │   └── img/
│   └── views/             # Templates HTML/PHP
├── app/                   # Logique backend
│   ├── config/           # Configuration
│   ├── controllers/      # Contrôleurs
│   ├── models/           # Modèles
│   ├── services/         # Services externes
│   └── core/             # Classes fondamentales
├── database/             # Scripts SQL
└── tests/                # Tests unitaires
```

## Configuration Supabase

1. Créer un projet sur [Supabase](https://supabase.com)
2. Récupérer les clés API dans les paramètres du projet
3. Exécuter le script SQL de création des tables
4. Configurer les politiques RLS (Row Level Security) si nécessaire

## Utilisation

### Expéditeur
1. S'inscrire/se connecter
2. Créer une annonce avec les détails du colis
3. Suivre l'état de la livraison
4. Communiquer avec le livreur via le chat
5. Évaluer le livreur après livraison

### Livreur
1. S'inscrire/se connecter
2. Consulter les annonces disponibles
3. Accepter une livraison
4. Suivre l'itinéraire
5. Présenter le QR code au destinataire
6. Évaluer l'expéditeur après livraison

### Administrateur
1. Accéder au tableau de bord admin
2. Gérer les utilisateurs
3. Superviser les livraisons
4. Consulter les statistiques

## API Endpoints

### Authentification
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion
- `POST /api/auth/logout` - Déconnexion

### Annonces
- `GET /api/ads` - Liste des annonces
- `POST /api/ads` - Créer une annonce
- `GET /api/ads/{id}` - Détails d'une annonce
- `PUT /api/ads/{id}` - Modifier une annonce
- `DELETE /api/ads/{id}` - Supprimer une annonce

### Livraisons
- `GET /api/deliveries` - Liste des livraisons
- `POST /api/deliveries/{id}/accept` - Accepter une livraison
- `PUT /api/deliveries/{id}/status` - Mettre à jour le statut
- `POST /api/deliveries/{id}/confirm` - Confirmer la livraison

### Chat
- `GET /api/chat/{delivery_id}` - Messages d'une livraison
- `POST /api/chat/{delivery_id}` - Envoyer un message

## Tests

```bash
# Exécuter tous les tests
composer test

# Exécuter les tests unitaires
./vendor/bin/phpunit tests/Unit/

# Exécuter les tests d'intégration
./vendor/bin/phpunit tests/Integration/
```

## Déploiement

### Production
1. Configurer un serveur web (Apache/Nginx)
2. Déployer les fichiers sur le serveur
3. Configurer les variables d'environnement
4. Configurer HTTPS
5. Optimiser les performances (cache, compression)

### Variables d'environnement de production
- `APP_ENV=production`
- `APP_DEBUG=false`
- Configurer les clés API de production

## Sécurité

- Validation des entrées utilisateur
- Protection CSRF
- Hachage sécurisé des mots de passe
- Authentification JWT
- Politiques RLS Supabase
- Validation côté client et serveur

## Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajouter nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Consulter la documentation Supabase
- Contacter l'équipe de développement

## Roadmap

- [ ] Application mobile native
- [ ] Intégration paiement en ligne
- [ ] Notifications push
- [ ] Optimisation des itinéraires
- [ ] Système de pourboires
- [ ] Support client avancé 