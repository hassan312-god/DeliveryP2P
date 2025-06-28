#!/bin/bash

echo "🚀 Déploiement complet vers Render..."

# Vérifier que nous sommes dans le bon répertoire
if [ ! -f "Dockerfile" ]; then
    echo "❌ Dockerfile non trouvé. Assurez-vous d'être dans le bon répertoire."
    exit 1
fi

# Vérifier la structure
echo "📁 Vérification de la structure..."
if [ ! -d "backend" ] || [ ! -d "frontend" ]; then
    echo "❌ Structure incorrecte. Exécutez d'abord ./migrate-to-render.sh"
    exit 1
fi

# Mettre à jour les URLs si nécessaire
echo "🔗 Vérification des URLs..."
if grep -q "deliveryp2p-backend.onrender.com" frontend/config.js; then
    echo "⚠️ URLs non mises à jour. Exécution de update-urls.sh..."
    ./update-urls.sh
fi

# Synchroniser avec Supabase si le script existe
if [ -f "sync-supabase.sh" ]; then
    echo "🔄 Synchronisation avec Supabase..."
    read -p "Voulez-vous synchroniser avec Supabase ? (y/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        ./sync-supabase.sh
    fi
fi

# Vérifier le statut Git
echo "📊 Vérification du statut Git..."
if ! git status --porcelain | grep -q .; then
    echo "✅ Aucun changement à commiter"
else
    echo "📝 Changements détectés, commit en cours..."
    git add .
    git commit -m "Deploy to Render - Restructured project for unified deployment"
fi

# Push vers GitHub
echo "📤 Push vers GitHub..."
if git push origin main; then
    echo "✅ Push réussi"
else
    echo "❌ Erreur lors du push"
    echo "💡 Essayez: git pull origin main puis git push origin main"
    exit 1
fi

# Créer un fichier de configuration Render
echo "⚙️ Création de la configuration Render..."
cat > render.yaml << 'EOF'
services:
  - type: web
    name: deliveryp2p
    env: docker
    plan: free
    buildCommand: ""
    startCommand: ""
    envVars:
      - key: SUPABASE_URL
        value: https://syamapjohtlbjlyhlhsi.supabase.co
      - key: SUPABASE_ANON_KEY
        sync: false
      - key: SUPABASE_SERVICE_KEY
        sync: false
      - key: JWT_SECRET
        sync: false
      - key: PASSWORD_SALT
        sync: false
      - key: SMTP_HOST
        sync: false
      - key: SMTP_PORT
        value: "587"
      - key: SMTP_USERNAME
        sync: false
      - key: SMTP_PASSWORD
        sync: false
      - key: SMTP_FROM_EMAIL
        sync: false
EOF

echo "✅ Configuration Render créée"

# Instructions pour le déploiement
echo ""
echo "🎉 Déploiement préparé avec succès !"
echo ""
echo "📋 Prochaines étapes sur Render :"
echo "1. Allez sur https://dashboard.render.com"
echo "2. Cliquez sur 'New Web Service'"
echo "3. Connectez votre repo GitHub"
echo "4. Configurez les variables d'environnement :"
echo "   - SUPABASE_ANON_KEY"
echo "   - SUPABASE_SERVICE_KEY"
echo "   - JWT_SECRET"
echo "   - PASSWORD_SALT"
echo "   - SMTP_HOST"
echo "   - SMTP_USERNAME"
echo "   - SMTP_PASSWORD"
echo "   - SMTP_FROM_EMAIL"
echo ""
echo "5. Déployez !"
echo ""
echo "🌐 URLs finales :"
echo "   - Application: https://ton-app.onrender.com"
echo "   - Backend API: https://ton-app.onrender.com/backend"
echo "   - Admin Dashboard: https://ton-app.onrender.com/backend/admin-dashboard.php"
echo ""
echo "✅ Déploiement terminé !" 