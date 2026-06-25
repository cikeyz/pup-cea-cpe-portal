-- =====================================================================
--   PUP CEA-CpE Portal - shared-hosting schema (tables only)
-- ---------------------------------------------------------------------
--   Use this file on shared PHP/MySQL hosts (InfinityFree, AlwaysData,
--   etc.) where your account has ONE pre-created database and you are
--   not allowed to run CREATE DATABASE / DROP DATABASE.
--
--   How to use:
--     1. In your hosting control panel, create a MySQL database (any
--        name) and a DB user, then note the host, name, user, password.
--     2. Open phpMyAdmin for that database.
--     3. Import this file. It creates the three portal tables inside
--        whatever database is currently selected.
--
--   For local XAMPP use database.sql instead (it also creates the DB).
-- =====================================================================

-- FK checks off while we drop/recreate so order never matters.
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS guests;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
--   users  -  the single login identity for every account type
--   account_type drives the dashboard avatar and the profile source.
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(50)  NOT NULL UNIQUE,
    email         VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    account_type  ENUM('student','guest') NOT NULL,
    profile_photo VARCHAR(255) DEFAULT NULL,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--   students  -  1:1 with users, holds the heavier enrollment data
--   student_number is issued by the system after enrollment succeeds
--   using the PUP-style format YYYY-#####-MN-#.
--   Student resets use the issued student_number + email.
--   Guest resets use username + email.
-- ---------------------------------------------------------------------
CREATE TABLE students (
    user_id         INT PRIMARY KEY,
    student_number  VARCHAR(20)  NOT NULL UNIQUE,
    first_name      VARCHAR(60)  NOT NULL,
    last_name       VARCHAR(60)  NOT NULL,
    dob             DATE         NOT NULL,
    gender          VARCHAR(30),
    phone           VARCHAR(20),
    address         VARCHAR(255),
    program_track   VARCHAR(60),
    year_level      VARCHAR(20),
    enrollment_type VARCHAR(30),
    school_docs     VARCHAR(255),
    short_bio       TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_students_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--   guests  -  1:1 with users, only needs the reason for the visit
-- ---------------------------------------------------------------------
CREATE TABLE guests (
    user_id         INT PRIMARY KEY,
    purpose_of_visit VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
