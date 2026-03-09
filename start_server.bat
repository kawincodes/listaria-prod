@echo off
echo Starting PHP Server...
start http://localhost:8000
php -c php.ini -S localhost:8000
pause