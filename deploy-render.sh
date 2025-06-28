#!/bin/bash

echo "🚀 Déploiement DeliveryP2P sur Render"
echo "======================================"

# Vérifier que git est configuré
if ! git config --get user.name > /dev/null 2>&1; then
    echo "❌ Git n'est pas configuré. Configurons-le..."
    git config --global user.name "DeliveryP2P"
    git config --global user.email "deploy@deliveryp2p.com"
fi

# Ajouter tous les fichiers
echo "📁 Ajout des fichiers..."
git add .

# Commit des changements
echo "💾 Commit des changements..."
git commit -m "🔧 Mise à jour Supabase - Table profiles + nouveaux endpoints - $(date)"

# Push vers GitHub
echo "📤 Push vers GitHub..."
git push origin main

echo ""
echo "✅ Déploiement terminé !"
echo "======================================"
echo "🌐 URLs de votre application :"
echo "   - Frontend : https://deliveryp2p.onrender.com"
echo "   - API : https://deliveryp2p-api.onrender.com"
echo ""
echo "🧪 Tests disponibles :"
echo "   - Test de santé : https://deliveryp2p-api.onrender.com/health"
echo "   - Test Supabase : https://deliveryp2p-api.onrender.com/supabase/test"
echo "   - Test QR : https://deliveryp2p-api.onrender.com/qr"
echo ""
echo "📋 Prochaines étapes :"
echo "1. Configurez Supabase avec vos vraies clés API"
echo "2. Exécutez le script SQL dans Supabase"
echo "3. Testez les endpoints avec de vraies données"
echo ""
echo "🎉 Votre application est prête pour la production !" 