@echo off
setlocal
cd /d "%~dp0"
echo ========================================
echo BACK THEN STORIES - LOCAL SETUP
echo ========================================
where php >nul 2>nul || (echo [ERROR] PHP not found. Install PHP 8.2+ or Laravel Herd.& pause & exit /b 1)
where composer >nul 2>nul || (echo [ERROR] Composer not found. Install Composer first.& pause & exit /b 1)
if not exist .env copy .env.example .env >nul
if not exist database\database.sqlite type nul > database\database.sqlite
echo Installing PHP packages...
call composer install || (echo Composer failed.& pause & exit /b 1)
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link
cls
echo Database ready.
echo Now create your admin account:
php artisan app:create-admin
echo.
echo Setup complete. Double-click RUN_LOCAL_WINDOWS.bat
pause
