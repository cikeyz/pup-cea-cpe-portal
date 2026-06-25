# PUP CEA-CpE Portal

<p align="center">
  <strong>PHP and MySQL portal for enrollment, guests, login, and dashboard.</strong><br>
  Vanilla PHP stack for XAMPP and shared hosting.
</p>

<p align="center">
  <a href="https://pup-cea-cpe-portal.freedev.app/index.php">Live Demo</a>
  &nbsp;·&nbsp;
  <a href="#quick-start-xampp">Quick Start</a>
  &nbsp;·&nbsp;
  <a href="#project-structure">Structure</a>
  &nbsp;·&nbsp;
  <a href="#license">License</a>
</p>

<p align="center">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white">
  <img alt="Apache" src="https://img.shields.io/badge/Apache-XAMPP-D22128?logo=apache&logoColor=white">
  <img alt="HTML5" src="https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white">
  <img alt="CSS3" src="https://img.shields.io/badge/CSS3-1572B6?logo=css&logoColor=white">
  <img alt="JavaScript" src="https://img.shields.io/badge/JavaScript-F7DF1E?logo=javascript&logoColor=111111">
  <img alt="License MIT" src="https://img.shields.io/badge/License-MIT-22c55e?logo=open-source-initiative&logoColor=white">
</p>

## Contents

- [Overview](#overview)
- [Features](#features)
- [Live Demo](#live-demo)
- [Quick Start (XAMPP)](#quick-start-xampp)
- [Project Structure](#project-structure)
- [Database Config](#database-config)
- [Hosted Deployment](#hosted-deployment)
- [License](#license)
- [Course Note](#course-note)

## Overview

PUP CEA-CpE Portal is a small course-to-production style web app for the PUP College of Engineering and Architecture, Computer Engineering program. It covers student enrollment, guest registration, session login, password recovery, and a logged-in home page using plain PHP and MySQL.

## Features

| Feature | Description |
|---------|-------------|
| Student enrollment | Collects details and issues a student number |
| Guest registration | Separate guest accounts for limited access |
| Login | Session-based auth for students and guests |
| Forgot password | Reset via student number + email or username + email |
| Dashboard | Logged-in home view after auth |

## Live Demo

Hosted instance:

```text
https://pup-cea-cpe-portal.freedev.app/index.php
```

The hosted demo runs the same repository code against a real MySQL database.

## Quick Start (XAMPP)

```bash
cd C:\xampp\htdocs
git clone https://github.com/cikeyz/pup-cea-cpe-portal.git
```

1. Start **Apache** and **MySQL** in the XAMPP control panel.
2. Import the schema:

```bash
C:\xampp\mysql\bin\mysql.exe -u root < database.sql
```

3. Open:

```text
http://localhost/pup-cea-cpe-portal/index.php
```

Default XAMPP credentials work as-is (`localhost`, user `root`, empty password, database `wms_activity08`). For other setups, copy `.env.example` to `.env` and edit values.

### Quick test flow

1. Open the login page.
2. Enroll as a student (number format like `2026-02809-MN-0`).
3. Log in with the credentials you set.
4. Register a guest account and log in.
5. Exercise forgot password with matching identifiers.

## Project Structure

```text
pup-cea-cpe-portal/
├── index.php
├── enroll.php
├── register-guest.php
├── forgot-password.php
├── home.php
├── logout.php
├── db_connect.php
├── database.sql
├── database-hosted.sql
├── .env.example
├── LICENSE
├── README.md
├── assets/
│   ├── cpe-logo.png
│   ├── favicon.svg
│   ├── pup-cea.jpg
│   └── pup-logo.png
├── css/
│   └── style.css
├── js/
│   ├── auth.js
│   └── enroll.js
└── uploads/
    └── .gitkeep
```

## Database Config

`db_connect.php` reads optional `.env` values:

| Variable | Default |
|----------|---------|
| `DB_HOST` | `localhost` |
| `DB_PORT` | `3306` |
| `DB_USER` | `root` |
| `DB_PASS` | (empty) |
| `DB_NAME` | `wms_activity08` |

## Hosted Deployment

Use `database-hosted.sql` when the host already provides a database and you cannot run `CREATE DATABASE`. Point `.env` at host credentials and deploy the PHP tree over FTP or your host git deploy.

## License

MIT. See [LICENSE](LICENSE).

PUP names, logos, and related marks belong to the Polytechnic University of the Philippines. This project is for educational use.

## Course Note

Built for CMPE 364 (Web and Mobile Systems), Polytechnic University of the Philippines, under Engr. Arlene B. Canlas. Published here as a standalone project.
