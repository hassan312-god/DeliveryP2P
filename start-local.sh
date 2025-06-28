#!/bin/bash

echo "🚀 Démarrage de l'API DeliveryP2P en local..."

# Arrêter les serveurs existants
echo "📋 Arrêt des serveurs existants..."
pkill -f "php -S" 2>/dev/null

# Attendre un peu
sleep 2

# Aller dans le dossier api et démarrer le serveur
echo "🌐 Démarrage du serveur PHP sur localhost:8000..."
cd api
php -S localhost:8000 &

# Attendre que le serveur démarre
sleep 3

echo "✅ Serveur démarré !"
echo ""
echo "📱 Testez votre API :"
echo "   • Santé de l'API : http://localhost:8000/health-test.php"
echo "   • Test simple : http://localhost:8000/test-simple.php"
echo ""
echo "🔧 Pour arrêter le serveur : pkill -f 'php -S'"
echo ""

# Tester l'API
echo "🧪 Test automatique de l'API..."
curl -s http://localhost:8000/health-test.php | head -20

echo ""
echo "🎉 Prêt ! Votre API fonctionne en local." 