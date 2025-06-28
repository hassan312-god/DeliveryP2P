# 🔧 Correction du problème "Database error saving new user"

## Problème identifié
L'erreur "Database error saving new user" se produit car :
1. **Mauvais format de données** : Le JS envoyait des champs avec des valeurs par défaut
2. **Policies RLS manquantes** : Aucune policy pour permettre l'insertion de profils
3. **Structure de données incorrecte** : Certains champs n'étaient pas alignés avec la table

## ✅ Corrections apportées

### 1. Code JavaScript corrigé (`js/modules/auth.js`)
```javascript
// AVANT (problématique)
const profileData = {
    id: authData.user.id,
    email: userData.email,
    nom: userData.nom,
    prenom: userData.prenom,
    telephone: userData.telephone || null,
    role: userData.role || 'client',
    date_inscription: new Date().toISOString(), // ❌ Problème
    statut: 'en_attente_confirmation' // ❌ Valeur par défaut
};

// APRÈS (corrigé)
const profileData = {
    id: authData.user.id,
    email: userData.email,
    nom: userData.nom,
    prenom: userData.prenom,
    telephone: userData.telephone || null,
    role: userData.role || 'client',
    email_confirme: false,
    is_active: true
};
```

### 2. Policies RLS créées (`fix-supabase-profiles.sql`)
- Policy pour permettre l'insertion de profils
- Policy pour permettre la lecture/modification de son propre profil
- Policy pour les admins

## 🚀 Instructions pour appliquer les corrections

### Étape 1 : Appliquer le script SQL dans Supabase
1. Allez sur [https://supabase.com/dashboard](https://supabase.com/dashboard)
2. Sélectionnez votre projet
3. Allez dans **SQL Editor**
4. Copiez et collez le contenu du fichier `fix-supabase-profiles.sql`
5. Cliquez sur **Run** pour exécuter le script

### Étape 2 : Vérifier la configuration
1. Allez dans **Table Editor** > **profiles**
2. Vérifiez que RLS est activé (icône de cadenas)
3. Vérifiez que les policies sont créées dans l'onglet **Policies**

### Étape 3 : Tester l'inscription
1. Allez sur `http://localhost:8000/auth/register.html`
2. Créez un nouveau compte
3. Vérifiez que l'inscription fonctionne sans erreur
4. Vérifiez que l'email de confirmation est envoyé

## 🔍 Vérification des corrections

### Dans la console du navigateur
Vous devriez voir :
```
✅ Inscription réussie!
User ID: [uuid]
Email: [email]
Email confirmé: Non
Un email de confirmation a été envoyé à [email]
```

### Dans Supabase Studio
1. **Authentication** > **Users** : L'utilisateur doit apparaître
2. **Table Editor** > **profiles** : Le profil doit être créé
3. **Logs** : Pas d'erreur d'insertion

## 🐛 Dépannage

### Si l'erreur persiste
1. **Vérifiez les logs** dans la console du navigateur
2. **Vérifiez les logs Supabase** dans **Logs** > **Database**
3. **Vérifiez les policies RLS** dans **Table Editor** > **profiles** > **Policies**

### Erreurs courantes
- **"new row violates row-level security policy"** : Policy RLS manquante
- **"column does not exist"** : Mauvais nom de colonne
- **"invalid input syntax for type uuid"** : Mauvais format d'ID

## 📋 Checklist de vérification

- [ ] Script SQL exécuté dans Supabase
- [ ] RLS activé sur la table profiles
- [ ] Policies créées et actives
- [ ] Code JavaScript mis à jour
- [ ] Test d'inscription réussi
- [ ] Email de confirmation reçu
- [ ] Profil créé dans la table profiles

## 🎯 Résultat attendu
Après ces corrections, l'inscription devrait fonctionner parfaitement :
1. ✅ Création du compte auth réussie
2. ✅ Création du profil utilisateur réussie
3. ✅ Email de confirmation envoyé
4. ✅ Redirection vers la page de confirmation 