-- ============================================
--  SIS - Student Information System
--  Database Setup Script
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS dbstudentinfosys
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dbstudentinfosys;

-- ---- User Profiles ----
CREATE TABLE IF NOT EXISTS tbluserprofile (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    firstname VARCHAR(100) NOT NULL,
    lastname  VARCHAR(100) NOT NULL,
    gender    VARCHAR(10)  DEFAULT NULL,
    created_at DATETIME    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---- User Accounts ----
CREATE TABLE IF NOT EXISTS tbluseraccount (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    username  VARCHAR(100) NOT NULL UNIQUE,
    emailadd  VARCHAR(150) NOT NULL,
    password  VARCHAR(255) NOT NULL,
    created_at DATETIME   DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---- Students ----
CREATE TABLE IF NOT EXISTS tblstudent (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    idnumber   VARCHAR(50)  NOT NULL,
    firstname  VARCHAR(100) NOT NULL,
    lastname   VARCHAR(100) NOT NULL,
    gender     VARCHAR(10)  DEFAULT NULL,
    program    VARCHAR(100) DEFAULT NULL,
    contactno  VARCHAR(20)  DEFAULT NULL,
    dob        DATE         DEFAULT NULL,
    created_at DATETIME     DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---- Sample Students ----
INSERT INTO tblstudent (idnumber, firstname, lastname, gender, program, contactno, dob) VALUES
('2024-00001', 'Juan',    'Dela Cruz',  'Male',   'BSIT',  '09171234567', '2004-03-15'),
('2024-00002', 'Maria',   'Santos',     'Female',  'BSCS',  '09281234567', '2004-07-22'),
('2024-00003', 'Jose',    'Reyes',      'Male',   'BSIS',  '09391234567', '2003-11-05'),
('2024-00004', 'Ana',     'Garcia',     'Female',  'BSN',   '09451234567', '2005-01-18'),
('2024-00005', 'Carlos',  'Mendoza',    'Male',   'BSBA',  '09561234567', '2003-09-30');
