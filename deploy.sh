#!/usr/bin/env bash
# ============================================
# Digilog BBS - Debian 12 VPS Deployment Script
# ============================================
# Run as root on a fresh Debian 12 VPS:
#   tar xzf digilogbbs-deploy.tar.gz
#   cd digilogbbs
#   bash deploy.sh
# ============================================

set -euo pipefail

DB_NAME="bbsdb"
DB_USER="bbsuser"

# ============================================
# Helpers
# ============================================

info()  { echo -e "\n\033[1;34m[INFO]\033[0m $*"; }
ok()    { echo -e "\033[1;32m[OK]\033[0m $*"; }
warn()  { echo -e "\033[1;33m[WARN]\033[0m $*"; }
fail()  { echo -e "\033[1;31m[FAIL]\033[0m $*"; exit 1; }

prompt_secret() {
    local var_name="$1" prompt_text="$2" confirm="${3:-}"
    local val val2
    while true; do
        read -rsp "$prompt_text: " val
        echo
        if [[ -z "$val" ]]; then
            echo "  Value cannot be empty. Try again."
            continue
        fi
        if [[ "$confirm" == "confirm" ]]; then
            read -rsp "  Confirm: " val2
            echo
            if [[ "$val" != "$val2" ]]; then
                echo "  Values do not match. Try again."
                continue
            fi
        fi
        break
    done
    eval "$var_name=\$val"
}

# ============================================
# Pre-flight checks
# ============================================

if [[ $EUID -ne 0 ]]; then
    fail "This script must be run as root."
fi

if [[ ! -f "schema.sql" || ! -f "seed.sql" || ! -d "public" || ! -d "src" ]]; then
    fail "Run this script from inside the extracted deployment directory."
fi

source /etc/os-release 2>/dev/null || true
if [[ "${ID:-}" != "debian" ]] || [[ "${VERSION_ID:-}" != "12" ]]; then
    warn "This script is designed for Debian 12. Detected: ${PRETTY_NAME:-unknown}."
    read -rp "Continue anyway? [y/N]: " yn
    [[ "$yn" =~ ^[Yy]$ ]] || exit 1
fi

read -rp "Domain name (e.g. bbs.example.com): " DOMAIN
if [[ -z "$DOMAIN" ]]; then
    fail "Domain name is required."
fi

APP_DIR="/var/www/html/${DOMAIN}-app"
WEB_DIR="/var/www/html/${DOMAIN}"
VHOST_CONF="/etc/apache2/sites-available/${DOMAIN}.conf"

info "=== Digilog BBS Deployment for ${DOMAIN} ==="
echo
echo "This script will:"
echo "  1. Install Apache, PHP 8.2, MariaDB, Certbot"
echo "  2. Deploy the BBS application to ${APP_DIR}"
echo "  3. Configure Apache with HTTPS for ${DOMAIN}"
echo "  4. Set up the MariaDB database"
echo
echo "Make sure DNS for ${DOMAIN} (and www.${DOMAIN}) points to this server"
echo "before running this script if you want HTTPS to work."
echo
read -rp "Press Enter to continue or Ctrl+C to abort..."

# ============================================
# Gather credentials
# ============================================

info "Gathering configuration..."

prompt_secret DB_PASS "Enter a password for the BBS database user (${DB_USER})" confirm

read -rp "Admin email (for Certbot and SMTP 'from' address): " ADMIN_EMAIL
if [[ -z "$ADMIN_EMAIL" ]]; then
    fail "Admin email is required."
fi

read -rp "SMTP host [smtp.example.com]: " SMTP_HOST
SMTP_HOST="${SMTP_HOST:-smtp.example.com}"

read -rp "SMTP port [587]: " SMTP_PORT
SMTP_PORT="${SMTP_PORT:-587}"

read -rp "SMTP username [${ADMIN_EMAIL}]: " SMTP_USER
SMTP_USER="${SMTP_USER:-$ADMIN_EMAIL}"

