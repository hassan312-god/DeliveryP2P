#!/bin/bash

echo "🚀 Déploiement de l'API DeliveryP2P..."

# Vérifier que git est configuré
if ! git config --get user.name > /dev/null 2>&1; then
    echo "❌ Git n'est pas configuré. Configurons-le..."
    git config --global user.name "DeliveryP2P"
    git config --global user.email "deploy@deliveryp2p.com"
fi

# Ajouter tous les fichiers
git add .

# Commit des changements
git commit -m "🔧 Mise à jour API QR - $(date)"

# Push vers GitHub
echo "📤 Push vers GitHub..."
git push origin main

echo "✅ Déploiement terminé !"
echo "🌐 API disponible sur: https://deliveryp2p.onrender.com"
echo "📱 Endpoints disponibles:"
echo "   - GET /health"
echo "   - GET /qr"
echo "   - POST /qr/generate" 