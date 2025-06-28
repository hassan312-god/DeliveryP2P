# Scripts PHP pour LivraisonP2P

Ce dossier contient des scripts PHP utilitaires pour l'application LivraisonP2P. Ces scripts facilitent l'administration, la maintenance et l'intégration avec Supabase.

## 📁 Structure des fichiers

```
php/
├── config.php              # Configuration centralisée
├── supabase-api.php        # Classe d'interaction avec Supabase
├── admin-dashboard.php     # Tableau de bord administrateur
├── qr-code-generator.php   # Générateur de QR codes
├── backup-manager.php      # Gestionnaire de sauvegardes
├── email-service.php       # Service d'envoi d'emails
└── README.md              # Ce fichier
```

## 🚀 Installation

### Prérequis

- PHP 7.4 ou supérieur
- Extension cURL activée
- Extension JSON activée
- Serveur web (Apache, Nginx, ou serveur de développement PHP)

### Configuration

1. **Modifier `config.php`** :
   ```php
   // Mettre à jour avec vos informations Supabase
   define('SUPABASE_URL', 'votre-url-supabase');
   define('SUPABASE_ANON_KEY', 'votre-clé-anon');
   define('SUPABASE_SERVICE_KEY', 'votre-clé-service'); // Optionnel
   
   // Configuration email
   define('SMTP_USERNAME', 'votre-email@gmail.com');
   define('SMTP_PASSWORD', 'votre-mot-de-passe-app');
   ```

2. **Créer les dossiers nécessaires** :
   ```bash
   mkdir logs
   mkdir backups
   mkdir uploads
   chmod 755 logs backups uploads
   ```

3. **Installer PHPMailer (optionnel)** :
   ```bash
   composer require phpmailer/phpmailer
   ```

## 📋 Scripts disponibles

### 1. Configuration (`config.php`)

Fichier de configuration centralisé contenant :
- Configuration Supabase
- Paramètres de l'application
- Configuration email
- Fonctions utilitaires
- Gestion des logs
- Validation des données

**Fonctions principales** :
- `getConfig($key)` : Obtenir une valeur de configuration
- `logMessage($level, $message, $context)` : Logger des messages
- `sanitizeInput($input)` : Nettoyer les entrées utilisateur
- `calculateDistance($lat1, $lon1, $lat2, $lon2)` : Calculer la distance
- `calculateDeliveryPrice($distance, ...)` : Calculer le prix de livraison

### 2. API Supabase (`supabase-api.php`)

Classe pour interagir avec l'API Supabase :
- Authentification
- CRUD operations
- Fonctions RPC
- Gestion des erreurs

**Méthodes principales** :
```php
$api = new SupabaseAPI();

// Authentification
$result = $api->authenticate($email, $password);

// Récupérer des données
$profiles = $api->getProfiles();
$deliveries = $api->getDeliveries();

// Créer des données
$result = $api->createDelivery($deliveryData);

// Exécuter des fonctions RPC
$stats = $api->getQRCodeStats($userId);
```

### 3. Tableau de bord Admin (`admin-dashboard.php`)

Interface d'administration complète avec :
- Statistiques en temps réel
- Graphiques interactifs
- Actions d'administration
- Activité récente
- Export de données

**Fonctionnalités** :
- Vue d'ensemble des utilisateurs, livraisons, QR codes
- Nettoyage automatique des anciens QR codes
- Export des données en JSON
- Vérification de santé de l'API

### 4. Générateur QR Codes (`qr-code-generator.php`)

API REST pour la gestion des QR codes :
- Génération de QR codes
- Gestion des types (livraison, utilisateur, paiement, etc.)
- Recherche et filtrage
- Statistiques d'utilisation

**Endpoints** :
```
GET ?action=list&user_id=123&type=delivery
GET ?action=stats&user_id=123
GET ?action=search&user_id=123&q=recherche
POST {"action": "generate", "content": "...", "type": "delivery"}
```

### 5. Gestionnaire de sauvegarde (`backup-manager.php`)

Système complet de sauvegarde et restauration :
- Sauvegarde complète de la base
- Sauvegarde par table
- Restauration de données
- Vérification d'intégrité
- Nettoyage automatique

**Fonctionnalités** :
```php
$backupManager = new BackupManager();

// Créer une sauvegarde complète
$result = $backupManager->createFullBackup();

// Lister les sauvegardes
$backups = $backupManager->listBackups();

// Restaurer une sauvegarde
$result = $backupManager->restoreBackup('backup_2024-01-15_10-30-00.json');
```

### 6. Service Email (`email-service.php`)

Service d'envoi d'emails avec templates :
- Emails de bienvenue
- Confirmations de livraison
- Notifications de statut
- Rapports quotidiens
- Support PHPMailer et mail() natif

