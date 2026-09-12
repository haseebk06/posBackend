#!/usr/bin/env bash
echo "Caching config..."
php artisan config:cache

echo "Running migrations..."
php artisan migrate --force

echo "Seeding database (one-time)..."
php artisan db:seed --force
