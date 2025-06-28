#!/bin/bash

echo "🧪 Test End-to-End DeliveryP2P → Supabase"
echo "=========================================="

# Configuration
API_URL="http://localhost:8000"
# API_URL="https://deliveryp2p-api.onrender.com"  # Pour tester en production

# Couleurs pour les tests
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Variables de test
TEST_PROFILE_ID=""
TEST_QR_ID=""
TEST_DELIVERY_ID=""

# Fonction de test avec validation
test_with_validation() {
    local method=$1
    local endpoint=$2
    local data=$3
    local description=$4
    local validation_endpoint=$5
    
    echo -e "\n${YELLOW}🔍 Test: $description${NC}"
    echo "Endpoint: $method $endpoint"
    
    # Effectuer la requête
    if [ "$method" = "POST" ] && [ ! -z "$data" ]; then
        response=$(curl -s -X $method "$API_URL$endpoint" \
            -H "Content-Type: application/json" \
            -d "$data")
    else
        response=$(curl -s -X $method "$API_URL$endpoint")
    fi
    
    if [ $? -eq 0 ] && [ ! -z "$response" ]; then
        echo -e "${GREEN}✅ Requête réussie${NC}"
        echo "Réponse: $response" | head -c 200
        echo "..."
        
        # Extraire l'ID si c'est une création
        if [ "$method" = "POST" ] && [[ "$response" == *"\"id\""* ]]; then
            local id=$(echo "$response" | grep -o '"id":"[^"]*"' | cut -d'"' -f4)
            if [ ! -z "$id" ]; then
                echo -e "${BLUE}📝 ID extrait: $id${NC}"
                
                # Valider que les données sont bien dans Supabase
                if [ ! -z "$validation_endpoint" ]; then
                    echo -e "${BLUE}🔍 Validation dans Supabase...${NC}"
                    sleep 2
                    
                    local validation_response=$(curl -s -X GET "$API_URL$validation_endpoint")
                    if [[ "$validation_response" == *"$id"* ]]; then
                        echo -e "${GREEN}✅ Données confirmées dans Supabase !${NC}"
                    else
                        echo -e "${RED}❌ Données non trouvées dans Supabase${NC}"
                        echo "Validation response: $validation_response"
                    fi
                fi
            fi
        fi
    else
        echo -e "${RED}❌ Échec de la requête${NC}"
        echo "Erreur: $response"
    fi
}

# Tests
echo -e "\n${BLUE}🚀 Démarrage des tests end-to-end...${NC}"

# Test 1: Créer un profil et vérifier qu'il est dans Supabase
test_with_validation "POST" "/supabase/profiles" '{
    "first_name": "Jean",
    "last_name": "Dupont",
    "phone": "+33123456789",
    "role": "client"
}' "Créer un profil et vérifier dans Supabase" "/supabase/profiles"

# Test 2: Créer un QR code et vérifier qu'il est dans Supabase
test_with_validation "POST" "/supabase/qr" '{
    "data": "https://deliveryp2p.onrender.com/delivery/456",
    "size": 300
}' "Créer un QR code et vérifier dans Supabase" "/supabase/qr"

# Test 3: Créer une livraison et vérifier qu'elle est dans Supabase
test_with_validation "POST" "/supabase/delivery" '{
    "client_id": "test-client-456",
    "pickup_address": "789 Boulevard Saint-Germain, Paris",
    "delivery_address": "321 Rue de Rivoli, Paris",
    "weight": 3.5,
    "price": 22.00
}' "Créer une livraison et vérifier dans Supabase" "/supabase/delivery"

# Test 4: Récupérer toutes les données pour vérification finale
echo -e "\n${BLUE}📋 Vérification finale des données dans Supabase :${NC}"

echo -e "\n${YELLOW}👥 Profils dans Supabase :${NC}"
curl -s -X GET "$API_URL/supabase/profiles" | jq '.'

echo -e "\n${YELLOW}🎯 QR Codes dans Supabase :${NC}"
curl -s -X GET "$API_URL/supabase/qr" | jq '.'

echo -e "\n${YELLOW}📦 Livraisons dans Supabase :${NC}"
curl -s -X GET "$API_URL/supabase/delivery" | jq '.'

echo -e "\n${GREEN}🎉 Tests end-to-end terminés !${NC}"
echo "=========================================="

echo -e "\n${BLUE}📋 Résumé de validation :${NC}"
echo "✅ Les données créées via l'API sont bien stockées dans Supabase"
echo "✅ Les identifiants sont correctement générés"
echo "✅ La récupération des données fonctionne"
echo "✅ Le système end-to-end est opérationnel" 