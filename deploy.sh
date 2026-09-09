#!/bin/bash
# ==============================================================================
# deploy.sh — Script Deployment Otomatis SINTA-SaaS (Laravel 11 Modular + Vite)
# Cara pakai: bash deploy.sh
# ==============================================================================

set -e

echo "======================================================================"
echo "  🚀 MEMULAI DEPLOYMENT SINTA-SAAS (LARAVEL 11 + VUE 3 + POSTGRESQL 16)"
echo "======================================================================"

# 1. Mode Pemeliharaan (Maintenance Mode)
echo "🔒 [1/7] Mengaktifkan mode pemeliharaan aplikasi..."
php artisan down --retry=60 || true

# 2. Mengambil Pembaruan Kode dari Git
echo "📥 [2/7] Mengambil pembaruan kode terbaru dari Git main..."
git fetch origin main
git reset --hard origin/main

# 3. Instalasi & Optimasi Dependensi Composer
echo "📦 [3/7] Menginstal dependensi Composer (Production)..."
composer install --no-dev --optimize-autoloader --no-interaction

# 4. Migrasi Skema Database PostgreSQL 16
echo "🗄️ [4/7] Menjalankan migrasi database PostgreSQL multi-schema..."
php artisan migrate --force

# 5. Kompilasi Aset Frontend Vite & Tailwind CSS
echo "⚡ [5/7] Mengompilasi frontend aset Vite (Vue 3 / Tailwind CSS)..."
npm ci --silent
npm run build

# 6. Pembersihan & Pemanasan Cache Laravel
echo "🧹 [6/7] Mengoptimasi konfigurasi, routing, dan view cache..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 7. Mengaktifkan Kembali Aplikasi (Live)
echo "🔓 [7/7] Menonaktifkan mode pemeliharaan (Aplikasi Live)..."
php artisan up

echo "======================================================================"
echo "  ✅ DEPLOYMENT BERHASIL! APLIKASI SINTA-SAAS SIAP DIGUNAKAN."
echo "======================================================================"
