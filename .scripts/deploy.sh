#!/bin/bash
set -e

echo "Deployment started ..."

cd /home1/micro1

# Enter maintenance mode
php artisan down || true

echo "Pull latest code"
git pull origin main --ff

echo "Install PHP dependencies"
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

echo "Install Node dependencies"
npm install

echo "Build Vite assets"
npm run build

echo "Run database migrations"
php artisan migrate --force

echo "Clear old cache"
php artisan optimize:clear

echo "Cache configs for performance"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Exit maintenance mode"
php artisan up

echo "Deployment finished!"
