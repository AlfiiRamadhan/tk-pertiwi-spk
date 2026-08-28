#!/bin/sh
# start.sh — Railway startup script

echo "=== SPK TK Pertiwi — Startup ==="
echo "PORT  : ${PORT:-8080}"
echo "APP   : /app"

# Jalankan import database via PHP (lebih reliable dari mysqladmin)
echo ""
echo "--- Inisialisasi Database ---"
php /app/db_import.php

echo ""
echo "--- Menjalankan PHP Server ---"
exec php -S 0.0.0.0:${PORT:-8080} -t /app

