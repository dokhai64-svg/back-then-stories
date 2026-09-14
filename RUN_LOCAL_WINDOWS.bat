@echo off
cd /d "%~dp0"
echo Opening Back Then Stories CMS at http://127.0.0.1:8000/admin
start "" http://127.0.0.1:8000/admin
php artisan serve --host=127.0.0.1 --port=8000
pause
