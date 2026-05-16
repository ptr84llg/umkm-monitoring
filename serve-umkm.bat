@echo off
echo ==========================================
echo Membersihkan cache Laravel SPMB SMA...
echo ==========================================

php artisan optimize:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo.
echo ==========================================
echo Menjalankan Laravel development server...
echo ==========================================

php artisan serve
pause