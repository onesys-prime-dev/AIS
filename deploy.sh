#!/bin/bash

set -Eeuo pipefail

APP_PATH="/home/onesys/htdocs/AIS"
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

echo "WARNING: Automatic migrations are temporarily disabled."
echo "Migration state must be reconciled before enabling migrations."

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

if ! curl --fail --silent "$FACE_URL/health"; then
    echo
    echo "ERROR: Face recognition API health check failed."
    exit 1
fi

echo
echo "Face recognition API: OK"

echo "=========================================="
echo "AIS deployment completed successfully."
echo "=========================================="