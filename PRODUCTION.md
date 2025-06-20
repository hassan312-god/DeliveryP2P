# 🚀 Guide de Production - LivraisonP2P

Ce guide explique comment lancer LivraisonP2P en production avec tous les designs harmonisés et les boutons liés au backend.

## 📋 Prérequis

- PHP 7.4 ou supérieur
- Compte Supabase configuré
- Service d'email configuré (SMTP, SendGrid ou Mailgun)
- Serveur web (Apache, Nginx ou serveur PHP intégré)

## ⚙️ Configuration

### 1. Configuration Supabase

Assurez-vous que votre fichier `config.js` contient les bonnes informations Supabase :

```javascript
const SUPABASE_URL = 'https://votre-projet.supabase.co';
const SUPABASE_ANON_KEY = 'votre-clé-anonyme';
const SUPABASE_SERVICE_ROLE_KEY = 'votre-clé-service';
```

### 2. Configuration Email

Configurez votre service d'email dans `php/config.php` :

```php
// Pour SMTP
define('EMAIL_SERVICE', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'votre-email@gmail.com');
define('SMTP_PASSWORD', 'votre-mot-de-passe-app');

// Pour SendGrid
define('EMAIL_SERVICE', 'sendgrid');
define('SENDGRID_API_KEY', 'votre-clé-api');

// Pour Mailgun
define('EMAIL_SERVICE', 'mailgun');
define('MAILGUN_API_KEY', 'votre-clé-api');
define('MAILGUN_DOMAIN', 'votre-domaine.com');
```

## 🚀 Lancement Rapide

### Windows
```bash
start-production.bat
```

### Linux/Mac
```bash
chmod +x start-production.sh
./start-production.sh
```

### Manuel
```bash
# 1. Configuration automatique
php setup-production.php

# 2. Lancement des services
php launch-production.php

# 3. Démarrage du serveur
php -S localhost:8000 -t .
```

## 🎨 Designs Harmonisés

Toutes les pages utilisent maintenant le design harmonisé avec :

- **Couleurs cohérentes** : Palette de couleurs unifiée
- **Typographie** : Police Inter pour une meilleure lisibilité
- **Animations** : Transitions fluides et animations d'entrée
- **Responsive** : Adaptation automatique à tous les écrans
- **Accessibilité** : Support des lecteurs d'écran et navigation clavier

### Pages mises à jour :
- ✅ `auth/login.html` - Connexion harmonisée
- ✅ `auth/register.html` - Inscription harmonisée
- ✅ `auth/forgot-password.html` - Mot de passe oublié
- ✅ `auth/reset-password.html` - Réinitialisation
- ✅ `auth/email-confirmation.html` - Confirmation email
- ✅ `auth/callback.html` - Callback OAuth

## 🔗 Boutons Liés au Backend

Tous les boutons sont maintenant connectés au backend avec :

### Fonctionnalités :
- **Validation en temps réel** : Vérification des champs à la saisie
- **Gestion d'erreurs** : Messages d'erreur contextuels
- **États de chargement** : Indicateurs visuels pendant les requêtes
- **Redirection intelligente** : Navigation automatique selon le rôle
- **Persistance des sessions** : Connexion maintenue

### Boutons connectés :
- ✅ Connexion/Inscription
- ✅ Connexion sociale (Google, Facebook)
- ✅ Envoi d'emails de confirmation
- ✅ Réinitialisation de mot de passe
- ✅ Navigation entre pages
- ✅ Actions utilisateur (profil, commandes, etc.)

## 📧 Services d'Email

### Types d'emails supportés :
- **Confirmation de compte** : Envoi automatique après inscription
- **Réinitialisation de mot de passe** : Lien sécurisé de récupération
- **Notifications de livraison** : Mises à jour en temps réel
- **Confirmations de paiement** : Reçus automatiques

### Configuration :
```php
// Templates disponibles
- confirmation-email.php
- password-reset.php
- delivery-notification.php
- payment-confirmation.php
```

## 🗄️ Base de Données

### Tables créées automatiquement :
- `profiles` - Profils utilisateurs
- `deliveries` - Livraisons
- `payments` - Paiements
- `notifications` - Notifications
- `email_queue` - File d'attente des emails
- `user_sessions` - Sessions utilisateurs
- `activity_logs` - Logs d'activité

### Politiques RLS :
- Sécurité par utilisateur
- Accès admin pour la gestion
- Protection des données sensibles

## 🔒 Sécurité

### Fonctionnalités de sécurité :
- **Authentification Supabase** : Sécurisée et scalable
- **Validation côté serveur** : Double vérification
- **Protection CSRF** : Tokens de sécurité
- **Rate limiting** : Protection contre les abus
- **Chiffrement** : Données sensibles chiffrées

## 📊 Monitoring

### Logs disponibles :
- `logs/server.log` - Logs du serveur web
- `logs/email-processor.log` - Traitement des emails
- `logs/monitoring.log` - Monitoring système
- `logs/production-report.json` - Rapport de production

### Métriques :
- Temps de réponse
- Taux d'erreur
- Utilisation des ressources
- Activité utilisateur

## 🚀 Déploiement

### Serveur de production :
```bash
# Avec Apache
sudo cp -r . /var/www/html/livraisonp2p/
sudo chown -R www-data:www-data /var/www/html/livraisonp2p/

# Avec Nginx
sudo cp -r . /var/www/livraisonp2p/
sudo chown -R www-data:www-data /var/www/livraisonp2p/
```

### Variables d'environnement :
```bash
export SUPABASE_URL="https://votre-projet.supabase.co"
export SUPABASE_ANON_KEY="votre-clé-anonyme"
export EMAIL_SERVICE="smtp"
export SMTP_HOST="smtp.gmail.com"
```

## 🔧 Maintenance

### Tâches automatiques :
- **Nettoyage des emails** : Tous les jours à 2h
- **Sauvegarde BDD** : Tous les jours à 3h
- **Nettoyage sessions** : Toutes les heures
- **Traitement emails** : Toutes les 5 minutes

### Commandes utiles :
```bash
# Vérifier l'état des services
php cron/check-services.php

# Nettoyer les logs
php cron/cleanup-logs.php

# Sauvegarder la base
php cron/backup-database.php
```

## 📞 Support

### En cas de problème :
1. Vérifiez les logs dans `logs/`
2. Testez la connexion Supabase
3. Vérifiez la configuration email
4. Consultez le rapport de production

### Contact :
- Documentation : `README.md`
- Configuration : `config.js` et `php/config.php`
- Logs : Dossier `logs/`

---

## 🎉 Félicitations !

Votre application LivraisonP2P est maintenant en production avec :
- ✅ Designs harmonisés et modernes
- ✅ Boutons connectés au backend
- ✅ Système d'authentification complet
- ✅ Service d'emails fonctionnel
- ✅ Base de données sécurisée
- ✅ Monitoring et maintenance automatiques

**URL d'accès :** http://localhost:8000 (ou votre domaine)
**Page de connexion :** http://localhost:8000/auth/login.html
**Page d'inscription :** http://localhost:8000/auth/register.html 