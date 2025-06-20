#!/bin/bash

echo "========================================"
echo "   LIVRAISONP2P - DEMARRAGE PRODUCTION"
echo "========================================"
echo

echo "[1/4] Configuration automatique..."
php setup-production.php
if [ $? -ne 0 ]; then
    echo "ERREUR: Configuration echouee"
    exit 1
fi

echo
echo "[2/4] Verification des services..."
php launch-production.php
if [ $? -ne 0 ]; then
    echo "ERREUR: Lancement des services echoue"
    exit 1
fi

echo
echo "[3/4] Demarrage du serveur web..."
php -S localhost:8000 -t . -d display_errors=0 &
SERVER_PID=$!

echo
echo "[4/4] Ouverture du navigateur..."
sleep 3
if command -v xdg-open &> /dev/null; then
    xdg-open http://localhost:8000
elif command -v open &> /dev/null; then
    open http://localhost:8000
else
    echo "Ouvrez manuellement: http://localhost:8000"
fi

echo
echo "========================================"
echo "    PRODUCTION DEMARREE AVEC SUCCES !"
echo "========================================"
echo
echo "URL: http://localhost:8000"
echo "Login: http://localhost:8000/auth/login.html"
echo "Register: http://localhost:8000/auth/register.html"
echo
echo "Appuyez sur Ctrl+C pour arreter le serveur..."

# Fonction de nettoyage
cleanup() {
    echo
    echo "Arret du serveur..."
    kill $SERVER_PID 2>/dev/null
    echo "Serveur arrete."
    exit 0
}

# Capturer Ctrl+C
trap cleanup SIGINT

# Attendre
wait $SERVER_PID 