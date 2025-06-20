# Documentation Base de Données - LivraisonP2P

## 📋 Vue d'ensemble

Cette documentation décrit les fonctionnalités backend et base de données ajoutées à l'application LivraisonP2P.

## 🗂️ Fichiers de Base de Données

### 1. `schema.sql` - Schéma Principal
- Tables principales (profiles, deliveries, payments, etc.)
- Index de performance
- Politiques RLS de base
- Fonctions utilitaires

### 2. `functions.sql` - Fonctions Avancées
- Calcul automatique des prix
- Mise à jour des statistiques utilisateur
- Gestion des notifications automatiques
- Fonctions de recherche et calcul

### 3. `triggers.sql` - Triggers Automatiques
- Validation des données
- Création automatique de paiements
- Audit des actions
- Gestion des messages

### 4. `views.sql` - Vues pour Simplifier les Requêtes
- Vues pour les livraisons complètes
- Statistiques utilisateurs
- Rapports de performance
- Données agrégées

### 5. `security.sql` - Sécurité Avancée
- Politiques RLS supplémentaires
- Fonctions de vérification des permissions
- Audit des actions
- Rôles et permissions

### 6. `backup.sql` - Maintenance et Sauvegarde
- Fonctions de sauvegarde
- Nettoyage automatique
- Optimisation des performances
- Surveillance de la santé

## 🚀 Installation

### Étape 1: Exécuter le Schéma Principal
```sql
-- Dans l'éditeur SQL de Supabase
-- Exécuter le contenu de schema.sql
```

### Étape 2: Ajouter les Fonctions
```sql
-- Exécuter le contenu de functions.sql
```

### Étape 3: Configurer les Triggers
```sql
-- Exécuter le contenu de triggers.sql
```

### Étape 4: Créer les Vues
```sql
-- Exécuter le contenu de views.sql
```

### Étape 5: Configurer la Sécurité
```sql
-- Exécuter le contenu de security.sql
```

### Étape 6: Ajouter la Maintenance
```sql
-- Exécuter le contenu de backup.sql
```

## 🔧 Fonctionnalités Principales

### 1. Calcul Automatique des Prix
```sql
-- Le prix est calculé automatiquement lors de la création d'une livraison
INSERT INTO deliveries (
    client_id, 
    pickup_address, 
    delivery_address, 
    priority
) VALUES (
    'user-uuid', 
    'Adresse de ramassage', 
    'Adresse de livraison', 
    'urgent'
);
-- Le prix final sera calculé automatiquement
```

### 2. Notifications Automatiques
```sql
-- Les notifications sont créées automatiquement lors des changements de statut
UPDATE deliveries 
SET status = 'accepted', courier_id = 'courier-uuid' 
WHERE id = 'delivery-uuid';
-- Une notification sera créée automatiquement
```

### 3. Statistiques Utilisateur
```sql
-- Obtenir les statistiques d'un utilisateur
SELECT * FROM get_user_stats('user-uuid');
```

### 4. Recherche de Livreurs
```sql
-- Trouver des livreurs disponibles près d'un point
SELECT * FROM find_available_couriers(14.7167, -17.4677, 5.0);
```

### 5. Historique des Livraisons
```sql
-- Obtenir l'historique des livraisons d'un utilisateur
SELECT * FROM get_delivery_history('user-uuid', 20, 0);
```

## 🔐 Sécurité

### Politiques RLS
- **Profils**: Les utilisateurs ne voient que leur propre profil
- **Livraisons**: Les utilisateurs voient seulement les livraisons auxquelles ils participent
- **Paiements**: Accès limité aux participants
- **Messages**: Limités aux participants de la livraison

### Vérification des Permissions
```sql
-- Vérifier une permission
SELECT check_user_permission('create_delivery', 'delivery', 'delivery-uuid');
```

### Audit des Actions
```sql
-- Toutes les actions importantes sont auditées automatiquement
-- Voir les logs dans la console Supabase
```

## 📊 Vues Disponibles

### 1. `deliveries_view`
Vue complète des livraisons avec toutes les informations
```sql
SELECT * FROM deliveries_view WHERE status = 'pending';
```

### 2. `users_stats_view`
Statistiques complètes des utilisateurs
```sql
SELECT * FROM users_stats_view WHERE role = 'livreur';
```

