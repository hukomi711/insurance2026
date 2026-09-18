#!/bin/bash
cd /d/insurance2026
php artisan migrate
php artisan db:seed --class=PlansSeeder
