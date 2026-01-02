#!/bin/bash

# 🚀 soapy bubbles Backend Performance Optimization Script
# This script optimizes the Laravel backend for better performance

echo "🚀 Starting Backend Performance Optimization..."

# 1. Clear all caches
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear

# 2. Clear compiled files
echo "🗑️ Clearing compiled files..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

# 3. Optimize for production
echo " Optimizing for production..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 4. Generate optimized autoloader
echo "📦 Generating optimized autoloader..."
composer dump-autoload --optimize

# 5. Set proper permissions
echo "🔐 Setting proper permissions..."
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/

# 6. Create storage symlink if not exists
echo "🔗 Creating storage symlink..."
php artisan storage:link

# 7. Run database optimizations
echo "🗄️ Optimizing database..."
php artisan migrate --force
php artisan db:seed --force

# 8. Clear and rebuild caches
echo "🔄 Rebuilding caches..."
php artisan optimize
php artisan optimize:clear
php artisan optimize

echo "✅ Backend Performance Optimization Complete!"
echo "🎉 Your backend should now be significantly faster!"
echo "📊 Monitor performance and adjust settings as needed."
