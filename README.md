# PUP CEA-CpE Portal

Simple PHP and MySQL portal for the PUP College of Engineering and Architecture, Computer Engineering program.

It includes:
- student enrollment
- guest registration
- login
- forgot password
- logged-in dashboard

## Live demo

A live demo is available at:

```
<LIVE_DEMO_URL>
```

The demo runs the same PHP/MySQL code in this repository. If the link is an
ephemeral tunnel, it stays up only while the host server is running; see the
notes at the bottom of this file.

## Requirements

- PHP 8.1 or newer
- MySQL 8 or compatible MariaDB
- Apache or any PHP-capable web server

## Project layout

```text
pup-cea-cpe-portal/
  assets/
    cpe-logo.png
    favicon.svg
    pup-cea.jpg
    pup-logo.png
  css/style.css
  db_connect.php
  database.sql
  enroll.php
  forgot-password.php
  home.php
  index.php
  js/
    auth.js
    enroll.js
  logout.php
  register-guest.php
  uploads/
```

## Features

- Database-backed login with `password_verify()`
- Student enrollment with uploaded photo and documents
- Guest registration with purpose of visit
- Student and guest password reset
- Dashboard with photo avatar for students and initials avatar for guests
- PUP-branded styling using the portal assets

## Local setup

1. Copy this folder to your web server root.
2. Start Apache and MySQL.
3. Import `database.sql` into MySQL.
4. Open the portal in your browser.

### XAMPP example

1. Put the folder in `C:\xampp\htdocs\pup-cea-cpe-portal`
2. Import the schema:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database.sql
```

3. Visit:

```text
http://localhost/pup-cea-cpe-portal/index.php
```

## Database config

`db_connect.php` reads a local `.env` file if one exists. You can also rely on the default XAMPP values.

Create a `.env` file from `.env.example` when you need custom credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=wms_activity08
```

## Workflow

1. Open the login page.
2. Enroll a student or register a guest.
3. Log in with the created account.
4. Use forgotten password recovery if needed.

## Notes

- Student numbers are issued after enrollment and follow the `YYYY-#####-MN-#` pattern.
- Student resets use student number plus email.
- Guest resets use username plus email.
- Uploaded files are stored in `uploads/`.
- When the live demo link is an ephemeral tunnel, it is only reachable while
  the local PHP server and the tunnel process are both running. For a permanent
  deployment, host this folder on any PHP/MySQL provider and update the link above.

