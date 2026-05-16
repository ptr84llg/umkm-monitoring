@echo off
echo ==========================================
echo Membersihkan cache Laravel SPMB SMA...
echo ==========================================

php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

pause