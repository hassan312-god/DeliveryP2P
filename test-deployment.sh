#!/bin/bash

echo "🧪 Test de la configuration de déploiement..."

# Vérifier la structure
echo "📁 Test de la structure..."
errors=0

if [ ! -d "backend" ]; then
    echo "❌ Dossier backend/ manquant"
    errors=$((errors + 1))
else
    echo "✅ Dossier backend/ présent"
fi

if [ ! -d "frontend" ]; then
    echo "❌ Dossier frontend/ manquant"
    errors=$((errors + 1))
else
    echo "✅ Dossier frontend/ présent"
fi

# Vérifier les fichiers essentiels
echo "📄 Test des fichiers essentiels..."

if [ ! -f "Dockerfile" ]; then
    echo "❌ Dockerfile manquant"
    errors=$((errors + 1))
else
    echo "✅ Dockerfile présent"
fi

if [ ! -f "frontend/index.html" ]; then
    echo "❌ frontend/index.html manquant"
    errors=$((errors + 1))
else
    echo "✅ frontend/index.html présent"
fi

if [ ! -f "backend/config.php" ]; then
    echo "❌ backend/config.php manquant"
    errors=$((errors + 1))
else
    echo "✅ backend/config.php présent"
fi

# Vérifier les URLs dans le frontend
echo "🔗 Test des URLs..."
if grep -q "deliveryp2p-backend.onrender.com" frontend/config.js; then
    echo "❌ URLs non mises à jour dans config.js"
    errors=$((errors + 1))
else
    echo "✅ URLs mises à jour dans config.js"
fi

if [ -f "frontend/js/services/api.js" ] && grep -q "deliveryp2p-backend.onrender.com" frontend/js/services/api.js; then
    echo "❌ URLs non mises à jour dans api.js"
    errors=$((errors + 1))
else
    echo "✅ URLs mises à jour dans api.js"
fi

# Vérifier le .htaccess
if [ ! -f "frontend/.htaccess" ]; then
    echo "❌ frontend/.htaccess manquant"
    errors=$((errors + 1))
else
    echo "✅ frontend/.htaccess présent"
fi

# Vérifier le .dockerignore
if [ ! -f ".dockerignore" ]; then
    echo "❌ .dockerignore manquant"
    errors=$((errors + 1))
else
    echo "✅ .dockerignore présent"
fi

# Test de la configuration Docker
echo "🐳 Test de la configuration Docker..."
if docker --version &> /dev/null; then
    echo "✅ Docker installé"
    
    # Test de build (optionnel)
    read -p "Voulez-vous tester le build Docker ? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "🔨 Test de build Docker..."
        if docker build -t deliveryp2p-test .; then
            echo "✅ Build Docker réussi"
        else
            echo "❌ Erreur lors du build Docker"
            errors=$((errors + 1))
        fi
    fi
else
    echo "⚠️ Docker non installé (optionnel pour le test)"
fi

# Résumé
echo ""
echo "📊 Résumé des tests :"
if [ $errors -eq 0 ]; then
    echo "🎉 Tous les tests sont passés !"
    echo "✅ Votre projet est prêt pour le déploiement sur Render"
else
    echo "❌ $errors erreur(s) détectée(s)"
    echo "⚠️ Corrigez les erreurs avant le déploiement"
fi

echo ""
echo "📋 Prochaines étapes :"
if [ $errors -eq 0 ]; then
    echo "1. Exécuter ./deploy-to-render.sh"
    echo "2. Configurer Render avec vos variables d'environnement"
    echo "3. Déployer !"
else
    echo "1. Corriger les erreurs listées ci-dessus"
    echo "2. Relancer ce test"
    echo "3. Puis procéder au déploiement"
fi 