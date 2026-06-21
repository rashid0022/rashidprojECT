# SUZA CLEARANCE SYSTEM - QUICK REFERENCE GUIDE

## 🚀 GETTING STARTED (5 minutes)

### 1. Apply Database Migration
```bash
mysql -u root -p suza_clearance_system < database/clearance_improvements.sql
```

### 2. Configure Environment
```bash
cp .env.example .env
nano .env  # Edit with your settings
```

### 3. Verify Files Created
```bash
ls -la includes/AuditLogger.php
ls -la api/clearance.php
ls -la admin/reports.php
```

### 4. Test Access
- Student Login: http://localhost/rashidprojECT/index.php
- Officer Login: http://localhost/rashidprojECT/index.php
- Admin Login: http://localhost/rashidprojECT/admin/

---

## 👨‍🎓 STUDENT USER GUIDE

### Submit Clearance Request
1. Login to Student Dashboard
2. Click "Request Clearance"
3. Select Academic Year
4. Click "Submit Request"
5. Confirmation message displays

### Track Request Status
1. Dashboard shows overview
2. Click "View Status" for details
3. See each department's status
4. Real-time updates

### Download Certificate
1. Once all approved, "Download Certificate" appears
2. Click to download PDF
3. Print or email as needed

### Receive Notifications
1. Bell icon shows unread notifications
2. Click to view all notifications
3. Mark as read when viewed
4. Email notifications also sent

---

## 👮 OFFICER USER GUIDE

### Access Pending Requests
1. Login to Officer Dashboard
2. View "Pending Requests" section
3. Click "Pending Requests" tab for full view
4. Cards show student info and required action

### Approve a Request
1. Click "Approve" button on request
2. Modal form opens
3. Enter optional comment
4. Click "Confirm Approval"
5. Student notified automatically

### Reject a Request
1. Click "Reject" button on request
2. Modal form opens
3. Enter required rejection reason
4. Click "Confirm Rejection"
5. Student notified with reason

### Update Profile
1. Click "My Profile" (if available)
2. Upload signature image
3. Update position and qualifications
4. Save changes

### View Statistics
1. Dashboard shows pending count
2. Approved/rejected statistics
3. Performance metrics
4. Monthly trends

---

## 👨‍💼 ADMIN USER GUIDE

### Access Admin Dashboard
- URL: `/admin/dashboard.php`
- Requires admin role
- Shows overview statistics

### View Reports
1. Click "Reports" in admin menu
2. See clearance statistics
3. View department performance
4. Check processing times

### Manage Departments
1. Go to Admin → Departments
2. View all departments
3. Add/edit departments
4. Assign officers to departments

### Manage Users
1. Go to Admin → Users
2. View all users
3. Create new users
4. Assign roles (student, officer, admin)

### View Audit Logs
1. Go to Admin → Audit Logs
2. Search by date range
3. Filter by action type
4. View change history

### Configure System
1. Go to Admin → Settings
2. Update organization info
3. Configure email settings
4. Set approval workflows

### Monitor System Health
1. Check email queue status
2. Review system logs
3. Monitor database size
4. Check disk space

---

## 🔧 TECHNICAL OPERATIONS

### Start Email Processing
```bash
php includes/EmailQueueProcessor.php
```

### Check Database Status
```sql
-- See pending approvals
SELECT COUNT(*) FROM clearance_status WHERE status = 'Pending';

-- Check email queue
SELECT * FROM email_queue WHERE status = 'pending' LIMIT 5;

-- View recent approvals
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 10;
```

### Generate Backup
```bash
mysqldump -u root -p suza_clearance_system > backup_$(date +%Y%m%d_%H%M%S).sql
```

### Restore from Backup
```bash
mysql -u root -p suza_clearance_system < backup_20260601_120000.sql
```

### Clear Old Logs
```sql
-- Delete notifications older than 30 days
DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Delete audit logs older than 90 days
DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

---

## 🔌 API QUICK REFERENCE

### Get Clearance Status
```bash
curl -X GET "http://localhost/rashidprojECT/api/clearance.php?form_id=1"
```

**Response:**
```json
{
    "success": true,
    "form": {
        "form_id": 1,
        "student_name": "John Doe",
        "status": "In Progress"
    },
    "progress": 50,
    "approvals": {
        "completed": [
            {"department": "Registrar", "status": "Approved", "date": "2026-06-01"}
        ],
        "pending": [
            {"department": "Finance", "status": "Pending"}
        ]
    }
}
```

### Get Notifications
```bash
curl -X GET "http://localhost/rashidprojECT/api/notifications.php?limit=10"
```

### Mark Notification as Read
```bash
curl -X POST "http://localhost/rashidprojECT/api/notifications.php" \
    -d "action=mark-read&id=123"
```

---

## 🆘 TROUBLESHOOTING

### Emails Not Sending
**Check:**
1. Email queue: `SELECT * FROM email_queue WHERE status = 'pending';`
2. SMTP settings in `.env`
3. Run processor: `php includes/EmailQueueProcessor.php`

**Fix:**
```bash
# Check mail logs
tail -f /var/log/mail.log

# Test mail configuration
php -r "mail('test@example.com', 'Test', 'Test message');"
```

### Database Connection Failed
**Check:**
1. MySQL is running: `systemctl status mysql`
2. Credentials in `config/database.php`
3. Database exists: `mysql -u root -p -e "SHOW DATABASES;"`

**Fix:**
```bash
# Restart MySQL
systemctl restart mysql

