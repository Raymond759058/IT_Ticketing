-- ============================================================
-- IT Ticketing System - Database Schema
-- Compatible with MySQL 5.7+ / MariaDB (XAMPP & cPanel)
-- ============================================================

CREATE DATABASE IF NOT EXISTS it_ticketing_system
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE it_ticketing_system;

-- ------------------------------------------------------------
-- Departments
-- ------------------------------------------------------------
CREATE TABLE departments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Users (Super Admin, IT Admin, Technician, User)
-- ------------------------------------------------------------
CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  phone VARCHAR(30) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('super_admin','it_admin','technician','user') NOT NULL DEFAULT 'user',
  department_id INT DEFAULT NULL,
  status ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  avatar VARCHAR(255) DEFAULT NULL,
  remember_token VARCHAR(255) DEFAULT NULL,
  reset_token VARCHAR(255) DEFAULT NULL,
  reset_expires DATETIME DEFAULT NULL,
  last_login DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Ticket Categories
-- ------------------------------------------------------------
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  description VARCHAR(255) DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Ticket Priorities
-- ------------------------------------------------------------
CREATE TABLE priorities (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  level INT NOT NULL DEFAULT 1,      -- 1=Low 2=Medium 3=High 4=Critical
  color VARCHAR(20) NOT NULL DEFAULT '#6c757d',
  sla_hours INT DEFAULT 72,
  status TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tickets
-- ------------------------------------------------------------
CREATE TABLE tickets (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_number VARCHAR(20) NOT NULL UNIQUE,
  subject VARCHAR(200) NOT NULL,
  description TEXT NOT NULL,
  category_id INT DEFAULT NULL,
  department_id INT DEFAULT NULL,
  priority_id INT DEFAULT NULL,
  status ENUM('Open','Pending','In Progress','Resolved','Closed','Cancelled') NOT NULL DEFAULT 'Open',
  requester_id INT NOT NULL,
  assigned_to INT DEFAULT NULL,
  contact_info VARCHAR(150) DEFAULT NULL,
  attachment VARCHAR(255) DEFAULT NULL,
  resolution_notes TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  resolved_at DATETIME DEFAULT NULL,
  closed_at DATETIME DEFAULT NULL,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
  FOREIGN KEY (priority_id) REFERENCES priorities(id) ON DELETE SET NULL,
  FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Ticket Replies / Work Notes
-- ------------------------------------------------------------
CREATE TABLE ticket_replies (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  user_id INT NOT NULL,
  message TEXT NOT NULL,
  is_internal_note TINYINT(1) NOT NULL DEFAULT 0,
  attachment VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Audit Logs
-- ------------------------------------------------------------
CREATE TABLE audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  ticket_id INT DEFAULT NULL,
  action VARCHAR(150) NOT NULL,
  details VARCHAR(500) DEFAULT NULL,
  ip_address VARCHAR(50) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- System Settings
-- ------------------------------------------------------------
CREATE TABLE settings (
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Seed Data
-- ------------------------------------------------------------
INSERT INTO departments (name, description) VALUES
('IT Support', 'General IT support and helpdesk'),
('Network', 'Network and infrastructure issues'),
('Software', 'Software installation and bugs'),
('Hardware', 'Hardware repairs and requests');

INSERT INTO categories (name, description) VALUES
('Hardware Issue', 'Computer, printer, and device problems'),
('Software Issue', 'Application errors and installs'),
('Network Issue', 'Internet and connectivity problems'),
('Account Access', 'Login, password and permission issues'),
('Email Issue', 'Email/Outlook related problems'),
('Other', 'Anything not listed above');

INSERT INTO priorities (name, level, color, sla_hours) VALUES
('Low', 1, '#198754', 120),
('Medium', 2, '#0d6efd', 72),
('High', 3, '#6f42c1', 24),
('Critical', 4, '#dc3545', 4);

INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'IT Ticketing System'),
('site_email', 'support@ittickets.local'),
('ticket_prefix', 'TCK'),
('allow_registration', '1'),
('email_notifications', '1');

-- Default Super Admin
-- Login email:    admin@ittickets.local
-- Login password: Admin@123
-- (Bcrypt hash below is valid for password_verify() in PHP -- CHANGE THIS PASSWORD after first login!)
INSERT INTO users (name, email, password, role, status) VALUES
('Super Admin', 'admin@ittickets.local', '$2b$10$D/YxKGukS1sTkihSFRUvyu0e0NqrIl9ETlxjRS7f7atg.8S3Np0hm', 'super_admin', 'active');
