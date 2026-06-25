# PUP CEA-CpE Portal

Simple PHP and MySQL portal for the PUP College of Engineering and Architecture,
Computer Engineering program. Built as a coursework project for CMPE 364
(Web and Mobile Systems) using vanilla PHP, MySQL, HTML, CSS, and JavaScript.

It includes:
- student enrollment
- guest registration
- login
- forgot password
- logged-in dashboard

## Live demo

A live demo is hosted at:

```
<LIVE_DEMO_URL>
```

The hosted demo runs the exact same code in this repository against a real
MySQL database, so anyone can try the portal without installing anything. See
"Hosted deployment" below for how that instance is set up.

## Run it locally with XAMPP (recommended for grading)

This is a vanilla PHP/MySQL project, so it runs on a standard XAMPP stack with
no build step and no dependencies.

1. Clone the repo into your XAMPP web root:

   ```bash
   cd C:\xampp\htdocs
   git clone https://github.com/cikeyz/pup-cea-cpe-portal.git
   ```

2. Start **Apache** and **MySQL** from the XAMPP control panel.

3. Import the schema. This creates the `wms_activity08` database and its
   three tables:

   ```bash
   C:\xampp\mysql\bin\mysql.exe -u root < database.sql
   ```

   Or use phpMyAdmin: Import -> choose `database.sql` -> Go.

4. Open the portal in your browser:

   ```
   http://localhost/pup-cea-cpe-portal/index.php
   ```

That's it. The default XAMPP credentials (`localhost`, user `root`, no
password, database `wms_activity08`) work out of the box. If your MySQL uses
different values, copy `.env.example` to `.env` and adjust them (see
"Database config").

### Quick test flow

1. Open the login page.
2. Click "Enroll here" and submit the enrollment form -> a student number is
   issued in the `YYYY-#####-MN-#` format (e.g. `2026-02809-MN-0`).
3. Log in with the username and password you just set.
4. Click "Register as guest" to create a guest account and log in with it.
5. Use "Forgot password?" to reset by student number + email (students) or
   username + email (guests).

## Requirements

- PHP 8.1 or newer
- MySQL 8 or compatible MariaDB
- Apache or any PHP-capable web server (XAMPP provides all three)

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
  database.sql          # full schema: creates DB + tables (for local XAMPP)
  database-hosted.sql   # tables only (for shared hosting without CREATE DB)
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

## Database config

`db_connect.php` reads a local `.env` file if one exists. With a plain XAMPP
setup you do not need a `.env` file at all; the defaults just work.

Create a `.env` file from `.env.example` when you need custom credentials:

```env
DB_HOST=localhost
DB_PORT=3306
DB_USER=root
DB_PASS=
DB_NAME=wms_activity08
```

## Hosted deployment

The live demo runs on a free PHP/MySQL host so it stays online without anyone
needing to run a local server. The deployed site uses the same source files as
the local XAMPP run; only the database credentials differ (set via `.env`).

To set up your own hosted copy:

1. Create a free account on a PHP/MySQL host (e.g. InfinityFree, AlwaysData).
2. In the control panel, create a MySQL database and a database user. Note the
   host name, database name, user, and password.
3. Open phpMyAdmin for that database and import `database-hosted.sql` (tables
   only; shared hosts do not allow `CREATE DATABASE`).
4. Upload the project files to your `htdocs` (or `www`) folder. You can use
   the host's File Manager to upload `deploy.zip` and extract it in place, or
   use FTP.
5. In the uploaded folder, copy `.env.example` to `.env` and fill in the
   credentials from step 2:

   ```env
   DB_HOST=<your host, e.g. sqlXXX.infinityfree.com>
   DB_PORT=3306
   DB_USER=<your db user>
   DB_PASS=<your db password>
   DB_NAME=<your db name>
   ```

6. Open the URL the host gave you. The portal should load and the enrollment
   and login flows should work against the hosted database.

`deploy.zip` is a ready-to-upload bundle of the runtime files (it is built
locally and gitignored, so it is not in the repo). Build it yourself only if
you need to redeploy; the repo itself is the source of truth.

## Notes

- Student numbers are issued only after enrollment and follow the
  `YYYY-#####-MN-#` pattern (e.g. `2026-02809-MN-0`).
- Student resets use student number plus email.
- Guest resets use username plus email.
- Uploaded files are stored in `uploads/` (gitignored except for `.gitkeep`).
- For grading, the local XAMPP path above is the canonical run; the hosted
  link is just a convenience demo.
