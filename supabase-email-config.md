# Configuration des Emails Supabase pour LivraisonP2P

## 1. Accéder au Dashboard Supabase

1. Allez sur [https://supabase.com/dashboard](https://supabase.com/dashboard)
2. Connectez-vous à votre compte
3. Sélectionnez votre projet : `syamapjohtlbjlyhlhsi`

## 2. Configuration de l'Authentification

### Étape 1 : Aller dans Authentication > Settings

1. Dans le menu de gauche, cliquez sur **Authentication**
2. Puis cliquez sur **Settings**

### Étape 2 : Configurer les URLs de redirection

Dans la section **URL Configuration**, configurez :

```
Site URL: https://livraisonp2p.netlify.app
Redirect URLs: 
- https://livraisonp2p.netlify.app/auth/callback.html
- https://livraisonp2p.netlify.app/auth/email-confirmation.html
- https://livraisonp2p.netlify.app/auth/reset-password.html
- http://localhost:8000/auth/callback.html
- http://localhost:8000/auth/email-confirmation.html
- http://localhost:8000/auth/reset-password.html
```

### Étape 3 : Activer la confirmation d'email

1. Dans **Email Auth**, activez :
   - ✅ **Enable email confirmations**
   - ✅ **Enable secure email change**
   - ✅ **Enable email confirmations on sign up**

2. Configurez les templates d'email :
   - **Confirmation Email Template**
   - **Invitation Email Template**
   - **Magic Link Email Template**
   - **Change Email Address Template**

## 3. Configuration des Templates d'Email

### Template de Confirmation d'Email

**Sujet :** `Confirmez votre compte LivraisonP2P`

**Contenu HTML :**
```html
<h2>Bienvenue sur LivraisonP2P !</h2>
<p>Bonjour {{ .Email }},</p>
<p>Merci de vous être inscrit sur LivraisonP2P. Pour activer votre compte, veuillez cliquer sur le lien ci-dessous :</p>
<p><a href="{{ .ConfirmationURL }}" style="background: #3B82F6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Confirmer mon compte</a></p>
<p>Ou copiez ce lien dans votre navigateur :</p>
<p>{{ .ConfirmationURL }}</p>
<p>Ce lien expirera dans 24 heures.</p>
<p>Si vous n'avez pas créé de compte, vous pouvez ignorer cet email.</p>
<p>Cordialement,<br>L'équipe LivraisonP2P</p>
```

### Template de Réinitialisation de Mot de Passe

**Sujet :** `Réinitialisation de votre mot de passe LivraisonP2P`

**Contenu HTML :**
```html
<h2>Réinitialisation de mot de passe</h2>
<p>Bonjour,</p>
<p>Vous avez demandé la réinitialisation de votre mot de passe LivraisonP2P.</p>
<p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
<p><a href="{{ .ConfirmationURL }}" style="background: #3B82F6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">Réinitialiser mon mot de passe</a></p>
<p>Ou copiez ce lien dans votre navigateur :</p>
<p>{{ .ConfirmationURL }}</p>
<p>Ce lien expirera dans 1 heure.</p>
<p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
<p>Cordialement,<br>L'équipe LivraisonP2P</p>
```

## 4. Configuration SMTP (Optionnel)

Si vous voulez utiliser votre propre serveur SMTP :

1. Dans **Authentication > Settings > SMTP Settings**
2. Activez **Custom SMTP**
3. Configurez :
   - **Host** : votre serveur SMTP
   - **Port** : 587 (TLS) ou 465 (SSL)
   - **Username** : votre email
   - **Password** : votre mot de passe
   - **Sender Name** : LivraisonP2P
   - **Sender Email** : noreply@livraisonp2p.com

## 5. Test de la Configuration

### Test 1 : Inscription
1. Allez sur votre site d'inscription
2. Créez un compte avec un email valide
3. Vérifiez que l'email de confirmation est reçu

### Test 2 : Réinitialisation de mot de passe
1. Allez sur la page de connexion
2. Cliquez sur "Mot de passe oublié"
3. Entrez votre email
4. Vérifiez que l'email de réinitialisation est reçu

## 6. Variables d'Environnement

Assurez-vous que ces variables sont configurées dans votre projet :

```env
SUPABASE_URL=https://syamapjohtlbjlyhlhsi.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InN5YW1hcGpvaHRsYmpseWhsaHNpIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTAzNzAxNjksImV4cCI6MjA2NTk0NjE2OX0._Sl5uiiUKK58wPa-lJG-TPe7K9rdem6Uc2V4epyhx_M
```

## 7. Dépannage

### Problème : Emails non reçus
1. Vérifiez les spams
2. Vérifiez la configuration SMTP
3. Vérifiez les logs dans Supabase Dashboard > Logs

### Problème : Liens de confirmation cassés
1. Vérifiez les URLs de redirection
2. Vérifiez que les pages de confirmation existent
3. Testez les URLs manuellement

### Problème : Erreurs d'authentification
1. Vérifiez les clés API
2. Vérifiez la configuration CORS
3. Vérifiez les logs de la console 