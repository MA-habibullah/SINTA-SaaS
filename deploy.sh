#!/bin/bash
# =============================================================
# deploy.sh — Script Deploy Otomatis SINTA-SaaS untuk VPS
# Cara pakai: bash deploy.sh
# =============================================================

# Pengecekan Keamanan: Pastikan script berjalan sebagai root
if [ "$EUID" -ne 0 ]; then
  echo "Meminta akses Administrator (sudo)..."
  exec sudo "$0" "$@"
fi

APP_DIR="/var/www/SINTA-SaaS"
DB_CONFIG="$APP_DIR/app/Config/Database.php"
BACKUP_FILE="/root/Database.php.backup"

echo "========================================"
echo "  SINTA-SaaS Deploy Script"
echo "========================================"

# LANGKAH 1: Backup Database.php jika ada
if [ -f "$DB_CONFIG" ]; then
    cp "$DB_CONFIG" "$BACKUP_FILE"
    echo "[OK] Database.php di-backup ke $BACKUP_FILE"
else
    echo "[WARN] Database.php tidak ditemukan, skip backup."
fi

# LANGKAH 2: Pull update terbaru dari GitHub
echo ""
echo "[INFO] Mengambil update dari GitHub..."
cd "$APP_DIR" || exit 1
git fetch origin
git reset --hard origin/main
echo "[OK] Code berhasil diupdate ke versi terbaru."

# LANGKAH 3: Restore Database.php dari backup
if [ -f "$BACKUP_FILE" ]; then
    mkdir -p "$APP_DIR/app/Config"
    cp "$BACKUP_FILE" "$DB_CONFIG"
    echo "[OK] Database.php berhasil di-restore."
else
    echo "[WARN] Backup Database.php tidak ada!"
    echo "       Buat manual: nano $DB_CONFIG"
fi

# LANGKAH 4: Set permission folder storage
if [ -d "$APP_DIR/storage" ]; then
    chmod -R 775 "$APP_DIR/storage"
    echo "[OK] Permission storage diset."
fi

# LANGKAH 5: Jalankan migrasi otomatis
echo ""
echo "[INFO] Menjalankan migrasi database..."
php "$APP_DIR/migrate.php"

echo ""
echo "========================================"
echo "  Deploy selesai!"
echo "========================================"
