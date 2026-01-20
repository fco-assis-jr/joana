@echo off
echo ========================================
echo  SISTEMA DE IMPORTACAO CSV - JOANA
echo ========================================
echo.
echo Iniciando servicos...
echo.

REM Start Queue Worker in a new window
start "Queue Worker" cmd /k "cd /d %~dp0 && php artisan queue:work --tries=3 --timeout=3600"

REM Wait a moment
timeout /t 2 /nobreak >nul

REM Start Laravel Server in a new window
start "Laravel Server" cmd /k "cd /d %~dp0 && php artisan serve"

echo.
echo ========================================
echo  SERVICOS INICIADOS COM SUCESSO!
echo ========================================
echo.
echo - Queue Worker: Rodando em segundo plano
echo - Laravel Server: http://localhost:8000
echo.
echo Pressione qualquer tecla para abrir o navegador...
pause >nul

REM Open browser
start http://localhost:8000

echo.
echo Sistema aberto no navegador!
echo.
echo Para parar os servicos, feche as janelas do:
echo - Queue Worker
echo - Laravel Server
echo.
pause
