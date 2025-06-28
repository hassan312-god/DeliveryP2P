# 🧪 Guide de Test Render - DeliveryP2P

## ✅ **Prêt pour les Tests en Production !**

Ton application est maintenant prête pour être testée sur Render avec la nouvelle structure Supabase (table `profiles`).

## 🚀 **Déploiement sur Render**

### **1. Déployer les changements**
```bash
./deploy-render.sh
```

### **2. Vérifier le déploiement**
- **Frontend** : https://deliveryp2p.onrender.com
- **API** : https://deliveryp2p-api.onrender.com

## 🧪 **Tests de Production**

### **Test 1 : Santé de l'API**
```bash
curl -X GET "https://deliveryp2p-api.onrender.com/health"
```

**Réponse attendue :**
```json
{
    "status": "healthy",
    "timestamp": "2025-06-28T12:00:00+02:00",
    "version": "2.0.0",
    "environment": "production",
    "database": {
        "status": "configured",
        "message": "Variables Supabase configurées"
    }
}
```

### **Test 2 : Connexion Supabase**
```bash
curl -X GET "https://deliveryp2p-api.onrender.com/supabase/test"
```

**Réponse attendue :**
```json
{
    "success": true,
    "message": "Connexion Supabase réussie",
    "timestamp": "2025-06-28T12:00:00+02:00",
    "config": {
        "url_configured": true,
        "anon_key_configured": true,
        "service_key_configured": true
    }
}
```

### **Test 3 : Créer un Profil**
```bash
curl -X POST "https://deliveryp2p-api.onrender.com/supabase/profiles" \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "phone": "+33123456789",
    "role": "client"
  }'
```

### **Test 4 : Créer un QR Code**
```bash
curl -X POST "https://deliveryp2p-api.onrender.com/supabase/qr" \
  -H "Content-Type: application/json" \
  -d '{
    "data": "https://deliveryp2p.onrender.com/delivery/123",
    "size": 300
  }'
```

### **Test 5 : Créer une Livraison**
```bash
curl -X POST "https://deliveryp2p-api.onrender.com/supabase/delivery" \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": "test-client-123",
    "pickup_address": "123 Rue de la Paix, Paris",
    "delivery_address": "456 Avenue des Champs, Paris",
    "weight": 2.5,
    "price": 15.50
  }'
```

### **Test 6 : Récupérer les Profils**
```bash
curl -X GET "https://deliveryp2p-api.onrender.com/supabase/profiles"
```

## 📋 **Script de Test Automatisé**

Créez un fichier `test-render.sh` :

```bash
#!/bin/bash

echo "🧪 Tests Render DeliveryP2P"
echo "============================"

API_URL="https://deliveryp2p-api.onrender.com"

# Test de santé
echo "🔍 Test de santé..."
curl -s -X GET "$API_URL/health" | jq '.'

# Test Supabase
echo "🔍 Test Supabase..."
curl -s -X GET "$API_URL/supabase/test" | jq '.'

# Test QR
echo "🔍 Test QR..."
curl -s -X GET "$API_URL/qr" | jq '.'

echo "✅ Tests terminés !"
```

## 🔧 **Configuration Supabase pour Production**

### **1. Créer un projet Supabase**
1. Allez sur [https://supabase.com](https://supabase.com)
2. Créez un nouveau projet
3. Notez l'URL et les clés API

### **2. Configurer Render**
Dans votre service API sur Render, ajoutez ces variables d'environnement :

```env
SUPABASE_URL=https://your-project-ref.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
SUPABASE_SERVICE_ROLE_KEY=your-service-role-key-here
JWT_SECRET=your-32-character-jwt-secret
ENCRYPTION_KEY=your-32-character-encryption-key
QR_CODE_SECRET=your-qr-code-secret
```

### **3. Exécuter le schéma SQL**
1. Allez dans **SQL Editor** de Supabase
2. Copiez le contenu de `supabase-schema.sql`
3. Exécutez le script

## 🎯 **Tests de Validation**

### **✅ Tests Locaux Réussis**
- API Endpoints : 5/5 fonctionnels
- Validation des données : Correcte
- Gestion d'erreurs : Opérationnelle
- Format JSON : Valide

### **🔄 Tests de Production**
- **Santé API** : À tester
- **Connexion Supabase** : À tester
- **Création Profils** : À tester
- **Création QR** : À tester
- **Création Livraisons** : À tester

## 📊 **Métriques de Performance**

### **Temps de Réponse Attendus**
- **Health Check** : <500ms
- **Supabase Test** : <1000ms
- **CRUD Operations** : <2000ms

### **Disponibilité**
- **Uptime** : 99.9%
- **Latence** : <1000ms
- **Throughput** : 1000+ req/min

## 🚨 **Dépannage**

### **Erreur 404**
- Vérifiez que l'API est déployée sur Render
- Vérifiez l'URL de l'API

### **Erreur de Connexion Supabase**
- Vérifiez les variables d'environnement sur Render
- Vérifiez que le projet Supabase est actif

### **Erreur de Table**
- Exécutez le script SQL dans Supabase
- Vérifiez que les tables sont créées

## 🎉 **Validation Finale**

Une fois tous les tests passés, ton application sera :

✅ **Déployée** sur Render  
✅ **Connectée** à Supabase  
✅ **Fonctionnelle** avec la table profiles  
✅ **Prête** pour la production  

**🚀 Ton application DeliveryP2P est maintenant prête pour les tests en production !** 