# Byte Bandits QA

Quality Assurance Management System built with plain PHP, MySQL, Bootstrap, jQuery, and a small Composer dependency for PHPMailer.

## Requirements

- PHP 8.0 or newer. PHP 8.2 is recommended because the Dockerfile uses `php:8.2-apache`.
- Apache or another PHP-capable web server.
- MySQL 8.x or a compatible MariaDB/MySQL server.
- Composer.
- PHP extensions:
  - `mysqli`
  - `curl`
  - `openssl`
  - `ctype`
  - `filter`
  - `hash`
  - `mbstring` recommended for email/international text handling
- Internet access for CDN assets used by the frontend:
  - Bootstrap
  - Font Awesome
  - jQuery
  - Chart.js
  - jsPDF and jsPDF AutoTable
  - SheetJS/XLSX
  - QRCode.js
  - Google Fonts
  - hCaptcha script on the forgot-password page

## Project Structure

```text
backend/
  api/              PHP API endpoints
  config/           Database, API-key, and mailer config
  data/             Sample external integration data
  schema/qa-db.sql  MySQL database dump
frontend/
  assets/           CSS, JS, and image assets
  pages/            PHP pages
  partials/         Shared header/sidebar
index.php           Root entry point; redirects to login or dashboard
composer.json       PHP dependency manifest
Dockerfile          Optional Apache/PHP container build
```

## Local Setup With XAMPP

1. Place the project in XAMPP's `htdocs` directory:

   ```text
   C:\xampp\htdocs\byte-bandits-qa
   ```

2. Start Apache and MySQL from the XAMPP Control Panel.

3. Install PHP dependencies from the project root:

   ```powershell
   composer install
   ```

   This installs `phpmailer/phpmailer` into `vendor/`.

4. Create the database and import the schema:

   ```powershell
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS qa_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   mysql -u root -p qa_system < backend/schema/qa-db.sql
   ```

   You can also import `backend/schema/qa-db.sql` through phpMyAdmin.

5. Configure environment variables. The code uses `getenv()`, so values must be available to Apache/PHP as environment variables. A plain `backend/.env` file is not automatically loaded by the current app.

   Required variables:

   ```env
   APP_API_KEY=generate-a-long-random-key
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=qa_system
   DB_PORT=3306
   DB_CHARSET=utf8mb4
   ```

   Generate an API key with:

   ```powershell
   php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
   ```

   In XAMPP, one simple option is to add `SetEnv` lines to Apache config, then restart Apache:

   ```apache
   SetEnv APP_API_KEY "your-generated-key"
   SetEnv DB_HOST "localhost"
   SetEnv DB_USER "root"
   SetEnv DB_PASS ""
   SetEnv DB_NAME "qa_system"
   SetEnv DB_PORT "3306"
   SetEnv DB_CHARSET "utf8mb4"
   ```

6. Open the app:

   ```text
   http://localhost/byte-bandits-qa/
   ```

   The root `index.php` redirects unauthenticated users to:

   ```text
   http://localhost/byte-bandits-qa/frontend/pages/login.php
   ```

## Optional Environment Variables

These are only needed for specific features.

Email and password reset:

```env
SENDGRID_API_KEY=your-sendgrid-api-key
SENDGRID_FROM_EMAIL=no-reply@example.com
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME=QA System
MAIL_REPLY_TO=support@example.com
HCAPTCHA_SITE_KEY=your-hcaptcha-site-key
HCAPTCHA_SECRET=your-hcaptcha-secret
```

Legacy Gmail/PHPMailer config:

```env
MAIL_USERNAME=your-gmail-address
MAIL_PASSWORD=your-gmail-app-password
```

ImageKit PDF uploads:

```env
IMAGEKIT_PRIVATE_KEY=private_xxxxxxxxxxxx
IMAGEKIT_URL_ENDPOINT=https://ik.imagekit.io/your-account
```

LMS KPI import:

```env
LMS_API_URL=https://example.com/api
LMS_API_KEY=your-lms-api-key
```

## Login User

The SQL dump includes a seeded admin account with username:

```text
admin.qa.app
```

If you do not know the password, generate a new bcrypt hash:

```powershell
php -r "echo password_hash('ChangeThisPassword123!', PASSWORD_BCRYPT), PHP_EOL;"
```

Then update the seeded user in MySQL:

```sql
UPDATE qa_users
SET password_hash = 'paste-generated-hash-here'
WHERE username = 'admin.qa.app';
```

## Docker Option

Build the image:

```powershell
docker build -t byte-bandits-qa .
```

Run it against an existing MySQL database:

```powershell
docker run --rm -p 8080:80 `
  -e APP_API_KEY="your-generated-key" `
  -e DB_HOST="host.docker.internal" `
  -e DB_USER="root" `
  -e DB_PASS="" `
  -e DB_NAME="qa_system" `
  -e DB_PORT="3306" `
  -e DB_CHARSET="utf8mb4" `
  byte-bandits-qa
```

Then open:

```text
http://localhost:8080/
```

The Dockerfile only builds the PHP/Apache app container. It does not start a MySQL container, so you still need a reachable MySQL server and imported schema.

## Troubleshooting

- `Server misconfiguration. Contact the administrator.` usually means `APP_API_KEY` is missing from Apache/PHP environment variables.
- `Database connection failed` means the `DB_*` variables are missing or MySQL is not reachable.
- If the SQL import fails near `CREATE DATABASE IF NOT EXISTS "qa_system"`, create the database manually and remove or adjust that first line to use backticks/no quotes.
- If the SQL import fails on `GTID_PURGED` or binary-log statements, comment out those dump lines and import again.
- If API requests return `401 Unauthorized`, confirm the page's `<meta name="x-api-key">` has the same value that `backend/config/api_auth.php` expects from `APP_API_KEY`.
- If email, upload, or LMS features fail, check the optional third-party environment variables for that feature.

## Security Notes

- Do not commit real `.env` files or API credentials. This repo's `.gitignore` already excludes `.env`, `backend/.env`, and `*.env`.
- If any real credentials were committed or shared, rotate them before deploying.
- Keep `vendor/` generated by `composer install` rather than editing files inside it.
