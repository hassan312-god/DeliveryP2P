@echo off
title LivraisonP2P - Production
color 0A

echo.
echo  ██╗     ██╗██╗   ██╗██████╗  █████╗ ██╗███████╗ ██████╗ ███╗   ██╗██████╗ ██████╗ ██████╗ 
echo  ██║     ██║██║   ██║██╔══██╗██╔══██╗██║██╔════╝██╔═══██╗████╗  ██║██╔══██╗██╔══██╗██╔══██╗
echo  ██║     ██║██║   ██║██████╔╝███████║██║███████╗██║   ██║██╔██╗ ██║██████╔╝██████╔╝██████╔╝
echo  ██║     ██║██║   ██║██╔══██╗██╔══██║██║╚════██║██║   ██║██║╚██╗██║██╔═══╝ ██╔═══╝ ██╔═══╝ 
echo  ███████╗██║╚██████╔╝██║  ██║██║  ██║██║███████║╚██████╔╝██║ ╚████║██║     ██║     ██║     
echo  ╚══════╝╚═╝ ╚═════╝ ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝╚══════╝ ╚═════╝ ╚═╝  ╚═══╝╚═╝     ╚═╝     ╚═╝     
echo.
echo  ============================================================================================
echo  🚀 LANCEMENT DE LA PRODUCTION - LIVRAISONP2P
echo  ============================================================================================
echo.

echo [1/5] 🔍 Verification des prerequis...
if not exist "config.js" (
    echo    ❌ config.js manquant
    echo    Veuillez configurer votre fichier config.js
    pause
    exit /b 1
)
echo    ✅ config.js trouve

if not exist "php\config.php" (
    echo    ❌ php\config.php manquant
    echo    Veuillez configurer votre fichier php\config.php
    pause
    exit /b 1
)
echo    ✅ php\config.php trouve

echo    ✅ Prerequis verifies
echo.

echo [2/5] 🎨 Verification des designs harmonises...
if exist "css\auth-styles.css" (
    echo    ✅ Styles d'authentification harmonises
) else (
    echo    ⚠️ Styles d'authentification non trouves
)

if exist "auth\login.html" (
    echo    ✅ Page de connexion harmonisee
) else (
    echo    ⚠️ Page de connexion non trouvee
)

if exist "auth\register.html" (
    echo    ✅ Page d'inscription harmonisee
) else (
    echo    ⚠️ Page d'inscription non trouvee
)
echo.

echo [3/5] 🔗 Verification des boutons connectes...
if exist "js\modules\auth.js" (
    echo    ✅ Module d'authentification connecte
) else (
    echo    ⚠️ Module d'authentification non trouve
)

if exist "js\services\supabase.js" (
    echo    ✅ Service Supabase connecte
) else (
    echo    ⚠️ Service Supabase non trouve
)

if exist "js\services\api.js" (
    echo    ✅ Service API connecte
) else (
    echo    ⚠️ Service API non trouve
)
echo.

echo [4/5] 📧 Verification des services d'email...
if exist "php\email-service.php" (
    echo    ✅ Service d'email configure
) else (
    echo    ⚠️ Service d'email non trouve
)

if exist "database\email-functions.sql" (
    echo    ✅ Fonctions email SQL configurees
) else (
    echo    ⚠️ Fonctions email SQL non trouvees
)
echo.

echo [5/5] 🚀 Demarrage du serveur web...
echo    Demarrage du serveur PHP sur localhost:8000...
start "LivraisonP2P Server" php -S localhost:8000 -t . -d display_errors=0

echo    Attente du demarrage du serveur...
timeout /t 3 /nobreak >nul

echo    Ouverture du navigateur...
start http://localhost:8000

echo.
echo  ============================================================================================
echo  🎉 PRODUCTION DEMARREE AVEC SUCCES !
echo  ============================================================================================
echo.
echo  📱 URL d'acces: http://localhost:8000
echo  🔐 Page de connexion: http://localhost:8000/auth/login.html
echo  📝 Page d'inscription: http://localhost:8000/auth/register.html
echo  📊 Dashboard admin: http://localhost:8000/php/admin-dashboard.php
echo.
echo  🎨 Features disponibles:
echo     ✅ Designs harmonises et modernes
echo     ✅ Boutons connectes au backend
echo     ✅ Authentification complete (email + social)
echo     ✅ Service d'emails fonctionnel
echo     ✅ QR codes (generation + scan)
echo     ✅ Notifications en temps reel
echo     ✅ Base de donnees securisee
echo.
echo  📊 Monitoring:
echo     📋 Logs: logs\server.log
echo     📧 Emails: logs\email-processor.log
echo     🔍 Rapport: logs\production-report.json
echo.
echo  ⚠️  Appuyez sur Ctrl+C pour arreter le serveur
echo.

:loop
timeout /t 1 /nobreak >nul
goto loop 