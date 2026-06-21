-- =====================================================
-- CLEARANCE SYSTEM IMPROVEMENTS & ENHANCEMENTS
-- MySQL 8.0+ Compatible
-- =====================================================

USE suza_clearance_system;

-- =====================================================
-- 1. OFFICER PROFILES & SIGNATURES
-- =====================================================

CREATE TABLE IF NOT EXISTS officer_profiles (
    officer_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    position VARCHAR(150) NOT NULL,
    qualification VARCHAR(150) NULL,
    signature_path VARCHAR(255) NULL,
    signature_image LONGBLOB NULL,
    phone_number VARCHAR(20) NULL,
    office_location VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_officer_profile_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 2. ENHANCED CLEARANCE STATUS WITH APPROVAL TRACKING
-- =====================================================

-- Rename and enhance clearance_status table
ALTER TABLE clearance_status ADD COLUMN IF NOT EXISTS 
    approval_date DATETIME NULL AFTER `updated_at`;

ALTER TABLE clearance_status ADD COLUMN IF NOT EXISTS 
    rejection_reason TEXT NULL AFTER `approval_date`;

ALTER TABLE clearance_status ADD COLUMN IF NOT EXISTS 
    signature_path VARCHAR(255) NULL AFTER `rejection_reason`;

ALTER TABLE clearance_status ADD COLUMN IF NOT EXISTS 
    notes TEXT NULL AFTER `signature_path`;

ALTER TABLE clearance_status ADD COLUMN IF NOT EXISTS 
    sequence_order INT NOT NULL DEFAULT 0 AFTER `notes`;

-- Index for faster queries
CREATE INDEX idx_clearance_status_form_department ON clearance_status(form_id, department_id);
CREATE INDEX idx_clearance_status_officer ON clearance_status(officer_id);
CREATE INDEX idx_clearance_status_approval_date ON clearance_status(approval_date);

-- =====================================================
-- 3. CLEARANCE CERTIFICATES
-- =====================================================

CREATE TABLE IF NOT EXISTS clearance_certificates (
    certificate_id INT AUTO_INCREMENT PRIMARY KEY,
    form_id INT NOT NULL UNIQUE,
    user_id INT NOT NULL,
    clearance_number VARCHAR(100) NOT NULL UNIQUE,
    pdf_path VARCHAR(255) NULL,
    qr_code_path VARCHAR(255) NULL,
    qr_code_data LONGTEXT NULL,
    issue_date DATE NOT NULL,
    expiry_date DATE NULL,
    generated_by INT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_count INT DEFAULT 0,
    last_verified_at DATETIME NULL,
    status ENUM('generated','sent','revoked') NOT NULL DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_cert_form FOREIGN KEY (form_id) REFERENCES clearance_forms(form_id) ON DELETE CASCADE,
    CONSTRAINT fk_cert_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_cert_generated_by FOREIGN KEY (generated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_certificate_clearance_number ON clearance_certificates(clearance_number);
CREATE INDEX idx_certificate_user ON clearance_certificates(user_id);
CREATE INDEX idx_certificate_status ON clearance_certificates(status);

-- =====================================================
-- 4. NOTIFICATIONS SYSTEM
-- =====================================================

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    form_id INT NULL,
    status_id INT NULL,
    type ENUM('approval','rejection','submission','completion','certificate') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    from_department_id INT NULL,
    from_officer_id INT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    action_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_form FOREIGN KEY (form_id) REFERENCES clearance_forms(form_id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_status FOREIGN KEY (status_id) REFERENCES clearance_status(status_id) ON DELETE CASCADE,
    CONSTRAINT fk_notif_department FOREIGN KEY (from_department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    CONSTRAINT fk_notif_officer FOREIGN KEY (from_officer_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_notification_user ON notifications(user_id, is_read);
CREATE INDEX idx_notification_created ON notifications(created_at);

-- =====================================================
-- 5. ENHANCED AUDIT LOGS
-- =====================================================

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS 
    entity_type VARCHAR(100) NULL AFTER `ip_address`;

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS 
    entity_id INT NULL AFTER `entity_type`;

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS 
    old_value LONGTEXT NULL AFTER `entity_id`;

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS 
    new_value LONGTEXT NULL AFTER `old_value`;

ALTER TABLE audit_logs ADD COLUMN IF NOT EXISTS 
    status_code INT NULL AFTER `new_value`;

CREATE INDEX idx_audit_user ON audit_logs(user_id);
CREATE INDEX idx_audit_created ON audit_logs(created_at);
CREATE INDEX idx_audit_entity ON audit_logs(entity_type, entity_id);
CREATE INDEX idx_audit_action ON audit_logs(action);

-- =====================================================
-- 6. CLEARANCE WORKFLOW CONFIGURATION
-- =====================================================

CREATE TABLE IF NOT EXISTS clearance_workflow (
    workflow_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    department_id INT NULL,
    sequence_order INT NOT NULL,
    is_mandatory TINYINT(1) NOT NULL DEFAULT 1,
    allow_parallel TINYINT(1) NOT NULL DEFAULT 0,
    time_limit_days INT NULL,
    notification_before_days INT DEFAULT 2,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_workflow_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 7. EMAIL QUEUE FOR NOTIFICATIONS
-- =====================================================

CREATE TABLE IF NOT EXISTS email_queue (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_user_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    body LONGTEXT NOT NULL,
    template_name VARCHAR(100) NULL,
    template_data JSON NULL,
    status ENUM('pending','sent','failed','bounced') NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    retry_count INT DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_email_user FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_email_status ON email_queue(status);
CREATE INDEX idx_email_recipient ON email_queue(recipient_user_id);

-- =====================================================
-- 8. SMS NOTIFICATIONS (Optional)
-- =====================================================

CREATE TABLE IF NOT EXISTS sms_queue (
    sms_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_phone VARCHAR(20) NOT NULL,
    recipient_user_id INT NULL,
    message TEXT NOT NULL,
    status ENUM('pending','sent','failed','delivery_failed') NOT NULL DEFAULT 'pending',
    sent_at DATETIME NULL,
    retry_count INT DEFAULT 0,
    last_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sms_user FOREIGN KEY (recipient_user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_sms_status ON sms_queue(status);

-- =====================================================
-- 9. CLEARANCE STATISTICS & REPORTING
-- =====================================================

CREATE TABLE IF NOT EXISTS clearance_statistics (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    report_date DATE NOT NULL,
    department_id INT NULL,
    total_requests INT DEFAULT 0,
    approved_count INT DEFAULT 0,
    rejected_count INT DEFAULT 0,
    pending_count INT DEFAULT 0,
    average_processing_time_days DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stat_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE,
    UNIQUE KEY uniq_stat_date_dept (report_date, department_id)
) ENGINE=InnoDB;

-- =====================================================
-- 10. SYSTEM SETTINGS
-- =====================================================

CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(500) NULL,
    setting_type ENUM('string','integer','boolean','json') DEFAULT 'string',
    description TEXT NULL,
    is_editable TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_setting_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Default system settings
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('app_name', 'SUZA Clearance System', 'string', 'Application name'),
('app_email', 'noreply@suza.ac.tz', 'string', 'System email address'),
('enable_email_notifications', '1', 'boolean', 'Enable email notifications'),
('enable_sms_notifications', '0', 'boolean', 'Enable SMS notifications'),
('certificate_expiry_days', '365', 'integer', 'Days until certificate expiry'),
('max_approval_time_days', '7', 'integer', 'Maximum days for approval'),
('enable_qr_verification', '1', 'boolean', 'Enable QR code verification'),
('organization_name', 'Stone Town University of Zanzibar', 'string', 'Organization name for certificates'),
('organization_address', 'Zanzibar, Tanzania', 'string', 'Organization address'),
('organization_phone', '+255 1234 5678', 'string', 'Organization phone');

-- =====================================================
-- 11. INSERT DEFAULT CLEARANCE WORKFLOW
-- =====================================================

INSERT IGNORE INTO clearance_workflow (name, description, department_id, sequence_order, is_mandatory, allow_parallel) VALUES
(1, 'Library Clearance', 'Library clearance check', 1, 1, 1, 0),
(2, 'Finance Clearance', 'Finance department review', 2, 2, 1, 1),
(3, 'Accommodation Clearance', 'Accommodation verification', 3, 3, 1, 1),
(4, 'Department Office Clearance', 'Final department approval', 4, 4, 1, 0);

-- =====================================================
-- END OF MIGRATION SCRIPT
-- =====================================================
