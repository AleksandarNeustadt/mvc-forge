#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
RELEASE_DIR="${PROJECT_ROOT}/release"
PACKAGE_VERSION="$(node -p "require('${PROJECT_ROOT}/package.json').version")"
PACKAGE_NAME="mvc-forge-v${PACKAGE_VERSION}-$(date +%Y%m%d-%H%M%S)"
STAGING_DIR="$(mktemp -d)"

cleanup() {
  rm -rf "${STAGING_DIR}"
}
trap cleanup EXIT

mkdir -p "${RELEASE_DIR}"

cd "${PROJECT_ROOT}"

composer install --working-dir=app --no-dev --optimize-autoloader
npm ci
npm run build

rsync -a \
  --exclude '.git/' \
  --exclude '.github/' \
  --exclude '.idea/' \
  --exclude '.vscode/' \
  --exclude 'node_modules/' \
  --exclude 'release/' \
  --exclude 'logs/' \
  --exclude 'stats/' \
  --exclude 'document_errors/' \
  --exclude 'app/.env' \
  --exclude 'app/.env.*' \
  --include 'app/.env.example' \
  --exclude 'app/storage/cache/***' \
  --exclude 'app/storage/logs/***' \
  --exclude 'app/storage/rate_limits/***' \
  --exclude 'app/storage/uploads/***' \
  --exclude 'app/storage/avatars/***' \
  "${PROJECT_ROOT}/" "${STAGING_DIR}/${PACKAGE_NAME}/"

mkdir -p \
  "${STAGING_DIR}/${PACKAGE_NAME}/app/storage/cache/views" \
  "${STAGING_DIR}/${PACKAGE_NAME}/app/storage/logs" \
  "${STAGING_DIR}/${PACKAGE_NAME}/app/storage/rate_limits" \
  "${STAGING_DIR}/${PACKAGE_NAME}/app/storage/uploads/blog" \
  "${STAGING_DIR}/${PACKAGE_NAME}/app/storage/avatars"

printf "upload-ready release package\n" > "${STAGING_DIR}/${PACKAGE_NAME}/release-notes.txt"

(
  cd "${STAGING_DIR}"
  zip -qr "${RELEASE_DIR}/${PACKAGE_NAME}.zip" "${PACKAGE_NAME}"
)

echo "[OK] Release package created: ${RELEASE_DIR}/${PACKAGE_NAME}.zip"
