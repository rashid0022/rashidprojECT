-- SUZA CLEARANCE FORM MANAGEMENT SYSTEM
-- MySQL 8.0+

DROP DATABASE IF EXISTS suza_clearance_system;
CREATE DATABASE suza_clearance_system
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE suza_clearance_system;

-- =====================================================
-- DEPARTMENTS
-- =====================================================

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- CLEARANCE ITEMS
-- =====================================================

CREATE TABLE clearance_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    department_id INT NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clearance_item_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    UNIQUE KEY uniq_clearance_item_name_department (item_name, department_id)
) ENGINE=InnoDB;

-- =====================================================
-- USERS
-- =====================================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    registration_number VARCHAR(100) UNIQUE NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(30) NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student','officer','admin') NOT NULL DEFAULT 'student',
    department_id INT NULL,
    profile_completed TINYINT(1) NOT NULL DEFAULT 0,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- STUDENT PROFILES
-- =====================================================

CREATE TABLE student_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    faculty VARCHAR(100) NOT NULL,
    course VARCHAR(100) NOT NULL,
    academic_year ENUM('1','2','3','4','5') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_student_profile_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- CLEARANCE FORMS
-- =====================================================

CREATE TABLE clearance_forms (
    form_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    academic_session VARCHAR(20) NOT NULL,
    date_applied DATE NOT NULL,
    status ENUM('Pending','In Progress','Completed','Rejected') NOT NULL DEFAULT 'Pending',
    pdf_path VARCHAR(255) NULL,
    completed_at DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clearance_form_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- CLEARANCE STATUS
-- =====================================================

CREATE TABLE clearance_status (
    status_id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL,
    department_id INT NOT NULL,
    officer_id INT NULL,
    status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_form FOREIGN KEY (form_id) REFERENCES clearance_forms(form_id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_status_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_status_officer FOREIGN KEY (officer_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- AUDIT LOGS
-- =====================================================

CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- DEPARTMENTS DATA
-- =====================================================

INSERT INTO departments (department_name) VALUES
    ('Library'),
    ('Finance'),
    ('Accommodation'),
    ('Department Office');

-- =====================================================
-- ADMIN ACCOUNT
-- =====================================================

INSERT INTO users (
    full_name,
    registration_number,
    email,
    phone,
    password,
    role,
    profile_completed,
    status
) VALUES (
    'System Administrator',
    'ADMIN-0001',
    'admin@suza.ac.tz',
    '0000000000',
    '$2y$12$kVsEoPsra0lAwO5Vc.SB1eG15miXyTrmKo4ylORx5BxdH2cWFS3FW',
    'admin',
    1,
    'active'
);