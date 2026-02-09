# Digilog BBS - Comprehensive Installation Guide

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Option A: Automated VPS Deployment (Recommended)](#option-a-automated-vps-deployment)
4. [Option B: Manual Installation](#option-b-manual-installation)
5. [Configuration Reference](#configuration-reference)
6. [Optional Services](#optional-services)
7. [Post-Installation](#post-installation)
8. [Troubleshooting](#troubleshooting)

---

## 1. Overview

Digilog BBS is a web-based bulletin board system built with:

| Component | Technology |
|-----------|------------|
| Backend | PHP 8.2 with Apache |
| Database | MariaDB 10.5+ (or MySQL 8.0+) |
| Door Games | Node.js 18+ WebSocket proxy + BBSLink |
| Email | PHPMailer over SMTP |
| Anti-Bot | Cloudflare Turnstile (optional) |
| HTTPS | Let's Encrypt via Certbot |

---

## 2. Prerequisites

### Hardware / VPS
- A Debian 12 VPS (or compatible Linux server)
- Minimum 512 MB RAM, 1 CPU core
- A registered domain name with DNS A records pointing to your server IP (both `yourdomain.com` and `www.yourdomain.com`)

### Software (installed automatically by deploy script, or manually)
- **PHP 8.2+** with extensions: `mysql`, `mbstring`, `curl`, `gd`, `xml`, `zip`, `opcache`
- **Apache 2** with modules: `rewrite`, `proxy`, `proxy_wstunnel`
- **MariaDB 10.5+** (or MySQL 8.0+)
- **Node.js 18+** (LTS)
- **Composer** (PHP dependency manager - only needed for local/manual installs)
- **Certbot** (for Let's Encrypt HTTPS)

### Accounts (optional but recommended)
- **SMTP provider** (e.g. Mailgun, SendGrid, AWS SES, or your own server) - for contact form emails
- **BBSLink account** (free at [bbslink.net](https://bbslink.net)) - for classic door games
- **Cloudflare Turnstile keys** (free at [Cloudflare Dashboard](https://dash.cloudflare.com) > Turnstile) - for anti-bot registration protection

---

## 3. Option A: Automated VPS Deployment (Recommended)

This is the fastest path. The included `deploy.sh` script fully provisions a Debian 12 server.

### Step 1: Install dependencies locally

On your **local machine**, ensure you have `composer` and `npm` installed.

```bash
git clone <repo-url> digilog-bbs
cd digilog-bbs

# Install PHP dependencies
composer install

# Install root-level Node dependencies
npm install

# Install door proxy dependencies
cd door-proxy && npm install && cd ..
```

### Step 2: Build the deployment tarball

```bash
bash package.sh
```

This creates `digilogbbs-deploy.tar.gz` containing all source code and pre-installed dependencies.

### Step 3: Transfer to VPS

```bash
scp digilogbbs-deploy.tar.gz root@your-vps:~/
```

### Step 4: Run the deployment script

```bash
ssh root@your-vps
tar xzf digilogbbs-deploy.tar.gz
cd digilogbbs
bash deploy.sh
```

The script will interactively prompt you for:

| Prompt | Example | Notes |
|--------|---------|-------|
| Domain name | `bbs.example.com` | DNS must already point to this server |
| Database password | (hidden input) | Confirmed twice; choose a strong password |
| Admin email | `you@example.com` | Used for Certbot and SMTP "from" |
| SMTP host | `smtp.mailgun.org` | Your email provider's SMTP server |
| SMTP port | `587` | Usually 587 (STARTTLS) or 465 (SSL) |
| SMTP username | `you@example.com` | Your SMTP login |
| SMTP password | (hidden input) | Your SMTP password |
| BBSLink system code | `ABC123` | From your BBSLink account (or leave blank) |
| BBSLink auth code | `DEF456` | From your BBSLink account (or leave blank) |
| BBSLink scheme code | `GHI789` | From your BBSLink account (or leave blank) |
| Turnstile site key | (optional) | Leave blank to disable CAPTCHA |
| Turnstile secret key | (optional) | Leave blank to disable CAPTCHA |

### What the script does (7 steps):

1. **Installs system packages** - Apache, PHP 8.2 + extensions, MariaDB, Certbot, Node.js LTS
2. **Deploys application files** to `/var/www/html/<domain>-app/` with a symlink from `/var/www/html/<domain>/` to the `public/` directory
3. **Generates `.env`** with all your configured values
4. **Sets file permissions** - `www-data` ownership, `.env` restricted to `640`
5. **Installs the door proxy** as a systemd service (`digilog-door-proxy.service`)
6. **Sets up MariaDB** - creates database `bbsdb`, user `bbsuser`, imports schema + seed data
7. **Configures Apache + HTTPS** - virtual host, WebSocket proxy for `/ws/door/`, Let's Encrypt certificate with auto-renewal

After completion, your BBS is live at `https://yourdomain.com`.

---

## 4. Option B: Manual Installation

For non-Debian systems, custom setups, or local development.

### Step 1: Install system packages

**Debian/Ubuntu:**
```bash
sudo apt update
sudo apt install -y \
    apache2 libapache2-mod-php8.2 \
    php8.2 php8.2-cli php8.2-common php8.2-mysql \
    php8.2-mbstring php8.2-curl php8.2-gd php8.2-xml \
    php8.2-zip php8.2-opcache \
    mariadb-server \
    curl unzip
```

Install Node.js 18+ (if not present):
```bash
curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo bash -
sudo apt install -y nodejs
```

Install Composer (if not present):
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Step 2: Clone and install dependencies

```bash
git clone <repo-url> /var/www/html/mysite-app
cd /var/www/html/mysite-app

# PHP dependencies
composer install

# Root Node dependencies (js-dos for door game assets)
npm install

# Door proxy dependencies
cd door-proxy && npm install && cd ..
```

### Step 3: Create the database

```bash
sudo mysql -u root <<SQL
CREATE DATABASE bbsdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'bbsuser'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON bbsdb.* TO 'bbsuser'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Import the schema and seed data:

```bash
mysql -u bbsuser -p bbsdb < schema.sql
mysql -u bbsuser -p bbsdb < seed.sql
```

The schema creates 7 tables: `users`, `sessions`, `subs`, `messages`, `read_pointers`, `contact_messages`, `registration_attempts`.

The seed data provides 10 message board subs, 4 test users, and 8 sample messages.

### Step 4: Configure environment

```bash
cp .env.example .env
```

Edit `.env` with your actual values (see [Configuration Reference](#5-configuration-reference) below). At minimum set:

```
DB_PASS=YOUR_STRONG_PASSWORD
SITE_URL=https://yourdomain.com
ADMIN_EMAIL=you@example.com
DOOR_PROXY_SECRET=<run: openssl rand -hex 32>
```

Secure the file:
```bash
sudo chown www-data:www-data .env
sudo chmod 640 .env
```

### Step 5: Set file permissions

```bash
sudo chown -R www-data:www-data /var/www/html/mysite-app
sudo find /var/www/html/mysite-app -type d -exec chmod 755 {} \;
sudo find /var/www/html/mysite-app -type f -exec chmod 644 {} \;
sudo chmod 640 /var/www/html/mysite-app/.env
```

### Step 6: Configure Apache

Create a symlink so the document root points to `public/`:

```bash
sudo ln -sf /var/www/html/mysite-app/public /var/www/html/mysite
```

Create a virtual host at `/etc/apache2/sites-available/mysite.conf`:

```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    DocumentRoot /var/www/html/mysite

    <Directory /var/www/html/mysite>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    # WebSocket proxy for door games
    <Location /ws/door/>
        ProxyPass ws://127.0.0.1:7682/
        ProxyPassReverse ws://127.0.0.1:7682/
    </Location>

    ErrorLog ${APACHE_LOG_DIR}/mysite_error.log
    CustomLog ${APACHE_LOG_DIR}/mysite_access.log combined
</VirtualHost>
```

Enable required modules and the site:

```bash
sudo a2enmod rewrite proxy proxy_http proxy_wstunnel php8.2
sudo a2ensite mysite.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl restart apache2
```

### Step 7: Set up HTTPS (production)

```bash
sudo apt install certbot python3-certbot-apache
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com --redirect
```

After Certbot runs, add the WebSocket proxy to the generated SSL vhost (`/etc/apache2/sites-available/yourdomain.com-le-ssl.conf`):

```apache
    # Add inside the <VirtualHost *:443> block:
    <Location /ws/door/>
        ProxyPass ws://127.0.0.1:7682/
        ProxyPassReverse ws://127.0.0.1:7682/
    </Location>
```

Then reload Apache:
```bash
sudo systemctl reload apache2
```

### Step 8: Start the door proxy

**For development/testing:**
```bash
node door-proxy/server.js
```

**For production (systemd service):**

Create `/etc/systemd/system/digilog-door-proxy.service`:

```ini
[Unit]
Description=Digilog Door Proxy (WebSocket-to-Telnet bridge)
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/html/mysite-app/door-proxy
ExecStart=/usr/bin/node server.js
Restart=on-failure
RestartSec=5
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable digilog-door-proxy
sudo systemctl start digilog-door-proxy
```

Verify it's running:
```bash
sudo systemctl status digilog-door-proxy
```

---

## 5. Configuration Reference

All configuration lives in the `.env` file in the project root (one level above `public/`).

### Required Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `DB_HOST` | `localhost` | MariaDB/MySQL host |
| `DB_NAME` | `bbsdb` | Database name |
| `DB_USER` | `bbsuser` | Database user |
| `DB_PASS` | *(none)* | Database password (**must set**) |
| `SITE_NAME` | `Digilog` | Displayed in header/title |
| `SITE_URL` | `http://bbs.local` | Full URL with scheme |
| `ADMIN_EMAIL` | `admin@localhost` | Shown on privacy page, used for Certbot |
| `DOOR_PROXY_PORT` | `7682` | WebSocket proxy listen port |
| `DOOR_PROXY_SECRET` | *(none)* | HMAC signing key (**must set** - generate with `openssl rand -hex 32`) |

### Security Settings

| Variable | Default | Description |
|----------|---------|-------------|
| `SESSION_LIFETIME` | `86400` | Session cookie lifetime in seconds (1 day) |
| `PASSWORD_MIN_LENGTH` | `8` | Minimum password length for registration |
| `REGISTRATION_RATE_LIMIT` | `3` | Max registration attempts per IP per hour |

### Email (SMTP) - Optional

| Variable | Description |
|----------|-------------|
| `SMTP_HOST` | SMTP server hostname |
| `SMTP_PORT` | SMTP port (typically `587`) |
| `SMTP_USER` | SMTP auth username |
| `SMTP_PASS` | SMTP auth password |
| `SMTP_FROM` | "From" email address |
| `SMTP_FROM_NAME` | "From" display name |

### Cloudflare Turnstile - Optional

| Variable | Description |
|----------|-------------|
| `TURNSTILE_SITE_KEY` | Public site key (leave empty to disable) |
| `TURNSTILE_SECRET_KEY` | Secret validation key |

### BBSLink Door Games - Optional

| Variable | Description |
|----------|-------------|
| `BBSLINK_SYSCODE` | Your BBSLink system code |
| `BBSLINK_AUTHCODE` | Your BBSLink auth code |
| `BBSLINK_SCHEMECODE` | Your BBSLink scheme code |

---

## 6. Optional Services

### BBSLink (Door Games)

1. Register at [bbslink.net](https://bbslink.net)
2. Add your system code, auth code, and scheme code to `.env`
3. Ensure the door proxy service is running
4. Ensure Apache proxies `/ws/door/` to `ws://127.0.0.1:7682/`
5. Users can access door games from the "Doors" menu in the BBS

### Cloudflare Turnstile (Anti-Bot)

1. Go to [Cloudflare Dashboard](https://dash.cloudflare.com) > Turnstile
2. Create a new widget for your domain
3. Add the site key and secret key to `.env`
4. The registration page will automatically show a CAPTCHA challenge

### SMTP Email

Required for the contact form to send notifications. Without it, contact form submissions are stored in the database but no email is sent.

---

## 7. Post-Installation

### First Login

1. Navigate to `https://yourdomain.com`
2. Log in with the default admin account:
   - **Username:** `admin`
   - **Password:** `password`
3. **Change the admin password immediately** via Change Password
4. Optionally edit the admin profile (bio, location)

### Test Users (from seed data)

| Username | Password | Role |
|----------|----------|------|
| `admin` | `password` | Admin |
| `alice` | `password` | User |
| `bob` | `password` | User |
| `charlie` | `password` | User |

Change or remove these for production use.

### Admin Panel

Accessible from the admin menu (visible to admin accounts):
- **User Management** - activate/deactivate users, edit profiles, reset passwords
- **Message Moderation** - soft-delete inappropriate messages
- **Contact Inbox** - view and manage contact form submissions

### Directory Layout (after deploy)

```
/var/www/html/
  <domain>/             -> symlink to <domain>-app/public
  <domain>-app/
    .env                 # Configuration (chmod 640)
    src/                 # PHP application code
      config.php         # DB connection, env loading, constants
      auth.php           # Authentication functions
      functions.php      # Utility helpers
      messages.php       # Message board logic
      email.php          # PHPMailer SMTP
      views/             # 22 page templates
    public/              # Web document root
      index.php          # Front controller / router
      .htaccess          # Apache rewrite rules
      assets/            # CSS, JS, fonts, images, door game files
    door-proxy/          # Node.js WebSocket-to-Telnet bridge
      server.js          # Proxy server
      cp437.js           # DOS character set converter
    vendor/              # Composer dependencies (PHPMailer)
    schema.sql           # Database schema
    seed.sql             # Sample data
```

---

## 8. Troubleshooting

### Site shows "Database connection failed"
- Verify `.env` has correct `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- Check MariaDB is running: `sudo systemctl status mariadb`
- Test connection: `mysql -u bbsuser -p bbsdb -e "SELECT 1;"`

### 404 or blank page
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite && sudo systemctl restart apache2`
- Verify `AllowOverride All` is set in the Apache vhost `<Directory>` block
- Check Apache error log: `/var/log/apache2/<domain>_error.log`

### Door games don't connect
- Check the door proxy service: `sudo systemctl status digilog-door-proxy`
- View proxy logs: `sudo journalctl -u digilog-door-proxy -f`
- Ensure `DOOR_PROXY_SECRET` is set in `.env` (generate with `openssl rand -hex 32`)
- Verify Apache WebSocket proxy is configured for both HTTP and HTTPS vhosts
- Confirm BBSLink credentials are correct in `.env`

### HTTPS/Certbot fails
- Ensure DNS A records for `yourdomain.com` and `www.yourdomain.com` point to your server
- Retry manually: `sudo certbot --apache -d yourdomain.com -d www.yourdomain.com --redirect`
- Check Certbot logs: `/var/log/letsencrypt/letsencrypt.log`

### Emails not sending
- Verify all `SMTP_*` variables in `.env`
- Check PHP error log for PHPMailer errors: `sudo tail -f /var/log/php*error*`
- Test SMTP connectivity: `openssl s_client -connect smtp.example.com:587 -starttls smtp`

### Permission errors
```bash
sudo chown -R www-data:www-data /var/www/html/<domain>-app
sudo chmod 640 /var/www/html/<domain>-app/.env
```

### View application logs
- **Apache access/error logs:** `/var/log/apache2/<domain>_access.log` and `_error.log`
- **PHP errors:** Typically `/var/log/php8.2-fpm.log` or Apache error log (when using mod_php)
- **Door proxy logs:** `sudo journalctl -u digilog-door-proxy`
- **MariaDB logs:** `sudo journalctl -u mariadb`
