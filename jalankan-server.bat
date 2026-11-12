@echo off
title Server Inventaris PC Kantor
echo ========================================================
echo   Menjalankan Server Inventaris PC Kantor (Laravel)
echo ========================================================
echo.
echo Akses Web Lokal  : http://localhost:8000/pcs
echo Akses LAN WiFi   : http://192.168.10.88:8000/pcs
echo Akses Radmin VPN : http://26.7.11.4:8000/pcs
echo.
start http://localhost:8000/pcs
php artisan serve --host=0.0.0.0 --port=8000
pause