**Templates disponibles** :
- `welcome` : Email de bienvenue
- `delivery_confirmation` : Confirmation de livraison
- `status_update` : Mise à jour de statut
- `payment_notification` : Confirmation de paiement
- `password_reset` : Réinitialisation de mot de passe
- `qr_code_notification` : Notification QR code
- `daily_report` : Rapport quotidien

## 🔧 Utilisation

### Accès aux interfaces web

1. **Tableau de bord admin** :
   ```
   http://votre-domaine/php/admin-dashboard.php
   ```

2. **Gestionnaire de sauvegarde** :
   ```
   http://votre-domaine/php/backup-manager.php
   ```

3. **Service email** :
   ```
   http://votre-domaine/php/email-service.php
   ```

### Utilisation en ligne de commande

```bash
# Créer une sauvegarde
php backup-manager.php --action=create_full

# Envoyer un email de test
php email-service.php --action=test --email=test@example.com

# Vérifier la configuration
php config.php --check
```

### Intégration dans d'autres scripts

```php
require_once 'php/config.php';
require_once 'php/supabase-api.php';

// Utiliser l'API Supabase
$api = new SupabaseAPI();
$profiles = $api->getProfiles();

// Utiliser le service email
$emailService = new EmailService();
$result = $emailService->sendWelcomeEmail($userData);

// Utiliser le gestionnaire de sauvegarde
$backupManager = new BackupManager();
$backup = $backupManager->createFullBackup();
```

## 🔒 Sécurité

### Bonnes pratiques

1. **Protection des fichiers** :
   ```apache
   # .htaccess
   <Files "*.php">
       Order Deny,Allow
       Deny from all
       Allow from 127.0.0.1
       Allow from ::1
   </Files>
   ```

2. **Validation des entrées** :
   ```php
   $input = sanitizeInput($_POST['data']);
   $email = validateEmail($_POST['email']);
   ```

3. **Gestion des erreurs** :
   ```php
   try {
       $result = $api->createUser($userData);
   } catch (Exception $e) {
       logMessage('ERROR', $e->getMessage());
   }
   ```

### Variables d'environnement

Pour la production, utilisez des variables d'environnement :

```php
// config.php
define('SUPABASE_URL', $_ENV['SUPABASE_URL'] ?? 'default-url');
define('SUPABASE_ANON_KEY', $_ENV['SUPABASE_ANON_KEY'] ?? 'default-key');
```

## 📊 Monitoring et logs

### Fichiers de logs

Les logs sont stockés dans `logs/` avec le format :
```
logs/
├── 2024-01-15.log
├── 2024-01-16.log
└── error.log
```

### Format des logs

```
[2024-01-15 10:30:45] [INFO] Application démarrée {"env":"development","version":"1.1.0"}
[2024-01-15 10:31:12] [ERROR] Erreur API Supabase {"endpoint":"profiles","error":"Connection timeout"}
```

### Rotation des logs

Ajoutez à votre crontab pour la rotation automatique :
```bash
# Rotation quotidienne des logs
0 0 * * * find /path/to/logs -name "*.log" -mtime +7 -delete
```

## 🚀 Déploiement

### Serveur de développement

```bash
# Démarrer le serveur PHP intégré
php -S localhost:8000 -t php/

# Accéder à l'interface
http://localhost:8000/admin-dashboard.php
```

### Serveur de production

1. **Apache** :
   ```apache
   <VirtualHost *:80>
       ServerName admin.livraisonp2p.com
       DocumentRoot /var/www/php
       
       <Directory /var/www/php>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. **Nginx** :
   ```nginx
   server {
       listen 80;
       server_name admin.livraisonp2p.com;
       root /var/www/php;
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
   }
   ```

## 🐛 Dépannage

### Problèmes courants

1. **Erreur de connexion Supabase** :
   - Vérifier les clés API dans `config.php`
   - Vérifier la connectivité réseau
   - Consulter les logs Supabase

2. **Erreur d'envoi d'email** :
   - Vérifier la configuration SMTP
   - Tester avec `email-service.php`
   - Vérifier les logs PHP

3. **Erreur de sauvegarde** :
   - Vérifier les permissions du dossier `backups/`
   - Vérifier l'espace disque disponible
   - Consulter les logs d'erreur

### Commandes de diagnostic

```bash
# Vérifier la configuration PHP
php -m | grep -E "(curl|json|openssl)"

# Tester la connexion Supabase
php -r "require 'config.php'; echo 'Config OK';"

# Vérifier les permissions
ls -la logs/ backups/ uploads/
```

## 📞 Support

Pour toute question ou problème :
- Consultez les logs dans `logs/`
- Vérifiez la documentation Supabase
- Contactez l'équipe de développement

---

**Version** : 1.1.0  
**Dernière mise à jour** : Janvier 2024  
**Auteur** : Équipe LivraisonP2P 