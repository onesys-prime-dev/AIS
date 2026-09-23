#!/bin/bash

set -Eeuo pipefail

# Resolves to wherever this script actually lives, not a hardcoded staging
# path — deploy.yml's "Run VPS deployment script" step already `cd`s into
# the right DEPLOY_PATH (staging's /home/onesys/htdocs/AIS, or testing's
# /home/onesystesting/htdocs/AIS-testing, each a different Linux user) before
# invoking `bash deploy.sh`, so re-deriving it here keeps this one script
# correct for both instead of always sending it back to staging's directory
# (which a different site-user has no permission to even cd into).
APP_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FACE_SERVICE="ais-face-service"
FACE_URL="http://127.0.0.1:8100"

echo "=========================================="
echo "AIS Deployment"
echo "=========================================="

cd "$APP_PATH"

echo "[1/6] Checking application..."

if [ ! -f "spark" ]; then
    echo "ERROR: CodeIgniter spark not found."
    exit 1
fi

if [ ! -f ".env" ]; then
    echo "ERROR: .env file not found."
    exit 1
fi

echo "Application files OK."

echo "[2/6] Database migrations..."

# Default namespace only (App). Deliberately NOT --all: the Shield/Settings
# package tables are not tracked in the migrations table on this server, so
# running their migrations would try to re-create tables that already exist.
echo "Migration status before:"
php spark migrate:status

if ! php spark migrate; then
    echo "ERROR: Database migration failed. Deployment aborted."
    exit 1
fi

echo "Database migrations: OK"

echo "[3/6] Clearing CodeIgniter cache..."

if ! php spark cache:clear; then
    echo "ERROR: Cache clear failed."
    exit 1
fi

echo "[4/6] Fixing writable permissions..."

if [ -d "writable" ]; then
    find writable -type d -exec chmod 775 {} \;
    find writable -type f -exec chmod 664 {} \;
fi

echo "[5/6] Restarting face recognition service..."

echo "Testing sudo permission..."

if ! sudo -n /usr/bin/systemctl is-active ais-face-service >/dev/null 2>&1; then
    echo "ERROR: onesys cannot access ais-face-service through sudo."
    sudo -n -l
    exit 1
fi

echo "Sudo permission: OK"

sudo -n /usr/bin/systemctl restart "$FACE_SERVICE"

sleep 2

# NOTE: the sudoers NOPASSWD rule on the VPS whitelists these exact command
# strings (no extra flags) — /usr/bin/systemctl {restart,status,is-active}
# ais-face-service. Adding flags like --quiet or --no-pager changes the
# invoked command signature and sudo silently falls back to requiring a
# password, which fails non-interactively. Redirect output via the shell
# instead of passing extra flags to systemctl.
if ! sudo -n /usr/bin/systemctl is-active "$FACE_SERVICE" >/dev/null; then
    echo "ERROR: Face recognition service failed to start."
    sudo -n /usr/bin/systemctl status "$FACE_SERVICE" | cat
    exit 1
fi

echo "Face recognition service is running."

echo "[6/6] Verifying deployment..."

echo "Application path:"
pwd

echo "PHP:"
php -v | head -n 1

echo "Face recognition API:"

# systemd reporting the unit "active" only means the process started — the
# actual FastAPI app can still be mid-startup (loading the YuNet/SFace
# models on first request) or crashed right after. Retry with backoff
# instead of a single immediate curl, which was failing here even though
# is-active reported healthy just above.
HEALTH_OK=0
for attempt in 1 2 3 4 5; do
    if curl --fail --silent --max-time 5 "$FACE_URL/health"; then
        HEALTH_OK=1
        break
    fi
    echo "Attempt $attempt/5 failed, retrying in 3s..."
    sleep 3
done

if [ "$HEALTH_OK" -ne 1 ]; then
    echo
    echo "ERROR: Face recognition API health check failed after 5 attempts."
    echo
    echo "--- systemctl status $FACE_SERVICE ---"
    sudo -n /usr/bin/systemctl status "$FACE_SERVICE" | cat
    echo
    echo "--- verbose curl to $FACE_URL/health ---"
    curl -v --max-time 5 "$FACE_URL/health" || true
    exit 1
fi

echo
echo "Face recognition API: OK"

echo "=========================================="
echo "AIS deployment completed successfully."
echo "=========================================="