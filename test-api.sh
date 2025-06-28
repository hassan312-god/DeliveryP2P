#!/bin/bash

echo "🧪 Tests de l'API DeliveryP2P"
echo "================================"

# Configuration
API_URL="http://localhost:8000"
# API_URL="https://deliveryp2p-api.onrender.com"  # Pour tester en production

# Couleurs pour les tests
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
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
        echo "Réponse: $response" | head -c 200
        echo "..."
    else
        echo -e "${RED}❌ Échec${NC}"
        echo "Erreur: $response"
    fi
}

# Tests
echo -e "\n${YELLOW}🚀 Démarrage des tests...${NC}"

# Test 1: Health Check
test_endpoint "GET" "/health" "" "Health Check"

# Test 2: Test QR Endpoint
test_endpoint "GET" "/qr" "" "Test QR Endpoint"

# Test 3: Génération QR simple
test_endpoint "POST" "/qr/generate" '{"data": "test123", "size": 200}' "Génération QR simple"

# Test 4: Génération QR avec URL
test_endpoint "POST" "/qr/generate" '{"data": "https://deliveryp2p.onrender.com", "size": 300}' "Génération QR avec URL"

# Test 5: Test de connexion
test_endpoint "GET" "/test-connection" "" "Test de connexion"

# Test 6: Test simple
test_endpoint "GET" "/test-simple.php" "" "Test simple"

echo -e "\n${GREEN}🎉 Tests terminés !${NC}"
echo "================================" 