# Test connection
mysql -u root -p -e "SELECT VERSION();"
```

### Slow Query Performance
**Check:**
1. Enable query log
2. Check indexes: `SHOW INDEX FROM clearance_status;`
3. Analyze table: `ANALYZE TABLE clearance_status;`

**Fix:**
```sql
-- Check slow queries
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;

-- Optimize table
OPTIMIZE TABLE clearance_status;
OPTIMIZE TABLE clearance_forms;
```

### Certificate Generation Failed
**Check:**
1. Upload directory permissions: `ls -la uploads/certificates/`
2. TCPDF installed: `php -r "require 'vendor/autoload.php';"`

**Fix:**
```bash
# Create directories
mkdir -p uploads/certificates
mkdir -p uploads/signatures
chmod 755 uploads/

# Install dependencies
composer install
```

---

## 📊 COMMON QUERIES

### Get Clearance Stats
```sql
SELECT 
    COUNT(*) as total,
    SUM(status = 'Completed') as completed,
    SUM(status = 'Rejected') as rejected,
    SUM(status = 'In Progress') as in_progress
FROM clearance_forms;
```

### Get Department Performance
```sql
SELECT 
    d.department_name,
    COUNT(cs.status_id) as total,
    SUM(cs.status = 'Approved') as approved,
    SUM(cs.status = 'Rejected') as rejected,
    AVG(DATEDIFF(cs.approval_date, cs.created_at)) as avg_days
FROM clearance_status cs
JOIN departments d ON cs.department_id = d.department_id
GROUP BY d.department_id;
```

### Get Pending by Department
```sql
SELECT 
    d.department_name,
    COUNT(*) as pending,
    u.full_name as officer
FROM clearance_status cs
JOIN departments d ON cs.department_id = d.department_id
LEFT JOIN users u ON cs.department_id = u.department_id AND u.role = 'officer'
WHERE cs.status = 'Pending'
GROUP BY d.department_id;
```

### Get Student Clearance History
```sql
SELECT 
    cf.form_id,
    cf.academic_session,
    cf.status,
    cf.date_applied,
    cf.completed_at,
    GROUP_CONCAT(CONCAT(d.department_name, ':', cs.status) SEPARATOR '; ') as approvals
FROM clearance_forms cf
LEFT JOIN clearance_status cs ON cf.form_id = cs.form_id
LEFT JOIN departments d ON cs.department_id = d.department_id
WHERE cf.user_id = ?
GROUP BY cf.form_id;
```

---

## 🔒 SECURITY CHECKLIST

Before Production:
- [ ] Change default passwords
- [ ] Set strong DB credentials
- [ ] Configure HTTPS/SSL
- [ ] Review .env permissions (chmod 600)
- [ ] Enable firewall rules
- [ ] Setup backups
- [ ] Test disaster recovery
- [ ] Review audit logs
- [ ] Configure monitoring
- [ ] Create admin accounts

---

## ⏰ MAINTENANCE SCHEDULE

### Daily
- Monitor email queue
- Check error logs
- Verify backups completed

### Weekly
- Review audit logs
- Monitor performance metrics
- Update system

### Monthly
- Purge old notifications
- Archive audit logs
- Generate performance report
- Review department metrics

### Quarterly
- Database optimization
- Security audit
- Capacity planning
- Feature updates

---

## 📞 COMMON TASKS

### Add New Officer
```sql
INSERT INTO users (full_name, email, password, role, department_id, created_at)
VALUES ('John Officer', 'john@example.com', PASSWORD('password123'), 'officer', 2, NOW());
```

### Add New Department
```sql
INSERT INTO departments (department_name, email, phone, created_at)
VALUES ('Finance', 'finance@suza.ac.tz', '0654123456', NOW());
```

### Export Clearance Data
```sql
SELECT cf.form_id, u.registration_number, u.full_name, cf.status, 
       GROUP_CONCAT(d.department_name) as departments,
       cf.date_applied, cf.completed_at
INTO OUTFILE '/tmp/clearance_export.csv'
FIELDS TERMINATED BY ','
FROM clearance_forms cf
JOIN users u ON cf.user_id = u.user_id
LEFT JOIN clearance_status cs ON cf.form_id = cs.form_id
LEFT JOIN departments d ON cs.department_id = d.department_id
GROUP BY cf.form_id;
```

---

## 🎯 QUICK LINKS

| Purpose | Link | Role |
|---------|------|------|
| Submit Clearance | `/student/request_clearance.php` | Student |
| Check Status | `/student/status.php` | Student |
| View Pending | `/officer/pending_requests.php` | Officer |
| Admin Reports | `/admin/reports.php` | Admin |
| API Status | `/api/clearance.php` | API User |
| Notifications | `/api/notifications.php` | All Users |

---

## 💡 TIPS & TRICKS

1. **Bulk Status Update:**
   - Use pending_requests.php to approve multiple at once

2. **Track Processing:**
   - Check WorkflowEngine::calculateProgress() for real numbers

3. **Monitor Queue:**
   - Query email_queue table for processing status

4. **Optimize Reports:**
   - Use clearance_statistics table for cached data

5. **Improve Performance:**
   - Enable query caching in MySQL
   - Use APCu for notification counts

---

## 📱 Mobile Access

- API available for mobile apps
- JWT tokens for authentication (implement if needed)
- All endpoints return JSON
- CORS headers configurable

---

**Last Updated:** June 2026
**Version:** 2.0
**Status:** Production Ready

For detailed information, refer to DEPLOYMENT_GUIDE.md
