-- ============================================================
-- Toilet Cleanliness Monitoring System - Database Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS toilet_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE toilet_monitor;

-- ------------------------------------------------------------
-- users: both Admin and Student/User accounts live here
-- ------------------------------------------------------------
CREATE TABLE users (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    username              VARCHAR(50)  NOT NULL UNIQUE,
    password_hash         VARCHAR(255) NOT NULL,
    full_name             VARCHAR(100) NOT NULL,
    email                 VARCHAR(150) DEFAULT NULL,
    role                  ENUM('admin','student') NOT NULL DEFAULT 'student',
    must_change_password  TINYINT(1) NOT NULL DEFAULT 1,
    status                ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- toilets: master list of toilets/washrooms on campus
-- ------------------------------------------------------------
CREATE TABLE toilets (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(20)  NOT NULL UNIQUE,   -- e.g. T01
    name        VARCHAR(100) NOT NULL,          -- e.g. Block A - Level 2 - Male
    location    VARCHAR(150) DEFAULT NULL,
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- user_toilets: many-to-many assignment between users & toilets
-- ------------------------------------------------------------
CREATE TABLE user_toilets (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    toilet_id    INT NOT NULL,
    assigned_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_toilet (user_id, toilet_id),
    CONSTRAINT fk_ut_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    CONSTRAINT fk_ut_toilet FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- toilet_sessions: one row per check-in/check-out visit
-- ------------------------------------------------------------
CREATE TABLE toilet_sessions (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    toilet_id         INT NOT NULL,
    user_id           INT NOT NULL,
    checkin_time      DATETIME NOT NULL,
    checkin_comment   TEXT DEFAULT NULL,
    checkout_time     DATETIME DEFAULT NULL,
    checkout_comment  TEXT DEFAULT NULL,
    status            ENUM('active','completed') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ts_toilet FOREIGN KEY (toilet_id) REFERENCES toilets(id) ON DELETE CASCADE,
    CONSTRAINT fk_ts_user   FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
    INDEX idx_toilet_status (toilet_id, status),
    INDEX idx_user_status (user_id, status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- session_photos: multiple photos per check-in AND per check-out
-- ------------------------------------------------------------
CREATE TABLE session_photos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    session_id   INT NOT NULL,
    photo_path   VARCHAR(255) NOT NULL,
    type         ENUM('checkin','checkout') NOT NULL,
    uploaded_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sp_session FOREIGN KEY (session_id) REFERENCES toilet_sessions(id) ON DELETE CASCADE,
    INDEX idx_session_type (session_id, type)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- NOTE: The default admin account is created by running install.php
-- in your browser after importing this schema (it generates a proper
-- bcrypt hash live via PHP's password_hash(), which is safer than a
-- hardcoded hash pasted into a SQL file). See README.md.
-- ------------------------------------------------------------

-- Optional sample data (safe to delete)
INSERT INTO toilets (code, name, location) VALUES
('T01', 'Block A - Level 1 - Male', 'Block A, Ground Floor'),
('T02', 'Block A - Level 1 - Female', 'Block A, Ground Floor'),
('T03', 'Block B - Level 2 - Male', 'Block B, 2nd Floor');
