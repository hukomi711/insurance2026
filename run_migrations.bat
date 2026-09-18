@echo off
cd /D "d:\insurance2026"
php artisan migrate
php artisan db:seed --class=PlansSeeder
echo Done!
pause
