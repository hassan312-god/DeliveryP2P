# Guide de Configuration Supabase pour LivraisonP2P

## 📋 Vue d'ensemble

Ce guide détaille la configuration complète de Supabase pour l'application LivraisonP2P, incluant la création de la base de données, la configuration des clés API, et l'intégration avec le backend PHP.

## 🚀 Étape 1: Création du Projet Supabase

### 1.1 Créer un compte Supabase
1. Aller sur [supabase.com](https://supabase.com)
2. Cliquer sur "Start your project"
3. Se connecter avec GitHub ou créer un compte

### 1.2 Créer un nouveau projet
1. Cliquer sur "New Project"
2. Choisir une organisation
3. Remplir les informations :
   - **Nom du projet** : `livraisonp2p`
   - **Mot de passe de la base de données** : Choisir un mot de passe sécurisé
   - **Région** : Choisir la région la plus proche (ex: West Europe)
4. Cliquer sur "Create new project"

### 1.3 Attendre l'initialisation
- Le projet prend environ 2-3 minutes à s'initialiser
- Vous recevrez un email de confirmation

## 🔑 Étape 2: Récupération des Clés API

### 2.1 Accéder aux paramètres API
1. Dans le tableau de bord Supabase, aller dans **Settings** (⚙️)
2. Cliquer sur **API** dans le menu de gauche

### 2.2 Récupérer les informations nécessaires
Vous verrez plusieurs sections importantes :

#### **Project URL**
```
https://your-project-id.supabase.co
```

#### **API Keys**
- **anon public** : Clé publique pour les requêtes anonymes
- **service_role secret** : Clé secrète pour les opérations administratives

#### **Project API keys**
```
anon: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
service_role: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

## 🗄️ Étape 3: Création de la Base de Données

### 3.1 Accéder à l'éditeur SQL
1. Dans le tableau de bord, aller dans **SQL Editor**
2. Cliquer sur **New query**

### 3.2 Exécuter le script SQL
1. Copier le contenu du fichier `database/schema_complete.sql`
2. Coller dans l'éditeur SQL
3. Cliquer sur **Run** pour exécuter le script

### 3.3 Vérifier la création des tables
1. Aller dans **Table Editor**
2. Vérifier que toutes les tables sont créées :
   - `users`
   - `ads`
   - `proposals`
   - `deliveries`
   - `evaluations`
   - `chat_messages`
   - `notifications`
   - `web_push_subscriptions`
   - `parcel_photos`
   - `delivery_photos`

## ⚙️ Étape 4: Configuration PHP

### 4.1 Créer le fichier .env
1. Copier le fichier `.env.example` vers `.env`
2. Remplir les variables avec vos clés Supabase :

```bash
# Configuration Supabase
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your_anon_key
SUPABASE_SERVICE_ROLE_KEY=your_service_role_key

# Configuration Google Maps
GOOGLE_MAPS_API_KEY=your_google_maps_api_key

# Configuration de l'application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Configuration de sécurité
JWT_SECRET=your_jwt_secret_key_here
SESSION_SECRET=your_session_secret_key_here
```

### 4.2 Vérifier la configuration
Le fichier `app/config/database.php` est déjà configuré pour utiliser ces variables d'environnement.

## 🔧 Étape 5: Configuration du Storage

### 5.1 Créer un bucket pour les fichiers
1. Aller dans **Storage** dans le tableau de bord
2. Cliquer sur **New bucket**
3. Nommer le bucket : `livraisonp2p`
4. Choisir **Public** pour les fichiers accessibles publiquement
5. Cliquer sur **Create bucket**

### 5.2 Configurer les politiques RLS
1. Aller dans **Storage** > **Policies**
2. Créer des politiques pour sécuriser l'accès aux fichiers

Exemple de politique pour les photos de véhicules :
```sql
CREATE POLICY "Users can upload their own vehicle photos" ON storage.objects
FOR INSERT WITH CHECK (
  bucket_id = 'livraisonp2p' AND 
  auth.uid()::text = (storage.foldername(name))[1]
);
```

## 📧 Étape 6: Configuration des Emails (Optionnel)

### 6.1 Configurer l'authentification par email
1. Aller dans **Authentication** > **Settings**
2. Configurer les templates d'email personnalisés
3. Activer la confirmation d'email si nécessaire

### 6.2 Templates d'email recommandés
- **Confirmation d'inscription**
- **Réinitialisation de mot de passe**
- **Notifications de livraison**

## 🔐 Étape 7: Configuration de la Sécurité

### 7.1 Politiques RLS (Row Level Security)
Les politiques RLS sont déjà incluses dans le script SQL. Vérifiez qu'elles sont activées :

```sql
-- Vérifier que RLS est activé
SELECT schemaname, tablename, rowsecurity 
FROM pg_tables 
WHERE schemaname = 'public';
```

### 7.2 Configurer les politiques personnalisées
Si nécessaire, ajustez les politiques selon vos besoins de sécurité.

## 🧪 Étape 8: Test de Connexion

### 8.1 Tester la connexion PHP
Créer un script de test simple :

```php
<?php
require_once 'vendor/autoload.php';

use App\Services\SupabaseService;

$supabase = new SupabaseService();

// Test de connexion
$result = $supabase->select('users', [], '*', 1);
if (isset($result['error'])) {
    echo "Erreur de connexion: " . $result['message'];
} else {
    echo "Connexion réussie !";
}
```

### 8.2 Tester l'authentification
```php
// Test d'inscription
$authResult = $supabase->signUp('test@example.com', 'password123', [
    'data' => [
        'first_name' => 'Test',
        'last_name' => 'User',
        'role' => 'expeditor'
    ]
]);

if (!isset($authResult['error'])) {
    echo "Inscription réussie !";
}
```

## 📱 Étape 9: Configuration des Notifications

### 9.1 Activer Realtime
1. Aller dans **Database** > **Replication**
2. Activer Realtime pour les tables nécessaires :
   - `chat_messages`
   - `notifications`
 