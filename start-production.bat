@echo off
echo ========================================
echo    LIVRAISONP2P - DEMARRAGE PRODUCTION
echo ========================================
echo.

echo [1/4] Configuration automatique...
php setup-production.php
if %errorlevel% neq 0 (
    echo ERREUR: Configuration echouee
    pause
    exit /b 1
)

echo.
echo [2/4] Verification des services...
php launch-production.php
if %errorlevel% neq 0 (
    echo ERREUR: Lancement des services echoue
    pause
    exit /b 1
)

echo.
echo [3/4] Demarrage du serveur web...
start "LivraisonP2P Server" php -S localhost:8000 -t . -d display_errors=0

echo.
echo [4/4] Ouverture du navigateur...
timeout /t 3 /nobreak >nul
start http://localhost:8000

echo.
echo ========================================
echo    PRODUCTION DEMARREE AVEC SUCCES !
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