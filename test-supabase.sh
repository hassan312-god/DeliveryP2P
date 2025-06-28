#!/bin/bash

echo "🧪 Tests Supabase DeliveryP2P"
echo "================================"

# Configuration
API_URL="http://localhost:8000"
# API_URL="https://deliveryp2p-api.onrender.com"  # Pour tester en production

# Couleurs pour les tests
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Fonction de test
test_endpoint() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    
    echo -e "\n${YELLOW}🔍 Test: $description${NC}"
    echo "Endpoint: $method $endpoint"
    
    if [ "$method" = "POST" ] && [ ! -z "$data" ]; then
        response=$(curl -s -X $method "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data")
    else
        response=$(curl -s -X $method "$API_URL$endpoint")
    fi
    
    if [ $? -eq 0 ] && [ ! -z "$response" ]; then
        echo -e "${GREEN}✅ Succès${NC}"
        echo "Réponse: $response" | head -c 300
        echo "..."
    else
        echo -e "${RED}❌ Échec${NC}"
        echo "Erreur: $response"
    fi
}

# Tests
echo -e "\n${BLUE}🚀 Démarrage des tests Supabase...${NC}"

# Test 1: Test de connexion Supabase
test_endpoint "GET" "/supabase/test" "" "Test de connexion Supabase"

# Test 2: Créer un profil
test_endpoint "POST" "/supabase/profiles" '{
    "first_name": "Test",
    "last_name": "User",
    "phone": "+33123456789",
    "role": "client"
}' "Créer un profil"

# Test 3: Créer un QR code
test_endpoint "POST" "/supabase/qr" '{
    "data": "https://deliveryp2p.onrender.com/delivery/123",
    "size": 300
}' "Créer un QR code"

# Test 4: Créer une livraison
test_endpoint "POST" "/supabase/delivery" '{
    "client_id": "test-client-123",
    "pickup_address": "123 Rue de la Paix, Paris",
    "delivery_address": "456 Avenue des Champs, Paris",
    "weight": 2.5,
    "price": 15.50
}' "Créer une livraison"

# Test 5: Récupérer les profils
test_endpoint "GET" "/supabase/profiles" "" "Récupérer les profils"

echo -e "\n${GREEN}🎉 Tests Supabase terminés !${NC}"
echo "================================"

echo -e "\n${BLUE}📋 Instructions pour configurer Supabase :${NC}"
echo "1. Créez un projet sur https://supabase.com"
echo "2. Récupérez vos clés API dans Settings > API"
echo "3. Créez les tables nécessaires avec supabase-schema.sql :"
echo "   - profiles (pour les utilisateurs)"
echo "   - qr_codes (pour les QR codes)"
echo "   - deliveries (pour les livraisons)"
echo "4. Configurez les variables d'environnement dans .env"
echo "5. Relancez les tests avec ce script" 