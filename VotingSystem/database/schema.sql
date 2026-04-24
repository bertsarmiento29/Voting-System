-- =====================================================
-- Online Voting System Database Schema
-- MySQL 8.0+ | PHP 8.0+
-- =====================================================

-- Create database
CREATE DATABASE IF NOT EXISTS voting_system
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE voting_system;

-- =====================================================
-- Users Table (Admin accounts)
-- =====================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'super_admin') DEFAULT 'admin',
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- =====================================================
-- Categories (Positions)
-- =====================================================
CREATE TABLE categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB;

-- =====================================================
-- Voters Table
-- =====================================================
CREATE TABLE voters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) NOT NULL UNIQUE,
    student_id VARCHAR(50) NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NULL,
    password VARCHAR(255) NOT NULL,
    department VARCHAR(100) NULL,
    year_level VARCHAR(20) NULL,
    photo VARCHAR(255) NULL,
    has_voted TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    verification_token VARCHAR(255) NULL,
    verified_at DATETIME NULL,
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_voter_id (voter_id),
    INDEX idx_student_id (student_id),
    INDEX idx_email (email),
    INDEX idx_has_voted (has_voted)
) ENGINE=InnoDB;

-- =====================================================
-- Candidates Table
-- =====================================================
CREATE TABLE candidates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    party_group VARCHAR(100) NULL,
    bio TEXT NULL,
    photo VARCHAR(255) NULL,
    vision TEXT NULL,
    mission TEXT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =====================================================
-- Votes Table
-- =====================================================
CREATE TABLE votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voter_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    vote_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (voter_id, category_id),
    INDEX idx_candidate (candidate_id),
    INDEX idx_category (category_id),
    INDEX idx_timestamp (vote_timestamp)
) ENGINE=InnoDB;

-- =====================================================
-- Election Settings Table
-- =====================================================
CREATE TABLE election_settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    election_name VARCHAR(200) DEFAULT 'Student Council Election',
    description TEXT NULL,
    start_date DATETIME NULL,
    end_date DATETIME NULL,
    is_active TINYINT(1) DEFAULT 0,
    allow_voting TINYINT(1) DEFAULT 0,
    show_results TINYINT(1) DEFAULT 0,
    require_verification TINYINT(1) DEFAULT 0,
    max_votes_per_voter INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- Audit Logs Table
-- =====================================================
CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    user_type ENUM('admin', 'voter') NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================================================
-- Sessions Table (for session management)
-- =====================================================
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    user_type ENUM('admin', 'voter') NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    payload TEXT NOT NULL,
    last_activity INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_activity (last_activity)
) ENGINE=InnoDB;

-- =====================================================
-- Insert default admin user (password: admin123)
-- =====================================================
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin');

-- =====================================================
-- Insert sample categories
-- =====================================================
INSERT INTO categories (name, description, display_order) VALUES
('President', 'Student Council President', 1),
('Vice President', 'Student Council Vice President', 2),
('Secretary', 'Student Council Secretary', 3),
('Treasurer', 'Student Council Treasurer', 4),
('Auditor', 'Student Council Auditor', 5),
('Public Information Officer', 'Student Council PIO', 6);

-- =====================================================
-- Insert default election settings
-- =====================================================
INSERT INTO election_settings (election_name, description, is_active, allow_voting, show_results) VALUES
('Student Council Election 2026', 'Annual Student Council Election', 1, 0, 0);