prompt_secret SMTP_PASS "SMTP password"

echo
info "BBSLink Door Games Configuration"
read -rp "BBSLink system code: " BBSLINK_SYSCODE
read -rp "BBSLink auth code: " BBSLINK_AUTHCODE
read -rp "BBSLink scheme code: " BBSLINK_SCHEMECODE
DOOR_PROXY_SECRET=$(openssl rand -hex 32)

echo
info "Cloudflare Turnstile (anti-bot for registration)"
echo "  Get free keys at https://dash.cloudflare.com -> Turnstile"
echo "  Leave blank to skip (can be added to .env later)"
read -rp "Turnstile site key []: " TURNSTILE_SITE_KEY
TURNSTILE_SITE_KEY="${TURNSTILE_SITE_KEY:-}"
read -rp "Turnstile secret key []: " TURNSTILE_SECRET_KEY
TURNSTILE_SECRET_KEY="${TURNSTILE_SECRET_KEY:-}"

echo
info "Configuration summary:"
echo "  Domain:     ${DOMAIN}"
echo "  App dir:    ${APP_DIR}"
echo "  Web dir:    ${WEB_DIR} -> ${APP_DIR}/public"
echo "  DB name:    ${DB_NAME}"
echo "  DB user:    ${DB_USER}"
echo "  Admin email: ${ADMIN_EMAIL}"
echo "  SMTP host:  ${SMTP_HOST}:${SMTP_PORT}"
echo "  SMTP user:  ${SMTP_USER}"
echo "  BBSLink:    ${BBSLINK_SYSCODE}"
echo
read -rp "Proceed with these settings? [Y/n]: " yn
[[ -z "$yn" || "$yn" =~ ^[Yy]$ ]] || exit 1

# ============================================
# Step 1: Install system packages
# ============================================

info "Step 1/7: Installing system packages..."

export DEBIAN_FRONTEND=noninteractive

apt-get update -qq
apt-get upgrade -y -qq

apt-get install -y -qq \
    apache2 \
    libapache2-mod-php8.2 \
    php8.2 \
    php8.2-cli \
    php8.2-common \
    php8.2-mysql \
    php8.2-mbstring \
    php8.2-curl \
    php8.2-gd \
    php8.2-xml \
    php8.2-zip \
    php8.2-opcache \
    mariadb-server \
    certbot \
    python3-certbot-apache \
    unzip \
    curl

# Install Node.js (LTS) if not present
if ! command -v node &>/dev/null; then
    info "Installing Node.js LTS..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - 2>&1 | tail -1
    apt-get install -y -qq nodejs
fi
NODE_VER=$(node --version 2>/dev/null || echo "not found")
ok "System packages installed. Node.js: ${NODE_VER}"

# ============================================
# Step 2: Deploy application files
# ============================================

info "Step 2/7: Deploying application files..."

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Create app directory
mkdir -p "${APP_DIR}"

# Copy project files
cp -a "${SCRIPT_DIR}/src" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/public" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/vendor" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/node_modules" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/door-proxy" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/schema.sql" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/seed.sql" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/.env.example" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/composer.json" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/composer.lock" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/package.json" "${APP_DIR}/"
cp -a "${SCRIPT_DIR}/package-lock.json" "${APP_DIR}/"

# Create symlink: DocumentRoot -> public/
if [[ -e "${WEB_DIR}" ]]; then
    rm -f "${WEB_DIR}"  # Remove if it's an existing symlink
fi
ln -sf "${APP_DIR}/public" "${WEB_DIR}"

ok "Files deployed to ${APP_DIR}"
ok "Symlink: ${WEB_DIR} -> ${APP_DIR}/public"

# ============================================
# Step 3: Generate .env
# ============================================

info "Step 3/7: Generating .env configuration..."

cat > "${APP_DIR}/.env" <<ENVFILE
# Database Configuration
DB_HOST=localhost
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

