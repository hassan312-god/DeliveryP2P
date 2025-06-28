#!/bin/bash

echo "🚀 Déploiement Frontend DeliveryP2P"
echo "==================================="

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
git commit -m "🔐 Ajout authentification Supabase - Inscription/Connexion fonctionnelles - $(date)"

# Push vers GitHub
echo "📤 Push vers GitHub..."
git push origin main

echo ""
echo "✅ Déploiement Frontend terminé !"
echo "==================================="
echo "🌐 URLs de votre application :"
echo "   - Frontend : https://deliveryp2p.onrender.com"
echo "   - API : https://deliveryp2p-api.onrender.com"
echo ""
echo "🔐 Pages d'authentification :"
echo "   - Inscription : https://deliveryp2p.onrender.com/auth/register.html"
echo "   - Connexion : https://deliveryp2p.onrender.com/auth/login.html"
echo ""
echo "🧪 Tests disponibles :"
echo "   - Test API : https://deliveryp2p-api.onrender.com/health"
echo "   - Test Supabase : https://deliveryp2p-api.onrender.com/supabase/test"
echo ""
echo "📋 Instructions de test :"
echo "1. Allez sur https://deliveryp2p.onrender.com/auth/register.html"
echo "2. Créez un compte avec vos informations"
echo "3. Vérifiez que le profil est créé dans Supabase"
echo "4. Connectez-vous avec les mêmes informations"
echo "5. Vérifiez la redirection vers le dashboard"
echo ""
echo "🎉 Votre système d'authentification est maintenant opérationnel !" 