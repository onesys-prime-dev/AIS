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

echo "[2/6] Running database migrations..."

php spark migrate --all

echo "[3/6] Clearing CodeIgniter cache..."

php spark cache:clear

echo "[4/6] Fixing writable permissions..."

if [ -d "writable" ]; then
    find writable -type d -exec chmod 775 {} \;
    find writable -type f -exec chmod 664 {} \;
fi

echo "[5/6] Restarting face recognition service..."

if systemctl list-unit-files | grep -q "^${FACE_SERVICE}.service"; then

    sudo systemctl restart "$FACE_SERVICE"

    sleep 2

    if ! systemctl is-active --quiet "$FACE_SERVICE"; then
        echo "ERROR: Face recognition service failed to start."
        sudo systemctl status "$FACE_SERVICE" --no-pager
        exit 1
    fi

    echo "Face recognition service is running."

else

    echo "ERROR: ${FACE_SERVICE}.service not found."
    exit 1

fi

echo "[6/6] Verifying deployment..."

echo "Application path:"
pwd

echo "PHP:"
php -v | head -n 1

echo "Face recognition API:"
curl --fail --silent "$FACE_URL/health"

echo
echo "Face recognition API: OK"

echo "=========================================="
echo "AIS deployment completed successfully."
echo "=========================================="