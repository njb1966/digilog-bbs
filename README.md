# Digilog BBS

A web-based bulletin board system inspired by classic dial-up BBS culture. Built with PHP 8.2 and MariaDB.

## Features

- **Message Boards** - Chronological, threaded discussions across multiple subs
- **Door Games** - Play classic BBS door games via BBSLink (TradeWars, LORD, etc.) through an in-browser terminal
- **User Profiles** - Bio, location, post count, activity tracking
- **Admin Panel** - User management, message moderation, contact inbox
- **Anti-Bot Registration** - Honeypot field, Cloudflare Turnstile, IP rate limiting
- **New Message Tracking** - "New since last visit" read pointers per sub
- **Privacy-First** - No analytics, no tracking scripts, no gamification
- **Dark Theme** - Clean, monospace-focused design

## Requirements

- PHP 8.2+ with extensions: mysql, mbstring, curl, gd, xml, zip
- MariaDB 10.5+ (or MySQL 8.0+)
- Apache with mod_rewrite
- Node.js 18+ (for the door game WebSocket proxy)
- Composer (PHP dependency manager)

## Quick Start (Local Development)

```bash
# Clone the repo
git clone https://github.com/yourusername/digilog-bbs.git
cd digilog-bbs

# Install PHP dependencies
composer install

# Install Node.js dependencies (for door game proxy)
cd door-proxy && npm install && cd ..

# Copy and configure environment
cp .env.example .env
# Edit .env with your database credentials and settings

# Create the database and import schema
mysql -u root -p -e "CREATE DATABASE bbsdb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p bbsdb < schema.sql
mysql -u root -p bbsdb < seed.sql

# Point your web server's document root to the public/ directory
# For Apache, configure a vhost or symlink
```

## VPS Deployment (Debian 12)

A full provisioning script is included for fresh Debian 12 servers:

```bash
# Build the deployment tarball locally
bash package.sh

# Copy to your VPS
scp digilogbbs-deploy.tar.gz root@your-vps:~/

# On the VPS
ssh root@your-vps
tar xzf digilogbbs-deploy.tar.gz
cd digilogbbs
bash deploy.sh
```

The deploy script will interactively prompt for your domain, database password, SMTP credentials, BBSLink codes, and Turnstile keys. It handles Apache, PHP, MariaDB, Node.js, and Let's Encrypt certificate setup.

## Configuration

All configuration is done through the `.env` file. See `.env.example` for all available options.

Key settings:

| Variable | Description |
|----------|-------------|
| `SITE_NAME` | Your BBS name (displayed in header and title) |
| `SITE_URL` | Full URL of your site |
| `ADMIN_EMAIL` | Contact email (shown on privacy page) |
| `TURNSTILE_SITE_KEY` | Cloudflare Turnstile site key (optional, leave empty to disable) |
| `TURNSTILE_SECRET_KEY` | Cloudflare Turnstile secret key |
| `BBSLINK_SYSCODE` | Your BBSLink system code (for door games) |

## Default Login

After running `seed.sql`, the default admin account is:

- **Username:** `admin`
- **Password:** `password`

**Change this immediately after first login.**

Test users `alice`, `bob`, and `charlie` are also created (all with password `password`).

## Door Games

Door games are powered by [BBSLink](https://bbslink.net). To enable them:

1. Register for a free BBSLink account
2. Add your system code, auth code, and scheme code to `.env`
3. Start the door proxy: `node door-proxy/server.js`
4. Configure Apache to proxy WebSocket connections from `/ws/door/` to `ws://127.0.0.1:7682/`

## Project Structure

```
public/          # Web root (document root points here)
  index.php      # Front controller / router
  assets/        # CSS, images, fonts
src/
  config.php     # Environment loading, DB connection, constants
  auth.php       # Authentication functions
  functions.php  # Helper functions
  messages.php   # Message board functions
  email.php      # Email sending (PHPMailer)
  views/         # Page templates
door-proxy/      # Node.js WebSocket-to-Telnet bridge for door games
schema.sql       # Database schema
seed.sql         # Sample data
deploy.sh        # VPS deployment script
package.sh       # Build deployment tarball
```

## License

[MIT](LICENSE)
