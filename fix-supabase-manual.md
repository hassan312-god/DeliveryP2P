# 🔧 CORRECTION MANUELLE SUPABASE - LivraisonP2P

## 🚨 PROBLÈME
Erreur "Database error saving new user" lors de l'inscription

## 📋 ÉTAPES À SUIVRE

### 1. **Ouvrir Supabase Dashboard**
- Va sur https://supabase.com/dashboard
- Connecte-toi à ton compte
- Sélectionne ton projet `syamapjohtlbjlyhlhsi`

### 2. **Aller dans SQL Editor**
- Dans le menu de gauche, clique sur **"SQL Editor"**
- Clique sur **"New query"**

### 3. **Copier et coller le script de correction**
Copie **TOUT** le contenu du fichier `CORRECTION-COMPLETE.sql` et colle-le dans l'éditeur SQL.

### 4. **Exécuter le script**
- Clique sur le bouton **"Run"** (ou appuie sur Ctrl+Enter)
- Attends que le script se termine
- Vérifie qu'il n'y a pas d'erreurs

### 5. **Vérifier que la table profiles existe**
Dans une nouvelle requête SQL, exécute :
```sql
SELECT * FROM profiles LIMIT 1;
```

### 6. **Tester l'inscription**
- Va sur http://localhost:8000/auth/register.html
- Tente de créer un nouvel utilisateur
- Vérifie que l'erreur a disparu

## 🔍 SI L'ERREUR PERSISTE

### Vérifier les logs Supabase
1. Dans Supabase Dashboard → **Logs** → **Database**
2. Tente une inscription
3. Regarde les logs d'erreur SQL
4. Copie ici le message d'erreur exact

### Vérifier la structure de la table
```sql
SELECT column_name, data_type, is_nullable, column_default
FROM information_schema.columns
WHERE table_name = 'profiles'
ORDER BY ordinal_position;
```

### Vérifier les triggers
```sql
SELECT trigger_name, event_object_table, action_timing, event_manipulation
FROM information_schema.triggers 
WHERE event_object_table = 'users';
```

## 📞 BESOIN D'AIDE ?
Si tu rencontres des difficultés, copie ici :
1. Le message d'erreur exact des logs Supabase
2. Le résultat de la requête de structure de table
3. Le résultat de la requête des triggers 