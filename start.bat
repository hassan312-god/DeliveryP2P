@echo off
echo ========================================
echo    LIVRAISONP2P - DEMARRAGE
echo ========================================
echo.

echo [1/3] Verification de la configuration...
if not exist "config.js" (
    echo ERREUR: config.js manquant
    pause
    exit /b 1
)

echo [2/3] Demarrage du serveur web...
start "LivraisonP2P Server" php -S localhost:8000 -t . -d display_errors=0

echo [3/3] Ouverture du navigateur...
timeout /t 3 /nobreak >nul
start http://localhost:8000

echo.
echo ========================================
echo    APPLICATION DEMARREE !
echo ========================================
echo.
echo URL: http://localhost:8000
echo Login: http://localhost:8000/auth/login.html
echo Register: http://localhost:8000/auth/register.html
echo.
echo Appuyez sur une touche pour arreter le serveur...
pause >nul

echo.
echo Arret du serveur...
taskkill /f /im php.exe >nul 2>&1
echo Serveur arrete. 