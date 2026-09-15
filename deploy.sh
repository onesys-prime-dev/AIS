#!/bin/bash

set -Eeuo pipefail

APP_PATH="/home/onesys/htdocs/AIS"

echo "=========================================="
echo "AIS Deployment"
echo "=========================================="

cd "$APP_PATH"

echo "[1/5] Checking application..."

if [ ! -f "spark" ]; then
    echo "ERROR: CodeIgniter spark not found."
    exit 1
fi

if [ ! -f ".env" ]; then
    echo "ERROR: .env file not found."
    exit 1
fi

echo "Application files OK."

echo "[2/5] Running database migrations..."

php spark migrate --all

echo "[3/5] Clearing CodeIgniter cache..."

php spark cache:clear

echo "[4/5] Fixing writable permissions..."

if [ -d "writable" ]; then
    find writable -type d -exec chmod 775 {} \;
    find writable -type f -exec chmod 664 {} \;
fi

echo "[5/5] Deployment verification..."

echo "Application path:"
pwd

echo "PHP:"
php -v | head -n 1

echo "=========================================="
echo "AIS deployment completed successfully."
echo "=========================================="