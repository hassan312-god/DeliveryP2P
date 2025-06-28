#!/bin/bash

echo "🧹 Nettoyage des anciens fichiers..."

# Vérifier si la migration a été effectuée
if [ ! -d "backend" ] || [ ! -d "frontend" ]; then
    echo "❌ Migration non effectuée. Exécutez d'abord ./migrate-to-render.sh"
    exit 1
fi

# Demander confirmation
echo "⚠️ Ce script va supprimer les anciens fichiers qui ne sont plus nécessaires."
read -p "Voulez-vous continuer ? (y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Nettoyage annulé"
    exit 1
fi

# Supprimer les anciens dossiers
echo "🗑️ Suppression des anciens dossiers..."

# Dossier php/ (si encore présent)
if [ -d "php" ]; then
    rm -rf php
    echo "✅ Dossier php/ supprimé"
fi

# Dossier public/ (si pas utilisé)
if [ -d "public" ] && [ ! -f "public/index.php" ]; then
    rm -rf public
    echo "✅ Dossier public/ supprimé"
fi

# Anciens fichiers de configuration
echo "🗑️ Suppression des anciens fichiers de configuration..."

# Anciens fichiers batch (Windows)
rm -f *.bat
echo "✅ Fichiers .bat supprimés"

# Anciens fichiers de configuration
rm -f production-config.json
echo "✅ production-config.json supprimé"

# Anciens scripts de démarrage
rm -f start-app.bat start-production.bat start-production.sh start.sh
echo "✅ Anciens scripts de démarrage supprimés"

# Anciens fichiers de documentation (garder les principaux)
rm -f HARMONISATION.md PRODUCTION.md
echo "✅ Anciens fichiers de documentation supprimés"

# Anciens fichiers SQL (déplacer vers backend si nécessaire)
if [ -d "database" ]; then
    echo "📁 Dossier database/ conservé (peut contenir des données importantes)"
fi

# Anciens logs
if [ -d "logs" ] && [ ! -d "backend/logs" ]; then
    rm -rf logs
    echo "✅ Ancien dossier logs/ supprimé"
fi

# Créer un fichier .gitignore mis à jour
echo "📝 Mise à jour du .gitignore..."
cat > .gitignore << 'EOF'
# Logs
*.log
logs/

# Environment variables
.env
.env.local
.env.production

# IDE
.vscode/
.idea/
*.swp
*.swo

# OS
.DS_Store
Thumbs.db

# Node modules (si utilisé plus tard)
node_modules/

# Backup files
*.backup

# Temporary files
*.tmp
*.temp

# Supabase
supabase/.env
supabase/.env.local

# Docker
.dockerignore
Dockerfile.backup
EOF

echo "✅ .gitignore mis à jour"

# Créer un README principal mis à jour
echo "📝 Mise à jour du README principal..."
cat > README.md << 'EOF'
# 🚚 DeliveryP2P

Application de livraison peer-to-peer avec authentification Supabase et interface moderne.

## 🚀 Déploiement Rapide

### Option 1 : Déploiement automatique
```bash
./deploy-to-render.sh
```

### Option 2 : Déploiement manuel
```bash
# 1. Migration
./migrate-to-render.sh

# 2. Mise à jour des URLs
./update-urls.sh

# 3. Test
./test-deployment.sh

# 4. Déploiement
./deploy-to-render.sh
```

## 📁 Structure

```
deliveryp2p/
├── backend/          # API PHP
├── frontend/         # Interface utilisateur
├── supabase/         # Configuration Supabase
└── scripts/          # Scripts de déploiement
```

## 🔧 Développement

### Local avec Supabase
```bash
./sync-supabase.sh
./start-dev.sh
```

### Local simple
```bash
cd frontend
python3 -m http.server 3000
```

## 📚 Documentation

- [Guide de déploiement](DEPLOYMENT.md)
- [Configuration Supabase](CORRECTION-SUPABASE.md)
- [Configuration Email](supabase-email-config.md)

## 🌐 URLs de Production

- **Application:** https://ton-app.onrender.com
- **API:** https://ton-app.onrender.com/backend
- **Admin:** https://ton-app.onrender.com/backend/admin-dashboard.php

## 🛠️ Technologies

- **Frontend:** HTML5, CSS3, JavaScript (Vanilla)
- **Backend:** PHP 8.2, Apache
- **Base de données:** Supabase (PostgreSQL)
- **Authentification:** Supabase Auth
- **Déploiement:** Render (Docker)

## 📞 Support

Pour toute question ou problème, consultez la documentation ou ouvrez une issue.
EOF

echo "✅ README principal mis à jour"

echo ""
echo "🎉 Nettoyage terminé !"
echo "📋 Votre projet est maintenant propre et organisé."
echo ""
echo "📁 Structure finale :"
echo "├── backend/          # API PHP"
echo "├── frontend/         # Interface utilisateur"
echo "├── supabase/         # Configuration Supabase"
echo "├── scripts/          # Scripts de déploiement"
echo "└── docs/             # Documentation"
echo ""
echo "🚀 Prêt pour le déploiement !" 