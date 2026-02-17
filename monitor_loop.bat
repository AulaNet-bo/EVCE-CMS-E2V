@echo off
:loop
C:\php\php.exe artisan steve:monitor-transactions
timeout /t 5 >nul
goto loop
