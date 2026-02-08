#!/usr/bin/env bash
# ============================================
# Digilog BBS - Build Deployment Tarball
# ============================================
# Run locally to create digilogbbs-deploy.tar.gz
# Then copy the tarball to the VPS and run deploy.sh
# ============================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BUILD_DIR="/tmp/digilogbbs-build"
TARBALL="digilogbbs-deploy.tar.gz"
PACKAGE_NAME="digilogbbs"

info()  { echo -e "\033[1;34m[INFO]\033[0m $*"; }
ok()    { echo -e "\033[1;32m[OK]\033[0m $*"; }
fail()  { echo -e "\033[1;31m[FAIL]\033[0m $*"; exit 1; }

# Verify we're in the project directory
if [[ ! -f "${SCRIPT_DIR}/schema.sql" || ! -d "${SCRIPT_DIR}/src" || ! -d "${SCRIPT_DIR}/public" ]]; then
    fail "Run this script from the bbs-project root directory."
fi

info "Building deployment tarball..."

# Clean previous build
rm -rf "${BUILD_DIR}"
mkdir -p "${BUILD_DIR}/${PACKAGE_NAME}"

DEST="${BUILD_DIR}/${PACKAGE_NAME}"

# Copy application files
info "Copying application files..."
cp -a "${SCRIPT_DIR}/src" "${DEST}/"
cp -a "${SCRIPT_DIR}/public" "${DEST}/"
cp -a "${SCRIPT_DIR}/vendor" "${DEST}/"
cp -a "${SCRIPT_DIR}/node_modules" "${DEST}/"
cp -a "${SCRIPT_DIR}/door-proxy" "${DEST}/"

# Copy config and schema files
cp "${SCRIPT_DIR}/schema.sql" "${DEST}/"
cp "${SCRIPT_DIR}/seed.sql" "${DEST}/"
cp "${SCRIPT_DIR}/.env.example" "${DEST}/"
cp "${SCRIPT_DIR}/composer.json" "${DEST}/"
cp "${SCRIPT_DIR}/composer.lock" "${DEST}/"
cp "${SCRIPT_DIR}/package.json" "${DEST}/"
cp "${SCRIPT_DIR}/package-lock.json" "${DEST}/"

# Copy deploy script
cp "${SCRIPT_DIR}/deploy.sh" "${DEST}/"
chmod +x "${DEST}/deploy.sh"

# Build tarball
info "Creating tarball..."
tar czf "${SCRIPT_DIR}/${TARBALL}" -C "${BUILD_DIR}" "${PACKAGE_NAME}"

# Clean up build directory
rm -rf "${BUILD_DIR}"

# Show result
SIZE=$(du -h "${SCRIPT_DIR}/${TARBALL}" | cut -f1)

echo
ok "Deployment tarball created: ${TARBALL} (${SIZE})"
echo
echo "To deploy on the VPS:"
echo "  1. Copy to VPS:  scp ${TARBALL} root@your-vps:~/"
echo "  2. SSH to VPS:   ssh root@your-vps"
echo "  3. Extract:      tar xzf ${TARBALL}"
echo "  4. Deploy:       cd ${PACKAGE_NAME} && bash deploy.sh"
echo
