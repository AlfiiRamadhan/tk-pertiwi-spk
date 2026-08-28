#!/bin/sh
# ============================================================
# start.sh — Railway startup script
# Otomatis import database.sql jika tabel belum ada,
# lalu jalankan PHP built-in server.
# ============================================================

echo "=== SPK TK Pertiwi — Startup ==="

# Tunggu MySQL siap (max 30 detik)
echo "Menunggu koneksi database..."
MAX_TRIES=30
COUNT=0
until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "ERROR: Database tidak dapat dihubungi setelah ${MAX_TRIES} detik."
        exit 1
    fi
    echo "  Menunggu database... ($COUNT/$MAX_TRIES)"
    sleep 1
done
echo "✓ Database terhubung!"

# Cek apakah tabel 'users' sudah ada (indikator DB sudah diinisialisasi)
TABLE_EXISTS=$(mysql -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
    -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='users';" 2>/dev/null)

if [ "$TABLE_EXISTS" = "0" ] || [ -z "$TABLE_EXISTS" ]; then
    echo "Database kosong — mengimpor database.sql..."
    mysql -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /app/database.sql
    echo "✓ Database berhasil diimpor!"
else
    echo "✓ Database sudah ada — skip import."
fi

# Jalankan PHP built-in server
echo "Menjalankan PHP server di port $PORT..."
exec php -S 0.0.0.0:$PORT -t /app