### 3. `payments_view`
Informations détaillées des paiements
```sql
SELECT * FROM payments_view WHERE status = 'completed';
```

### 4. `active_couriers_view`
Livreurs actifs avec localisation
```sql
SELECT * FROM active_couriers_view WHERE online_status = 'En ligne';
```

### 5. `daily_stats_view`
Statistiques quotidiennes
```sql
SELECT * FROM daily_stats_view ORDER BY date DESC LIMIT 30;
```

## 🛠️ Maintenance

### Nettoyage Automatique
```sql
-- Nettoyer les anciennes données
SELECT * FROM cleanup_old_data_scheduled();
```

### Optimisation
```sql
-- Optimiser les performances
SELECT * FROM optimize_database();
```

### Surveillance
```sql
-- Vérifier la santé de la base de données
SELECT * FROM check_database_health();
```

### Rapports
```sql
-- Générer un rapport de maintenance
SELECT * FROM generate_maintenance_report();
```

## 📈 Performance

### Index Créés
- Index sur les clés étrangères
- Index sur les statuts
- Index temporels
- Index géographiques
- Index de recherche textuelle

### Optimisations
- Calcul automatique des prix
- Mise à jour des statistiques en temps réel
- Nettoyage automatique des données anciennes
- Cache des requêtes fréquentes

## 🔍 Monitoring

### Métriques Surveillées
- Taille de la base de données
- Connexions actives
- Ratio de cache
- Index non utilisés
- Performance des requêtes

### Alertes
- Taille de DB > 500MB
- Connexions actives > 100
- Ratio de cache < 80%
- Index non utilisés détectés

## 🚨 Dépannage

### Problèmes Courants

1. **Erreur de permission**
   ```sql
   -- Vérifier les politiques RLS
   SELECT * FROM pg_policies WHERE tablename = 'deliveries';
   ```

2. **Performance lente**
   ```sql
   -- Vérifier les index
   SELECT * FROM pg_indexes WHERE tablename = 'deliveries';
   ```

3. **Données corrompues**
   ```sql
   -- Vérifier l'intégrité
   SELECT * FROM optimize_database();
   ```

### Logs et Debugging
```sql
-- Voir les dernières actions auditées
SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 10;

-- Vérifier les erreurs
SELECT * FROM pg_stat_activity WHERE state = 'active';
```

## 🔄 Sauvegarde et Restauration

### Sauvegarde Manuelle
```sql
-- Créer une sauvegarde
SELECT create_data_backup();
```

### Sauvegarde Automatique
```sql
-- Programmer une sauvegarde automatique
-- À configurer dans Supabase Dashboard
```

### Restauration
```sql
-- Restaurer depuis une sauvegarde
SELECT restore_from_backup('backup_20241201_143022');
```

## 📝 API Functions

### Fonctions Disponibles via l'API REST

1. **get_user_stats(user_id)**
   - Statistiques complètes d'un utilisateur

2. **find_available_couriers(lat, lon, max_distance)**
   - Recherche de livreurs disponibles

3. **get_delivery_history(user_id, limit, offset)**
   - Historique des livraisons

4. **get_earnings_by_period(user_id, start_date, end_date)**
   - Revenus par période

5. **get_global_stats()**
   - Statistiques globales (admin)

### Utilisation via JavaScript
```javascript
// Exemple d'utilisation
const { data, error } = await supabase
    .rpc('get_user_stats', { user_uuid: 'user-id' });

if (error) console.error('Erreur:', error);
else console.log('Statistiques:', data);
```

## 🔮 Fonctionnalités Futures

### Planifiées
- [ ] Partitionnement des tables par date
- [ ] Réplication en lecture seule
- [ ] Cache Redis pour les requêtes fréquentes
- [ ] Analytics avancés
- [ ] Machine Learning pour l'optimisation des prix

### Optimisations
- [ ] Index partiels pour les requêtes fréquentes
- [ ] Compression des données anciennes
- [ ] Archivage automatique
- [ ] Monitoring en temps réel

## 📞 Support

Pour toute question concernant la base de données :

1. Consultez les logs dans Supabase Dashboard
2. Vérifiez les politiques RLS
3. Testez les fonctions individuellement
4. Contactez l'équipe de développement

---

**Note**: Toutes ces fonctionnalités sont conçues pour fonctionner avec Supabase et respectent les meilleures pratiques de sécurité et de performance. 