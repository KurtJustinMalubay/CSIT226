-- ============================================
--  Lost & Found System
--  Database Setup Script
--  Run this in phpMyAdmin or MySQL CLI
-- ============================================

CREATE DATABASE IF NOT EXISTS lost_and_found_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE lost_and_found_db;

-- ---- User ----
CREATE TABLE IF NOT EXISTS User (
    uId VARCHAR(50) PRIMARY KEY,
    fullName VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    universityId VARCHAR(50) NOT NULL UNIQUE,
    isAdmin BOOLEAN DEFAULT 0,
    isStudent BOOLEAN DEFAULT 0,
    isFaculty BOOLEAN DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---- Admin_Staff ----
CREATE TABLE IF NOT EXISTS Admin_Staff (
    adId VARCHAR(50) PRIMARY KEY,
    adminRole VARCHAR(100) NOT NULL,
    FOREIGN KEY (adId) REFERENCES User(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Faculty ----
CREATE TABLE IF NOT EXISTS Faculty (
    facId VARCHAR(50) PRIMARY KEY,
    department VARCHAR(100) NOT NULL,
    FOREIGN KEY (facId) REFERENCES User(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Student ----
CREATE TABLE IF NOT EXISTS Student (
    studId VARCHAR(50) PRIMARY KEY,
    course VARCHAR(100) NOT NULL,
    FOREIGN KEY (studId) REFERENCES User(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Item_Report ----
CREATE TABLE IF NOT EXISTS Item_Report (
    reportId INT AUTO_INCREMENT PRIMARY KEY,
    reporterId VARCHAR(50) NOT NULL,
    itemName VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    eventDate DATE NOT NULL,
    reportType ENUM('Lost', 'Found') NOT NULL,
    currentStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') DEFAULT 'Pending',
    location VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporterId) REFERENCES User(uId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Claim_Request ----
CREATE TABLE IF NOT EXISTS Claim_Request (
    claimId INT AUTO_INCREMENT PRIMARY KEY,
    reportId INT NOT NULL,
    claimantId VARCHAR(50) NOT NULL,
    approveAdminId VARCHAR(50) DEFAULT NULL,
    proofOfOwnership TEXT NOT NULL,
    claimStatus ENUM('Pending', 'Approved', 'Denied') DEFAULT 'Pending',
    claimDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reportId) REFERENCES Item_Report(reportId) ON DELETE CASCADE,
    FOREIGN KEY (claimantId) REFERENCES User(uId) ON DELETE CASCADE,
    FOREIGN KEY (approveAdminId) REFERENCES Admin_Staff(adId) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---- Audit_Log ----
CREATE TABLE IF NOT EXISTS Audit_Log (
    logId INT AUTO_INCREMENT PRIMARY KEY,
    reportId INT NOT NULL,
    adminId VARCHAR(50) NOT NULL,
    oldStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') DEFAULT NULL,
    newStatus ENUM('Pending', 'Matched', 'Claimed', 'Returned', 'Resolved', 'Cancelled') NOT NULL,
    timeStamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reportId) REFERENCES Item_Report(reportId) ON DELETE CASCADE,
    FOREIGN KEY (adminId) REFERENCES Admin_Staff(adId) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---- Default Admin User ----
-- Password is 'admin123' (hashed)
INSERT IGNORE INTO User (uId, fullName, email, password, universityId, isAdmin, isStudent, isFaculty) VALUES
('admin_1', 'System Administrator', 'admin@university.edu', '$2y$10$tZ2xZ11B4.1c11Gz.L.aZ.tPzZ5uE2B1Xg.z1/B1uJb2r3.0WkGim', 'ADMIN-001', 1, 0, 0);

INSERT IGNORE INTO Admin_Staff (adId, adminRole) VALUES
('admin_1', 'System Admin');
