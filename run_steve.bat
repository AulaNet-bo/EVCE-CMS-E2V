@echo off
cd /d "%~dp0"
call ..\set_env.bat
cd ..\Steve-Java
echo Starting Steve (Java) on Port 8081...
java -Dspring.profiles.active=dev -Dhttp.port=8081 -jar target/steve.war
pause
