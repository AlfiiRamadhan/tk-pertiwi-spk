#!/bin/sh
# ============================================================
# start.sh — Railway startup script
# Otomatis import database.sql jika tabel belum ada,
# lalu jalankan PHP built-in server.
# ============================================================

echo "=== SPK TK Pertiwi — Startup ==="
echo "PORT: ${PORT:-8080}"

# ── Auto-detect dari MYSQL_URL jika env vars individual tidak ada ──
if [ -z "$DB_HOST" ] && [ -n "$MYSQL_URL" ]; then
    echo "Mendeteksi MYSQL_URL, mengurai koneksi..."
    # Format: mysql://user:pass@host:port/dbname
    DB_USER=$(echo "$MYSQL_URL" | sed -E 's|mysql://([^:]+):.*|\1|')
    DB_PASS=$(echo "$MYSQL_URL" | sed -E 's|mysql://[^:]+:([^@]+)@.*|\1|')
    DB_HOST=$(echo "$MYSQL_URL" | sed -E 's|mysql://[^@]+@([^:/]+).*|\1|')
    DB_PORT=$(echo "$MYSQL_URL" | sed -E 's|mysql://[^@]+@[^:]+:([0-9]+).*|\1|')
    DB_NAME=$(echo "$MYSQL_URL" | sed -E 's|mysql://[^@]+@[^/]+/([^?]+).*|\1|')
    echo "  -> Host: $DB_HOST, Port: $DB_PORT, DB: $DB_NAME, User: $DB_USER"
fi

# ── Juga coba variabel Railway MySQL plugin default ──
DB_HOST="${DB_HOST:-${MYSQLHOST:-localhost}}"
DB_PORT="${DB_PORT:-${MYSQLPORT:-3306}}"
DB_NAME="${DB_NAME:-${MYSQLDATABASE:-db_tk_pertiwi}}"
DB_USER="${DB_USER:-${MYSQLUSER:-root}}"
DB_PASS="${DB_PASS:-${MYSQLPASSWORD:-}}"

echo ""
echo "=== Konfigurasi Database ==="
echo "  Host : $DB_HOST"
echo "  Port : $DB_PORT"
echo "  DB   : $DB_NAME"
echo "  User : $DB_USER"
echo "  Pass : $([ -n "$DB_PASS" ] && echo '(set)' || echo '(kosong)')"
echo ""

# ── Tunggu MySQL siap (max 60 detik) ──
echo "Menunggu koneksi database..."
MAX_TRIES=60
COUNT=0
DB_CONNECTED=0

until mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent --connect-timeout=3 2>/dev/null; do
    COUNT=$((COUNT + 1))
    if [ $COUNT -ge $MAX_TRIES ]; then
        echo "PERINGATAN: Database tidak dapat dihubungi setelah ${MAX_TRIES} detik."
        echo "  Pastikan env variables DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME sudah di-set di Railway!"
        echo "  PHP server tetap dijalankan, namun fitur DB tidak akan berfungsi."
        DB_CONNECTED=0
        break
    fi
    echo "  Menunggu database... ($COUNT/$MAX_TRIES) host=$DB_HOST:$DB_PORT"
    sleep 1
done

if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent --connect-timeout=3 2>/dev/null; then
    DB_CONNECTED=1
    echo "Database terhubung!"
fi

# ── Import database.sql jika DB terhubung dan tabel belum ada ──
if [ "$DB_CONNECTED" = "1" ]; then
    TABLE_EXISTS=$(mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -sse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME' AND table_name='users';" 2>/dev/null)

    if [ "$TABLE_EXISTS" = "0" ] || [ -z "$TABLE_EXISTS" ]; then
        echo "Database kosong — mengimpor database.sql..."
        if mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /app/database.sql 2>&1; then
            echo "Database berhasil diimpor!"
        else
            echo "Gagal import database.sql — cek log di atas untuk detail."
        fi
    else
        echo "Database sudah ada (tabel users ditemukan) — skip import."
    fi
fi

# ── Jalankan PHP built-in server ──
echo ""
echo "Menjalankan PHP server di port ${PORT:-8080}..."
exec php -S 0.0.0.0:${PORT:-8080} -t /app
