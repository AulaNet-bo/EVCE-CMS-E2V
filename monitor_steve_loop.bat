@echo off
setlocal
echo ============================================
echo  STEVE Monitor Loop (every 2s)
echo ============================================
cd /d "C:\Users\Usuario\Desktop\Develop\STEVE+CMS"

:loop
C:\php\php.exe artisan steve:monitor-transactions
timeout /t 2 >nul
goto loop
