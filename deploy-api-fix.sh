#!/bin/bash

# Script de déploiement pour corriger le routage API sur Render
# Auteur: LivraisonP2P Team
# Version: 1.0.0

set -e

echo "🚀 Déploiement de la correction API sur Render..."

# Configuration
RENDER_SERVICE_NAME="deliveryp2p"
RENDER_API_URL="https://api.render.com/v1/services"

# Vérification des variables d'environnement
if [ -z "$RENDER_API_KEY" ]; then
    echo "❌ Erreur: RENDER_API_KEY n'est pas définie"
    echo "Veuillez définir votre clé API Render:"
    echo "export RENDER_API_KEY=votre_clé_api"
    exit 1
fi

# Récupération du service ID
echo "📋 Récupération du service ID..."
SERVICE_ID=$(curl -s -H "Authorization: Bearer $RENDER_API_KEY" \
    "$RENDER_API_URL" | jq -r ".[] | select(.name == \"$RENDER_SERVICE_NAME\") | .id")

if [ -z "$SERVICE_ID" ] || [ "$SERVICE_ID" = "null" ]; then
    echo "❌ Erreur: Service '$RENDER_SERVICE_NAME' non trouvé"
    exit 1
fi

echo "✅ Service ID trouvé: $SERVICE_ID"

# Déclenchement du redéploiement
echo "🔄 Déclenchement du redéploiement..."
DEPLOY_RESPONSE=$(curl -s -X POST \
    -H "Authorization: Bearer $RENDER_API_KEY" \
    -H "Content-Type: application/json" \
    "$RENDER_API_URL/$SERVICE_ID/deploys")

DEPLOY_ID=$(echo "$DEPLOY_RESPONSE" | jq -r '.id')

if [ -z "$DEPLOY_ID" ] || [ "$DEPLOY_ID" = "null" ]; then
    echo "❌ Erreur: Impossible de déclencher le redéploiement"
    echo "Réponse: $DEPLOY_RESPONSE"
    exit 1
fi

echo "✅ Redéploiement déclenché: $DEPLOY_ID"

# Attente du déploiement
echo "⏳ Attente du déploiement..."
while true; do
    DEPLOY_STATUS=$(curl -s -H "Authorization: Bearer $RENDER_API_KEY" \
        "$RENDER_API_URL/$SERVICE_ID/deploys/$DEPLOY_ID" | jq -r '.status')
    
    echo "📊 Statut: $DEPLOY_STATUS"
    
    case $DEPLOY_STATUS in
        "live")
            echo "✅ Déploiement terminé avec succès!"
            break
            ;;
        "failed")
            echo "❌ Déploiement échoué"
            exit 1
            ;;
        "canceled")
            echo "❌ Déploiement annulé"
            exit 1
            ;;
        *)
            echo "⏳ Déploiement en cours..."
            sleep 10
            ;;
    esac
done

# Test de l'API après déploiement
echo "🧪 Test de l'API après déploiement..."

# Attendre que le service soit prêt
echo "⏳ Attente que le service soit prêt..."
sleep 30

# Test du health endpoint
echo "📡 Test du health endpoint..."
HEALTH_RESPONSE=$(curl -s "https://deliveryp2p-go4x.onrender.com/health")
if echo "$HEALTH_RESPONSE" | grep -q "healthy"; then
    echo "✅ Health endpoint fonctionne"
else
    echo "❌ Health endpoint ne fonctionne pas"
    echo "Réponse: $HEALTH_RESPONSE"
fi

# Test du test-simple endpoint
echo "📡 Test du test-simple endpoint..."
TEST_RESPONSE=$(curl -s "https://deliveryp2p-go4x.onrender.com/test-simple")
if echo "$TEST_RESPONSE" | grep -q "API is working correctly"; then
    echo "✅ Test-simple endpoint fonctionne"
else
    echo "❌ Test-simple endpoint ne fonctionne pas"
    echo "Réponse: $TEST_RESPONSE"
fi

# Test d'un endpoint d'authentification
echo "📡 Test d'un endpoint d'authentification..."
AUTH_RESPONSE=$(curl -s -X POST \
    -H "Content-Type: application/json" \
    -d '{"test":"data"}' \
    "https://deliveryp2p-go4x.onrender.com/auth/register")
if echo "$AUTH_RESPONSE" | grep -q "success\|error"; then
    echo "✅ Endpoint d'authentification fonctionne (réponse API)"
else
    echo "❌ Endpoint d'authentification ne fonctionne pas (réponse HTML)"
    echo "Réponse: ${AUTH_RESPONSE:0:200}..."
fi

echo "🎉 Déploiement terminé!"
echo "🌐 URL de l'application: https://deliveryp2p-go4x.onrender.com"
echo "🔗 URL de l'API: https://deliveryp2p-go4x.onrender.com/health" 