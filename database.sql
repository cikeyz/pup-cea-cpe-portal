-- =====================================================================
--   Activity-08 · Group 4 · Login & Registration System (PHP + MySQL)
-- ---------------------------------------------------------------------
--   Recreates the wms_activity08 database with three tables:
--     users    - shared login identity (username, email, password hash)
--     students - extra enrollment fields for enrolled PUP students
--     guests   - light record for non-enrolled (guest) visitors
--
--   Import via XAMPP:
--     C:\xampp\mysql\bin\mysql.exe -u root < database.sql
--   or use phpMyAdmin → Import → choose this file → Go.
-- =====================================================================

DROP DATABASE IF EXISTS wms_activity08;
CREATE DATABASE wms_activity08
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE wms_activity08;

-- ---------------------------------------------------------------------
--   users  ·  the single login identity for every account type
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
--   students  ·  1:1 with users, holds the heavier enrollment data
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
--   guests  ·  1:1 with users, only needs the reason for the visit
-- ---------------------------------------------------------------------
CREATE TABLE guests (
    user_id         INT PRIMARY KEY,
    purpose_of_visit VARCHAR(255) NOT NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guests_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
