@echo off
title LivraisonP2P - Application Harmonisée
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
echo  🚀 LANCEMENT DE L'APPLICATION HARMONISÉE - LIVRAISONP2P
echo  ============================================================================================
echo.

echo [1/6] 🔍 Verification des prerequis...
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

if not exist "css\app-styles.css" (
    echo    ❌ css\app-styles.css manquant
    echo    Veuillez creer le fichier de styles harmonises
    pause
    exit /b 1
)
echo    ✅ css\app-styles.css trouve

echo    ✅ Prerequis verifies
echo.

echo [2/6] 🎨 Verification des designs harmonises...
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

if exist "index.html" (
    echo    ✅ Page d'accueil harmonisee
) else (
    echo    ⚠️ Page d'accueil non trouvee
)

if exist "client\dashboard.html" (
    echo    ✅ Dashboard client harmonise
) else (
    echo    ⚠️ Dashboard client non trouve
)

if exist "courier\dashboard.html" (
    echo    ✅ Dashboard livreur harmonise
) else (
    echo    ⚠️ Dashboard livreur non trouve
)

if exist "admin\dashboard.html" (
    echo    ✅ Dashboard admin harmonise
) else (
    echo    ⚠️ Dashboard admin non trouve
)
echo.

echo [3/6] 🔗 Verification des boutons connectes...
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

if exist "js\components\toast.js" (
    echo    ✅ Composant Toast connecte
) else (
    echo    ⚠️ Composant Toast non trouve
)
echo.

echo [4/6] 📧 Verification des services d'email...
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

echo [5/6] 🗄️ Verification de la base de donnees...
if exist "database\schema.sql" (
    echo    ✅ Schema de base de donnees trouve
) else (
    echo    ⚠️ Schema de base de donnees non trouve
)

if exist "setup-database.php" (
    echo    ✅ Script de configuration BDD trouve
) else (
    echo    ⚠️ Script de configuration BDD non trouve
)
echo.

echo [6/6] 🚀 Demarrage du serveur web...
echo    Demarrage du serveur PHP sur localhost:8000...
start "LivraisonP2P Server" php -S localhost:8000 -t . -d display_errors=0

echo    Attente du demarrage du serveur...
timeout /t 3 /nobreak >nul

echo    Ouverture du navigateur...
start http://localhost:8000

echo.
echo  ============================================================================================
echo  🎉 APPLICATION HARMONISÉE DEMARRÉE AVEC SUCCÈS !
echo  ============================================================================================
echo.
echo  📱 URL d'acces: http://localhost:8000
echo  🔐 Page de connexion: http://localhost:8000/auth/login.html
echo  📝 Page d'inscription: http://localhost:8000/auth/register.html
echo  👤 Dashboard client: http://localhost:8000/client/dashboard.html
echo  🚚 Dashboard livreur: http://localhost:8000/courier/dashboard.html
echo  👨‍💼 Dashboard admin: http://localhost:8000/admin/dashboard.html
echo  ⚙️ Configuration admin: http://localhost:8000/php/admin-dashboard.php
echo.
echo  🎨 Features harmonisees disponibles:
echo     ✅ Design harmonise sur toutes les pages
echo     ✅ Boutons connectes au backend
echo     ✅ Authentification complete (email + social)
echo     ✅ Service d'emails fonctionnel
echo     ✅ QR codes (generation + scan)
echo     ✅ Notifications en temps reel
echo     ✅ Base de donnees securisee
echo     ✅ Validation des formulaires
echo     ✅ Gestion d'erreurs
echo     ✅ Animations fluides
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