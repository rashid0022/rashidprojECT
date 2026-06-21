<?php

/**
 * Notification Service Class
 * Handles all notification operations (database and queue)
 */

class NotificationService {
    private $pdo;
    private $userId;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Create and send a notification
     */
    public function notify($userId, $type, $title, $message, $formId = null, $statusId = null, $fromDepartmentId = null, $fromOfficerId = null, $actionUrl = null) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO notifications (
                    user_id, form_id, status_id, type, title, message, 
                    from_department_id, from_officer_id, action_url, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $result = $stmt->execute([
                $userId,
                $formId,
                $statusId,
                $type,
                $title,
                $message,
                $fromDepartmentId,
                $fromOfficerId,
                $actionUrl
            ]);

            // Also queue an email notification
            if ($result) {
                $this->queueEmailNotification($userId, $type, $title, $message);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Notify clearance approval
     */
    public function notifyApproval($userId, $formId, $statusId, $departmentName, $departmentId, $officerId, $officerName, $comment = null) {
        $title = $departmentName . " Approval";
        $message = "Your clearance request has been approved by " . $departmentName . " department.";
        if ($comment) {
            $message .= "\n\nComment: " . $comment;
        }

        return $this->notify(
            $userId,
            'approval',
            $title,
            $message,
            $formId,
            $statusId,
            $departmentId,
            $officerId,
            "student/status.php?form_id=" . $formId
        );
    }

    /**
     * Notify clearance rejection
     */
    public function notifyRejection($userId, $formId, $statusId, $departmentName, $departmentId, $officerId, $officerName, $reason = null) {
        $title = $departmentName . " Rejection";
        $message = "Your clearance request has been rejected by " . $departmentName . " department.";
        if ($reason) {
            $message .= "\n\nReason: " . $reason;
        }

        return $this->notify(
            $userId,
            'rejection',
            $title,
            $message,
            $formId,
            $statusId,
            $departmentId,
            $officerId,
            "student/status.php?form_id=" . $formId
        );
    }

    /**
     * Notify clearance completion
     */
    public function notifyCompletion($userId, $formId, $clearanceNumber = null) {
        $title = "Clearance Completed";
        $message = "Congratulations! Your clearance request has been approved by all departments. ";
        if ($clearanceNumber) {
            $message .= "Your clearance certificate number is: " . $clearanceNumber;
        }

        return $this->notify(
            $userId,
            'completion',
            $title,
            $message,
            $formId,
            null,
            null,
            null,
            "student/certificate.php?form_id=" . $formId
        );
    }

    /**
     * Queue email notification
     */
    private function queueEmailNotification($userId, $type, $title, $message) {
        try {
            // Get user email
            $stmt = $this->pdo->prepare('SELECT email FROM users WHERE user_id = ?');
            $stmt->execute([$userId]);
            $email = $stmt->fetchColumn();

            if (!$email) return false;

            // Queue email
            $stmt = $this->pdo->prepare('
                INSERT INTO email_queue (recipient_email, recipient_user_id, subject, body, template_name, status)
                VALUES (?, ?, ?, ?, ?, "pending")
            ');

            return $stmt->execute([$email, $userId, $title, $message, "clearance_" . $type]);
        } catch (Exception $e) {
            error_log("Email queue failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user notifications
     */
    public function getUserNotifications($userId, $unreadOnly = false, $limit = 50) {
        $query = 'SELECT * FROM notifications WHERE user_id = ?';
        $params = [$userId];

        if ($unreadOnly) {
            $query .= ' AND is_read = 0';
        }

        $query .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notificationId, $userId = null) {
        $query = 'UPDATE notifications SET is_read = 1, read_at = NOW() WHERE notification_id = ?';
        $params = [$notificationId];

        if ($userId) {
            $query .= ' AND user_id = ?';
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare('
            UPDATE notifications SET is_read = 1, read_at = NOW()
            WHERE user_id = ? AND is_read = 0
        ');
        return $stmt->execute([$userId]);
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }

    /**
     * Delete old notifications
     */
    public function purgeOldNotifications($daysToKeep = 30) {
        $stmt = $this->pdo->prepare('
            DELETE FROM notifications 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ');
        return $stmt->execute([$daysToKeep]);
    }

    /**
     * Get notification statistics
     */
    public function getNotificationStats($userId = null, $type = null) {
        $query = 'SELECT COUNT(*) as total, SUM(is_read = 1) as read_count FROM notifications WHERE 1=1';
        $params = [];

        if ($userId) {
            $query .= ' AND user_id = ?';
            $params[] = $userId;
        }

        if ($type) {
            $query .= ' AND type = ?';
            $params[] = $type;
        }

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
