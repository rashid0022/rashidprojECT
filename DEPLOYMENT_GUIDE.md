# ADVANCED CLEARANCE SYSTEM - DEPLOYMENT & INTEGRATION GUIDE

## Overview

This document provides complete instructions for deploying and integrating the advanced features into your SUZA Clearance System.

---

## PHASE 1: DATABASE MIGRATION

### Step 1: Backup Current Database
```bash
# Backup existing database
mysqldump -u root -p suza_clearance_system > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Apply Migration Script
```bash
# Run the improvements migration
mysql -u root -p suza_clearance_system < database/clearance_improvements.sql
```

### Step 3: Verify Tables Created
```sql
-- Check new tables
SHOW TABLES;

-- Verify key columns exist
DESCRIBE clearance_status;
DESCRIBE clearance_certificates;
DESCRIBE notifications;
DESCRIBE audit_logs;
```

---

## PHASE 2: INSTALL DEPENDENCIES

### Option A: Using Composer (Recommended)

```bash
cd /path/to/rashidprojECT
composer require tcpdf/tcpdf:^6.6
composer require phpmailer/phpmailer:^6.8
```

### Option B: Manual Installation

1. **TCPDF** (PDF Generation)
   - Download: https://tcpdf.org/
   - Extract to: `vendor/tcpdf/`

2. **PHPMailer** (Email Service)
   - Download: https://github.com/PHPMailer/PHPMailer
   - Extract to: `vendor/phpmailer/`

---

## PHASE 3: CONFIGURE ENVIRONMENT

### Create `.env` file in project root:

```env
# Application Settings
APP_NAME=SUZA Clearance System
APP_EMAIL=noreply@suza.ac.tz
APP_TIMEZONE=Africa/Dar_es_Salaam

# Email Configuration
MAIL_DRIVER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME=SUZA Clearance System

# Optional: SMS Configuration
SMS_ENABLED=false
SMS_PROVIDER=africastalking
SMS_API_KEY=your-api-key

# System Settings
CERTIFICATE_EXPIRY_DAYS=365
MAX_APPROVAL_TIME_DAYS=7
ENABLE_QR_VERIFICATION=true
ENABLE_EMAIL_NOTIFICATIONS=true

# Upload Paths
UPLOAD_CERTIFICATES=uploads/certificates/
UPLOAD_SIGNATURES=uploads/signatures/
UPLOAD_LOGOS=uploads/logos/
```

### Load Environment Variables:

Add to `config/database.php`:
```php
// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $envFile = file(__DIR__ . '/../.env');
    foreach ($envFile as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#')) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}
```

---

## PHASE 4: INTEGRATE SERVICE CLASSES

### Add Auto-loader to Your Config:

Create `config/loader.php`:
```php
<?php
// Auto-load required classes
$services = [
    'AuditLogger',
    'NotificationService',
    'EmailQueueProcessor',
    'CertificateGenerator',
    'WorkflowEngine'
];

foreach ($services as $service) {
    $path = __DIR__ . '/../includes/' . $service . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}
```

Add to `config/database.php`:
```php
require_once __DIR__ . '/loader.php';
```

---

## PHASE 5: UPDATE APPROVAL WORKFLOW

### Option A: Use Enhanced Approve Handler

Update `officer/dashboard.php` form action:
```html
<form action="approve_enhanced.php" method="post">
    <!-- Form fields -->
</form>
```

### Option B: Update Existing approve.php

Replace content of `officer/approve.php` with `officer/approve_enhanced.php`

---

## PHASE 6: SETUP AUTOMATED TASKS

### Email Queue Processor (Cron Job)

Add to server crontab:
```bash
# Process pending emails every 5 minutes
*/5 * * * * php /path/to/rashidprojECT/includes/EmailQueueProcessor.php

# Cleanup old logs daily at 2 AM
0 2 * * * php /path/to/cleanup.php

# Generate daily reports at 6 AM
0 6 * * * php /path/to/generate-reports.php
```

Create `cleanup.php`:
```php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/AuditLogger.php';
require_once __DIR__ . '/includes/NotificationService.php';

$auditLogger = new AuditLogger($pdo);
$notificationService = new NotificationService($pdo);

// Cleanup old records
$auditLogger->purgeOldLogs(90); // Keep last 90 days
$notificationService->purgeOldNotifications(30); // Keep last 30 days

echo "Cleanup completed\n";
?>
```

---

## PHASE 7: IMPLEMENT NOTIFICATIONS

### Update Student Dashboard to Show Notifications

Add to `student/dashboard.php`:
```php
<?php
require_once __DIR__ . '/../includes/NotificationService.php';
$notificationService = new NotificationService($pdo);
$unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']);
?>

<!-- Add notification bell in header -->
<button class="btn btn-link position-relative" data-bs-toggle="offcanvas" data-bs-target="#notificationCanvas">
    <i class="bi bi-bell fs-5"></i>
    <?php if ($unreadCount > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            <?= $unreadCount; ?>
        </span>
    <?php endif; ?>
</button>

<!-- Notification canvas offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="notificationCanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Notifications</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <!-- Load notifications via API -->
    </div>
</div>
```

---

## PHASE 8: CREATE CERTIFICATE PAGE

Create `student/certificate.php`:
```php
<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/CertificateGenerator.php';

$certificateGenerator = new CertificateGenerator($pdo);
$formId = (int) ($_GET['form_id'] ?? 0);

// Get certificate
$stmt = $pdo->prepare('
    SELECT cc.*, cf.form_id, u.full_name
    FROM clearance_certificates cc
    JOIN clearance_forms cf ON cc.form_id = cf.form_id
    JOIN users u ON cf.user_id = u.user_id
    WHERE cf.form_id = ? AND cf.user_id = ?
');
$stmt->execute([$formId, $_SESSION['user_id']]);
$certificate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificate) {
    $_SESSION['error'] = 'Certificate not found';
    header('Location: dashboard.php');
    exit;
}

// Handle download
if (isset($_GET['download'])) {
    $result = $certificateGenerator->downloadCertificate($certificate['certificate_id'], $_SESSION['user_id']);
    if ($result['success']) {
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
        readfile($result['filepath']);
        exit;
    }
}
?>
<!-- Display certificate with download button -->
```

---

## PHASE 9: ADD API ENDPOINTS

All API endpoints are now in `api/` folder:
- `api/clearance.php` - Get clearance status
- `api/notifications.php` - Manage notifications

### Usage Examples:

```bash
# Get clearance status
curl -X GET "http://localhost/rashidprojECT/api/clearance.php?form_id=1"

# Get user notifications
curl -X GET "http://localhost/rashidprojECT/api/notifications.php?limit=20"

# Mark notification as read
curl -X POST "http://localhost/rashidprojECT/api/notifications.php" \
  -d "action=mark-read&id=123"
```

---

## PHASE 10: ENABLE ADMIN REPORTS

### Access Reports Dashboard
1. Login as Admin
2. Navigate to: **Admin Menu → Reports**
3. View statistics and department performance

### Available Reports:
- Overall clearance statistics
- Department-wise performance
- Processing time analytics
- Recent requests status

---

## PHASE 11: SECURITY CONSIDERATIONS

### 1. File Uploads Security
```php
// Validate file uploads
function validateSignatureUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large');
    }
    
    return true;
}
```

### 2. API Rate Limiting
```php
// Add to API endpoints
function checkRateLimit($userId, $limit = 60, $timeWindow = 3600) {
    $key = "api_rate_" . $userId;
    // Implement using Redis or file-based counter
}
```

### 3. Audit All Critical Actions
```php
// Every approval/rejection is automatically logged
$auditLogger->logClearanceApproval(...);
```

---

## PHASE 12: MONITORING & MAINTENANCE

### Monitor Queue Status:
```php
<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/EmailQueueProcessor.php';