# Site Configuration
SITE_NAME="Digilog"
SITE_URL=https://${DOMAIN}
ADMIN_EMAIL=${ADMIN_EMAIL}

# Email Configuration (SMTP)
SMTP_HOST=${SMTP_HOST}
SMTP_PORT=${SMTP_PORT}
SMTP_USER=${SMTP_USER}
SMTP_PASS=${SMTP_PASS}
SMTP_FROM=${SMTP_USER}
SMTP_FROM_NAME="Digilog"

# Security
SESSION_LIFETIME=86400
PASSWORD_MIN_LENGTH=8

# Cloudflare Turnstile (leave empty to disable)
TURNSTILE_SITE_KEY=${TURNSTILE_SITE_KEY}
TURNSTILE_SECRET_KEY=${TURNSTILE_SECRET_KEY}

# BBSLink Door Games
BBSLINK_SYSCODE=${BBSLINK_SYSCODE}
BBSLINK_AUTHCODE=${BBSLINK_AUTHCODE}
BBSLINK_SCHEMECODE=${BBSLINK_SCHEMECODE}

# Door Proxy (WebSocket-to-Telnet bridge)
DOOR_PROXY_PORT=7682
DOOR_PROXY_SECRET=${DOOR_PROXY_SECRET}
ENVFILE

ok ".env created at ${APP_DIR}/.env"

# ============================================
# Step 4: Set file permissions
# ============================================

info "Step 4/7: Setting file permissions..."

chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} \;
find "${APP_DIR}" -type f -exec chmod 644 {} \;
chmod 640 "${APP_DIR}/.env"

# Ensure door game temp directory is writable
if [[ -d "${APP_DIR}/public/assets/doors/temp" ]]; then
    chmod 775 "${APP_DIR}/public/assets/doors/temp"
fi

ok "Permissions set (owner: www-data, .env: 640)."

# Install door-proxy dependencies
if [[ -d "${APP_DIR}/door-proxy" ]]; then
    info "Installing door-proxy dependencies..."
    cd "${APP_DIR}/door-proxy" && npm install --production 2>&1 | tail -1
    cd "${SCRIPT_DIR}"

    # Install systemd service (patch WorkingDirectory for this domain)
    sed "s|YOURDOMAIN|${DOMAIN}|g" "${APP_DIR}/door-proxy/digilog-door-proxy.service" > /etc/systemd/system/digilog-door-proxy.service
    systemctl daemon-reload
    systemctl enable digilog-door-proxy
    systemctl restart digilog-door-proxy
    ok "Door proxy service installed and started."
fi

# ============================================
# Step 5: MariaDB setup
# ============================================

info "Step 5/7: Setting up MariaDB..."

# Start MariaDB if not running
systemctl enable mariadb
systemctl start mariadb

# Create database and user
mysql -u root <<DBSQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
DBSQL

ok "Database '${DB_NAME}' and user '${DB_USER}' created."

# Check if tables already exist (re-deploy safe)
TABLE_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")

if [[ "${TABLE_COUNT}" -gt 0 ]]; then
    ok "Database already has ${TABLE_COUNT} tables — skipping schema and seed import to preserve existing data."
else
    # Fresh install: import schema and seed data
    mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${APP_DIR}/schema.sql"
    ok "Schema imported."

    mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${APP_DIR}/seed.sql"
    ok "Seed data imported."

    TABLE_COUNT=$(mysql -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" -sN -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}';")
    ok "Database has ${TABLE_COUNT} tables."
fi

# ============================================
# Step 6: Apache configuration
# ============================================

info "Step 6/7: Configuring Apache..."

# Write vhost config
cat > "${VHOST_CONF}" <<VHOST
<VirtualHost *:80>
    ServerName ${DOMAIN}
    ServerAlias www.${DOMAIN}
    DocumentRoot ${WEB_DIR}

    <Directory ${WEB_DIR}>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # WebSocket proxy for door games
    <Location /ws/door/>
        ProxyPass ws://127.0.0.1:7682/
        ProxyPassReverse ws://127.0.0.1:7682/
    </Location>

    ErrorLog \${APACHE_LOG_DIR}/${DOMAIN}_error.log
    CustomLog \${APACHE_LOG_DIR}/${DOMAIN}_access.log combined
