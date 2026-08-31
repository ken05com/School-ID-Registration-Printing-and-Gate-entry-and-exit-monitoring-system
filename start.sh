#!/usr/bin/env bash
# ============================================================
# Start the School ID System (private MySQL + PHP dev server)
# ============================================================
set -e

LAMPP=/opt/lampp
PHP="$LAMPP/bin/php"
MYSQLD="$LAMPP/sbin/mysqld"
MYSQL="$LAMPP/bin/mysql"

APP_DIR="$(cd "$(dirname "$0")" && pwd)"
DATA_DIR="$APP_DIR/database/data"
SOCKET="$APP_DIR/database/mysql.sock"
MYSQL_PID="$APP_DIR/database/mysql.pid"
MYSQL_ERR="$APP_DIR/database/mysql.err"
PORT=3307
WEB_PORT=8000

DB_HOST="127.0.0.1"
DB_PORT="$PORT"

echo "==> Starting MySQL (private instance) ..."
"$MYSQLD" --no-defaults \
  --datadir="$DATA_DIR" \
  --socket="$SOCKET" \
  --port="$PORT" \
  --bind-address=127.0.0.1 \
  --user="$(whoami)" \
  --pid-file="$MYSQL_PID" \
  --log-error="$MYSQL_ERR" &
MYSQL_PROC=$!
echo "    MySQL PID: $MYSQL_PROC"

# Wait for MySQL to be ready
for i in {1..30}; do
  if "$MYSQL" --no-defaults --socket="$SOCKET" -u school -pschool123 -e "SELECT 1" >/dev/null 2>&1; then
    echo "    MySQL is ready."
    break
  fi
  sleep 1
done

echo "==> Starting PHP web server at http://localhost:$WEB_PORT"
echo "    Press Ctrl+C to stop."
SCHOOL_DB_HOST="$DB_HOST" SCHOOL_DB_PORT="$PORT" \
SCHOOL_DB_NAME=school_id_system SCHOOL_DB_USER=school SCHOOL_DB_PASS=school123 \
"$PHP" -S "0.0.0.0:$WEB_PORT" -t "$APP_DIR/public"