$processor = new EmailQueueProcessor($pdo);
$stats = $processor->getQueueStats();
print_r($stats);
?>
```

### Check System Health:
```sql
-- Monitor pending approvals
SELECT d.department_name, COUNT(*) as pending
FROM clearance_status cs
JOIN departments d ON cs.department_id = d.department_id
WHERE cs.status = 'Pending'
GROUP BY d.department_id;

-- Check notification backlog
SELECT COUNT(*) FROM email_queue WHERE status = 'pending';

-- Audit trail for compliance
SELECT * FROM audit_logs 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY created_at DESC;
```

---

## TROUBLESHOOTING

### Issue: Emails not being sent
**Solution:**
1. Check email queue: `SELECT * FROM email_queue WHERE status='pending';`
2. Verify SMTP credentials in `.env`
3. Check server logs: `tail -f /var/log/mail.log`
4. Run processor manually: `php includes/EmailQueueProcessor.php`

### Issue: Certificates not generating
**Solution:**
1. Check upload directory permissions: `chmod 755 uploads/certificates/`
2. Verify TCPDF installation: `php -r "require 'vendor/autoload.php';"`
3. Check error logs: `tail -f logs/error.log`

### Issue: Audit logs growing too large
**Solution:**
1. Run cleanup: `php cleanup.php`
2. Archive old logs to separate table
3. Increase cron frequency

---

## PERFORMANCE OPTIMIZATION

### 1. Database Indexing
All indexes are created in migration script. Verify:
```sql
SHOW INDEX FROM clearance_status;
SHOW INDEX FROM notifications;
SHOW INDEX FROM audit_logs;
```

### 2. Cache Notifications
```php
// Cache unread count for 5 minutes
$cacheKey = 'user_' . $_SESSION['user_id'] . '_unread_notifications';
if (apcu_exists($cacheKey)) {
    $unreadCount = apcu_fetch($cacheKey);
} else {
    $unreadCount = $notificationService->getUnreadCount($_SESSION['user_id']);
    apcu_store($cacheKey, $unreadCount, 300);
}
```

### 3. Batch Email Processing
Already implemented - processes 50 emails per cron run

---

## PRODUCTION DEPLOYMENT CHECKLIST

- [ ] Database backed up
- [ ] Migration script applied successfully
- [ ] Dependencies installed (Composer/Manual)
- [ ] `.env` file configured
- [ ] Upload directories created with correct permissions
- [ ] Email configured and tested
- [ ] Cron jobs scheduled
- [ ] API endpoints tested
- [ ] Admin reports accessible
- [ ] Notifications working
- [ ] Certificate generation tested
- [ ] Audit logging verified
- [ ] Security review completed
- [ ] Performance tested
- [ ] Backup strategy in place

---

## NEXT STEPS

1. **Week 1-2:** Deploy Phase 1-3 and test notifications
2. **Week 3-4:** Implement signatures and certificates
3. **Week 5-6:** Deploy QR verification and APIs
4. **Week 7-8:** Setup monitoring and optimization
5. **Week 9-10:** User training and documentation

---

## SUPPORT & DOCUMENTATION

- API Documentation: See `API_DOCUMENTATION.md`
- Database Schema: See `database/clearance_improvements.sql`
- Configuration: See `.env.example`
- Troubleshooting: See this section above

---

**System Ready for Production Deployment! 🚀**
