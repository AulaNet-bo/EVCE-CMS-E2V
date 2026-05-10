@echo off
setlocal
echo ===================================================
echo     EVCE CMS - Despliegue a Produccion (GCP)
echo ===================================================
echo.

cd /d "%~dp0"

echo [1/5] Comprimiendo archivos criticos (app, database, resources, routes, config, public)...
if exist deploy_build.zip del deploy_build.zip
tar -caf deploy_build.zip app database resources routes config public
if not exist deploy_build.zip (
    echo [ERROR] No se pudo crear el archivo de despliegue. Asegurate de ejecutar esto desde la carpeta steve-cms.
    pause
    exit /b
)
echo Archivo deploy_build.zip creado correctamente.
echo.

echo [2/5] Subiendo paquete al servidor de GCP...
echo (Esto puede tardar unos segundos, por favor espera...)
call gcloud compute scp deploy_build.zip evseserver:/home/Usuario/deploy_build.zip --zone us-central1-a --project active-display-484301-j7
if %errorlevel% neq 0 (
    echo [ERROR] Fallo al subir el archivo a GCP. Revisa tu conexion o credenciales de gcloud.
    pause
    exit /b
)
echo.

echo [3/5] Descomprimiendo archivos en el servidor remoto...
call gcloud compute ssh evseserver --zone us-central1-a --project active-display-484301-j7 --command="mkdir -p /home/Usuario/deploy_tmp && unzip -q -o /home/Usuario/deploy_build.zip -d /home/Usuario/deploy_tmp/"
echo.

echo [4/5] Inyectando codigo en el contenedor Docker y recargando (Optimize/Migrate)...
call gcloud compute ssh evseserver --zone us-central1-a --project active-display-484301-j7 --command="sudo docker cp /home/Usuario/deploy_tmp/app steve-cms-app:/app/ && sudo docker cp /home/Usuario/deploy_tmp/database steve-cms-app:/app/ && sudo docker cp /home/Usuario/deploy_tmp/resources steve-cms-app:/app/ && sudo docker cp /home/Usuario/deploy_tmp/routes steve-cms-app:/app/ && sudo docker cp /home/Usuario/deploy_tmp/config steve-cms-app:/app/ && sudo docker cp /home/Usuario/deploy_tmp/public steve-cms-app:/app/ && sudo docker exec steve-cms-app php artisan migrate --force && sudo docker exec steve-cms-app php artisan optimize:clear"
echo.

echo [5/5] Limpiando archivos temporales...
if exist deploy_build.zip del deploy_build.zip
call gcloud compute ssh evseserver --zone us-central1-a --project active-display-484301-j7 --command="rm -rf /home/Usuario/deploy_build.zip /home/Usuario/deploy_tmp"
echo.

echo ===================================================
echo   !DESPLIEGUE COMPLETADO EXITOSAMENTE!
echo ===================================================
pause
