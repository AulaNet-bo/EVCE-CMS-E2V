@echo off
cd /d "%~dp0"
echo ============================================
echo      STARTING NEW LARAVEL CMS (Steve)
echo ============================================
echo Directory: %CD%
echo Port: 8080
echo.
echo NOTE: If you see "Starting Standalone OCPP Server", you are running the WRONG file!
echo.
C:\php\php.exe artisan serve --port=8080
pause
