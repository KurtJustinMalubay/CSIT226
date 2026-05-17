-- ============================================
--  Lost & Found System
--  Database Setup Script
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS dbstudentinfosys
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE dbstudentinfosys;

-- ---- user ----
CREATE TABLE IF NOT EXISTS user (
    uId VARCHAR(50) PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    universityId VARCHAR(50) NOT NULL UNIQUE,
    isAdmin BOOLEAN DEFAULT 0,
    isStudent BOOLEAN DEFAULT 0,
    isFaculty BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---- admin_staff ----
CREATE TABLE IF NOT EXISTS admin_staff (
    adId VARCHAR(50) PRIMARY KEY,
    adminRole VARCHAR(100) NOT NULL,
    FOREIGN KEY (adId) REFERENCES user(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- faculty ----
CREATE TABLE IF NOT EXISTS faculty (
    facId VARCHAR(50) PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    FOREIGN KEY (facId) REFERENCES user(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- student ----
CREATE TABLE IF NOT EXISTS student (
    studId VARCHAR(50) PRIMARY KEY,
    course VARCHAR(100) NOT NULL,
    contactNo VARCHAR(20),
    dob DATE,
    FOREIGN KEY (studId) REFERENCES user(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- item_report ----
CREATE TABLE IF NOT EXISTS item_report (
    reportId INT AUTO_INCREMENT PRIMARY KEY,
    reporterId VARCHAR(50) NOT NULL,
    itemName VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    eventDate DATE NOT NULL,
    reportType ENUM('Lost', 'Found') NOT NULL,
    currentStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') DEFAULT 'Pending',
    location VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporterId) REFERENCES user(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- claim_request ----
CREATE TABLE IF NOT EXISTS claim_request (
    claimId INT AUTO_INCREMENT PRIMARY KEY,
    reportId INT NOT NULL,
    claimantId VARCHAR(50) NOT NULL,
    approveAdminId VARCHAR(50) DEFAULT NULL,
    proofOfOwnership TEXT NOT NULL,
    claimStatus ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
    claimDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reportId) REFERENCES item_report(reportId) ON DELETE CASCADE,
    FOREIGN KEY (claimantId) REFERENCES user(uId) ON DELETE CASCADE,
    FOREIGN KEY (approveAdminId) REFERENCES admin_staff(adId) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---- audit_log ----
CREATE TABLE IF NOT EXISTS audit_log (
    logId INT AUTO_INCREMENT PRIMARY KEY,
    reportId INT NOT NULL,
    adminId VARCHAR(50) NOT NULL,
    oldStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') DEFAULT NULL,
    newStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') NOT NULL,
    timeStamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reportId) REFERENCES item_report(reportId) ON DELETE CASCADE,
    FOREIGN KEY (adminId) REFERENCES admin_staff(adId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Default Admin User ----
-- Password is 'admin123' (hashed)
INSERT IGNORE INTO user (uId, username, fullName, email, password, universityId, isAdmin, isStudent, isFaculty) VALUES
('admin_1', 'admin', 'System Administrator', 'admin@university.edu', '$2y$10$tZ2xZ11B4.1c11Gz.L.aZ.tPzZ5uE2B1Xg.z1/B1uJb2r3.0WkGim', 'ADMIN-001', 1, 0, 0);

INSERT IGNORE INTO admin_staff (adId, adminRole) VALUES
('admin_1', 'System Admin');

