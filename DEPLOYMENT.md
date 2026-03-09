# Deployment Guide — Resort Reservation System

## Prerequisites

- Ubuntu/Debian server with Apache2
- PHP 8.1+ with extensions: `pdo_mysql`, `mbstring`, `openssl`
- MySQL 8.0+ or MariaDB 10.5+
- (Optional) Certbot for SSL

## Step 1: Install Server Packages

```bash
sudo apt update
sudo apt install apache2 php php-mysql php-mbstring php-xml libapache2-mod-php mysql-server -y
```

## Step 2: Enable Required Apache Modules

```bash
sudo a2enmod rewrite headers deflate expires ssl
sudo systemctl restart apache2
```

## Step 3: Copy Project Files

```bash
# Copy project to the web root
sudo cp -r /path/to/resort-reservation-system /var/www/resort-reservation-system

# Set ownership
sudo chown -R www-data:www-data /var/www/resort-reservation-system

# Set permissions
sudo find /var/www/resort-reservation-system -type f -exec chmod 644 {} \;
sudo find /var/www/resort-reservation-system -type d -exec chmod 755 {} \;

# Make logs and tmp writable
sudo chmod 775 /var/www/resort-reservation-system/logs
sudo chmod 775 /var/www/resort-reservation-system/tmp
sudo chmod 775 /var/www/resort-reservation-system/output

# Protect .env
sudo chmod 640 /var/www/resort-reservation-system/.env
```

## Step 4: Configure Environment

```bash
cd /var/www/resort-reservation-system
sudo cp .env.example .env
sudo nano .env
```

Fill in your production values:
- `APP_URL` — your domain (e.g., `https://yourdomain.com`)
- `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` — your MySQL credentials
- `SMTP_*` — your SMTP email settings
- `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME` — sender info

## Step 5: Set Up the Database

```bash
# Log into MySQL
sudo mysql -u root -p

# Create database and user
CREATE DATABASE resort_reservation_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'resort_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL PRIVILEGES ON resort_reservation_db.* TO 'resort_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Import schema
sudo mysql -u resort_user -p resort_reservation_db < /var/www/resort-reservation-system/database/resort_reservation_db.sql
```

## Step 6: Create Admin User

```bash
cd /var/www/resort-reservation-system
sudo -u www-data php scripts/create_admin.php
```

## Step 7: Configure Apache VirtualHost

```bash
# Copy the provided config
sudo cp /var/www/resort-reservation-system/resort.conf /etc/apache2/sites-available/resort.conf

# Edit it with your domain
sudo nano /etc/apache2/sites-available/resort.conf

# Enable the site and disable the default
sudo a2ensite resort.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2
```

## Step 8: Set Up SSL (Recommended)

```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache -y

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Certbot will auto-configure Apache for SSL
```

After SSL is set up, uncomment these lines in `.htaccess`:
- HTTPS redirect rules
- `Strict-Transport-Security` header
- `session.cookie_secure` PHP setting

And update `APP_URL` in `.env` to use `https://`.

## Step 9: Set Up Cron Job

Reservation statuses (auto-cancel expired pending, auto-checkout) are updated via a cron job instead of on every page load:

```bash
# Edit crontab for www-data
sudo crontab -u www-data -e

# Add this line (runs every 10 minutes)
*/10 * * * * /usr/bin/php /var/www/resort-reservation-system/scripts/cron_update_statuses.php >> /var/www/resort-reservation-system/logs/cron.log 2>&1
```

## Step 10: PHP Production Settings

Edit your PHP configuration:

```bash
sudo nano /etc/php/$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')/apache2/php.ini
```

Set these values:
```ini
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
expose_php = Off
session.cookie_httponly = 1
session.cookie_secure = 1
session.cookie_samesite = Strict
session.use_strict_mode = 1
session.use_only_cookies = 1
max_execution_time = 30
memory_limit = 128M
upload_max_filesize = 10M
post_max_size = 10M
```

Then restart Apache:
```bash
sudo systemctl restart apache2
```

## Step 11: Verify Deployment

1. Visit `https://yourdomain.com` — the homepage should load
2. Visit `https://yourdomain.com/config/database.php` — should return **403 Forbidden**
3. Visit `https://yourdomain.com/helpers/` — should return **403 Forbidden**
4. Try the admin panel at `https://yourdomain.com/admin/`
5. Test the booking flow end-to-end

## Security Checklist

- [x] `.htaccess` blocks access to `config/`, `helpers/`, `scripts/`, `logs/`, `tmp/`, `database/`, `output/`, `vendor/`
- [x] `.env` is not accessible via web — test: `curl https://yourdomain.com/.env`
- [x] PHP `display_errors` is Off
- [x] Security headers are set (check with [securityheaders.com](https://securityheaders.com))
- [x] SSL certificate is valid
- [x] Database uses a dedicated user (not `root`)
- [x] Database password is strong
- [x] CSRF tokens on all forms
- [x] Session cookies are HttpOnly and Secure
- [ ] Set up regular database backups
- [ ] Set up log rotation for `logs/` directory
- [ ] Consider adding rate limiting (e.g., `mod_evasive` for Apache)

## Troubleshooting

**500 Internal Server Error:**
- Check Apache error log: `sudo tail -f /var/log/apache2/resort-error.log`
- Check PHP error log: `sudo tail -f /var/log/php_errors.log`
- Verify `AllowOverride All` is set in your VirtualHost

**403 Forbidden on homepage:**
- Check file ownership: `ls -la /var/www/resort-reservation-system/`
- Ensure `www-data` owns the files
- Ensure `mod_rewrite` is enabled: `sudo a2enmod rewrite`

**Database connection errors:**
- Verify `.env` credentials match your MySQL user
- Test: `mysql -u resort_user -p resort_reservation_db`

**Emails not sending:**
- Check `logs/` directory for email error logs
- For Gmail, ensure you're using an [App Password](https://support.google.com/accounts/answer/185833)
- Verify SMTP port 587 is not blocked by your server firewall
