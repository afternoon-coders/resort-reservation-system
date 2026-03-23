# Resort Reservation System

A PHP and MySQL web application for managing resort bookings, guest accounts, cottage availability, payments, and admin operations.

This project supports:
- Public resort website pages (home, rooms, amenities, gallery, contact)
- Guest registration and login
- Booking requests with email confirmation
- Admin dashboard and reservation management
- Seeder and utility scripts for local development

## Tech Stack

- PHP (procedural + model helpers)
- MySQL / MariaDB
- PHPMailer (SMTP confirmation emails)
- HTML, CSS, JavaScript

## Project Structure

Key directories:

- `auth/` Authentication, login, registration, booking confirmation
- `admin/` Admin dashboard pages and admin-side assets
- `views/` Public-facing page templates
- `helpers/` Database and model classes (Guest, User, Reservation, Room, Payment, Service)
- `api/` API endpoints (for example available rooms)
- `config/` App configuration (database environment loading)
- `database/` SQL schema
- `scripts/` CLI scripts for setup, seed data, and utilities
- `static/` Public assets (CSS, JS, icons, images)
- `logs/` Email fallback output and logs

## Prerequisites

Install the following before running locally:

- PHP 8.0+
- MySQL 8+ (or MariaDB equivalent)
- Web server stack such as XAMPP, WAMP, MAMP, or Apache + PHP
- `curl` and `tar` (needed by `scripts/setup_phpmailer.php`)

## Local Setup

1. Clone or copy this project into your web root.
2. Create the database and tables:
	 - Import `database/resort_reservation_db.sql` in phpMyAdmin, MySQL Workbench, or CLI.
3. Configure environment values in `.env`:
	 - `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`
	 - `SMTP_HOST`, `SMTP_USER`, `SMTP_PASS`, `SMTP_PORT`
4. Ensure PHPMailer exists (if missing):

```bash
php scripts/setup_phpmailer.php
```

5. Seed initial data (optional but recommended):

```bash
php scripts/seed_all.php
```

6. Create an admin account:

```bash
php scripts/create_admin.php <username> <first_name> <last_name> <email> <password>
```

7. Start your PHP/Apache server and open the app in your browser.

## Running with PHP Built-In Server (Optional)

If you want to run without Apache, from the project root:

```bash
php -S localhost:8000
```

Then visit:

- `http://localhost:8000/index.php` for the public site
- `http://localhost:8000/auth/login.php` for login
- `http://localhost:8000/admin/index.php` for admin area (after login)

## Environment Variables

Configured in `.env`:

- `APP_URL` Public URL used for app links
- `DB_HOST` Database host
- `DB_USER` Database user
- `DB_PASS` Database password
- `DB_NAME` Database name
- `SMTP_HOST` SMTP host (for example Gmail SMTP)
- `SMTP_USER` SMTP username/email
- `SMTP_PASS` SMTP password or app password
- `SMTP_PORT` SMTP port
- `MAIL_FROM_ADDRESS` Sender address
- `MAIL_FROM_NAME` Sender display name

## Common Scripts

Available in `scripts/`:

- `setup_phpmailer.php` Install/check PHPMailer in `vendor/`
- `seed_all.php` Run selected seeding scripts in order
- `seed_users.php` Seed sample users
- `seed_cottages.php` Seed cottages and related data
- `seed_guests.php` Seed guest records
- `seed_reservations.php` Seed reservations
- `seed_payments.php` Seed payment records
- `create_admin.php` Create an admin user from CLI
- `migrate_reservation_timestamps.php` Migration utility for reservation timestamps

## Authentication and Roles

- Users log in through `auth/login.php`
- Session stores user details and role
- Role-based redirect:
	- `admin` users go to `admin/index.php`
	- Other users go to `index.php`

## Email Confirmation Flow

- Booking confirmation emails are sent using PHPMailer in `helpers/Mailer.php`
- If email sending fails, HTML output is saved in `logs/` for debugging
- Confirm links point to `auth/confirm_booking.php` with a token

## Development Notes

- Database credentials and SMTP settings are loaded from `.env` via `helpers/env.php`
- `config/database.php` initializes MySQLi connection for legacy includes
- `helpers/DB.php` provides shared PDO access for model classes

## Troubleshooting

- Blank or failing DB operations:
	- Verify `.env` DB values and that MySQL service is running
- Email not sending:
	- Check SMTP host, credentials, port, and firewall restrictions
	- Inspect `logs/last_email_*.html` fallback files
- Missing PHPMailer classes:
	- Run `php scripts/setup_phpmailer.php`
- Permission issues for logs:
	- Ensure the app can write to the `logs/` directory

## Security Recommendations

- Never commit `.env` to source control
- Use strong DB and SMTP credentials
- Rotate SMTP app passwords if they were ever exposed
- Force HTTPS in production

## License

No license file is currently included. Add a `LICENSE` file if you plan to distribute this project.