</VirtualHost>
VHOST

ok "Vhost config written to ${VHOST_CONF}"

# Enable required modules
a2enmod rewrite -q 2>/dev/null || true
a2enmod php8.2 -q 2>/dev/null || true
a2enmod proxy -q 2>/dev/null || true
a2enmod proxy_http -q 2>/dev/null || true
a2enmod proxy_wstunnel -q 2>/dev/null || true

# Enable site, disable default
a2ensite "${DOMAIN}.conf" -q 2>/dev/null || true
a2dissite 000-default.conf -q 2>/dev/null || true

# Test config and restart
apache2ctl configtest 2>&1
systemctl restart apache2

ok "Apache configured and restarted."

# Quick smoke test
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/ 2>/dev/null || echo "000")
if [[ "$HTTP_CODE" == "200" ]]; then
    ok "Apache serving pages (HTTP ${HTTP_CODE})."
else
    warn "Apache returned HTTP ${HTTP_CODE}. Check error logs if site doesn't load."
fi

# ============================================
# Step 7: Certbot / HTTPS
# ============================================

info "Step 7/7: Setting up HTTPS with Let's Encrypt..."

echo "Requesting TLS certificate for ${DOMAIN} and www.${DOMAIN}..."
echo "If this fails, ensure DNS A records point to this server's IP."
echo

if certbot --apache \
    -d "${DOMAIN}" \
    -d "www.${DOMAIN}" \
    --non-interactive \
    --agree-tos \
    -m "${ADMIN_EMAIL}" \
    --redirect 2>&1; then
    ok "HTTPS configured with automatic HTTP->HTTPS redirect."

    # Add WebSocket proxy to SSL vhost (Certbot creates a separate one)
    SSL_VHOST="/etc/apache2/sites-available/${DOMAIN}-le-ssl.conf"
    if [[ -f "$SSL_VHOST" ]] && ! grep -q "ws://127.0.0.1:7682" "$SSL_VHOST"; then
        sed -i '/<\/Directory>/a\
\
    # WebSocket proxy for door games\
    <Location /ws/door/>\
        ProxyPass ws://127.0.0.1:7682/\
        ProxyPassReverse ws://127.0.0.1:7682/\
    </Location>' "$SSL_VHOST"
        apache2ctl configtest 2>&1 && systemctl reload apache2
        ok "WebSocket proxy added to SSL vhost."
    fi

    # Verify auto-renewal
    if certbot renew --dry-run 2>&1 | grep -q "success"; then
        ok "Certificate auto-renewal verified."
    else
        warn "Auto-renewal dry-run had issues. Check 'certbot renew --dry-run' manually."
    fi
else
    warn "Certbot failed. The site will work over HTTP."
    warn "After DNS is configured, run:"
    warn "  certbot --apache -d ${DOMAIN} -d www.${DOMAIN} --redirect"
fi

# ============================================
# Done!
# ============================================

echo
echo "============================================"
echo "  Digilog BBS Deployment Complete!"
echo "============================================"
echo
echo "  URL:        https://${DOMAIN}"
echo "  App dir:    ${APP_DIR}"
echo "  Web root:   ${WEB_DIR} -> ${APP_DIR}/public"
echo "  Database:   ${DB_NAME} (user: ${DB_USER})"
echo "  Config:     ${APP_DIR}/.env"
echo "  Apache log: /var/log/apache2/${DOMAIN}_error.log"
echo
echo "  Default login:"
echo "    Username: admin"
echo "    Password: password"
echo
echo "  IMPORTANT: Change the admin password immediately!"
echo "    Login -> Change Password"
echo
echo "  Test users (all password 'password'):"
echo "    alice, bob, charlie"
echo
echo "============================================"
