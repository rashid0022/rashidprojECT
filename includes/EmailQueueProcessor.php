<?php

/**
 * Email Queue Processor
 * Processes and sends emails from the email queue
 * Should be run via cron job: php email_processor.php
 */

class EmailQueueProcessor {
    private $pdo;
    private $maxRetries = 3;
    private $batchSize = 50;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Process pending emails
     */
    public function processPendingEmails() {
        try {
            $emails = $this->getPendingEmails();

            foreach ($emails as $email) {
                $this->sendEmail($email);
            }

            return count($emails);
        } catch (Exception $e) {
            error_log("Email processing failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get pending emails from queue
     */
    private function getPendingEmails() {
        $stmt = $this->pdo->prepare('
            SELECT * FROM email_queue 
            WHERE status = "pending" AND retry_count < ?
            ORDER BY created_at ASC
            LIMIT ?
        ');
        $stmt->execute([$this->maxRetries, $this->batchSize]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Send individual email
     */
    private function sendEmail($emailData) {
        try {
            $success = mail(
                $emailData['recipient_email'],
                $emailData['subject'],
                $this->buildEmailBody($emailData),
                $this->getEmailHeaders()
            );

            if ($success) {
                $this->markAsSent($emailData['email_id']);
            } else {
                $this->incrementRetry($emailData['email_id'], 'SMTP delivery failed');
            }
        } catch (Exception $e) {
            $this->incrementRetry($emailData['email_id'], $e->getMessage());
        }
    }

    /**
     * Build email body from template
     */
    private function buildEmailBody($emailData) {
        $body = $emailData['body'];

        // Simple template rendering
        if ($emailData['template_data']) {
            $templateData = json_decode($emailData['template_data'], true);
            foreach ($templateData as $key => $value) {
                $body = str_replace('{{' . $key . '}}', $value, $body);
            }
        }

        return $body;
    }

    /**
     * Get email headers
     */
    private function getEmailHeaders() {
        $appEmail = $this->getSystemSetting('app_email', 'noreply@suza.ac.tz');
        $appName = $this->getSystemSetting('app_name', 'SUZA Clearance System');

        return "From: " . $appName . " <" . $appEmail . ">\r\n"
             . "Reply-To: " . $appEmail . "\r\n"
             . "Content-Type: text/html; charset=UTF-8\r\n"
             . "X-Mailer: SUZA Clearance System";
    }

    /**
     * Mark email as sent
     */
    private function markAsSent($emailId) {
        $stmt = $this->pdo->prepare('
            UPDATE email_queue 
            SET status = "sent", sent_at = NOW()
            WHERE email_id = ?
        ');
        return $stmt->execute([$emailId]);
    }

    /**
     * Increment retry count
     */
    private function incrementRetry($emailId, $error) {
        $stmt = $this->pdo->prepare('
            UPDATE email_queue 
            SET retry_count = retry_count + 1, last_error = ?
            WHERE email_id = ?
        ');
        return $stmt->execute([$error, $emailId]);
    }

    /**
     * Get system setting
     */
    private function getSystemSetting($key, $default = null) {
        try {
            $stmt = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ?');
            $stmt->execute([$key]);
            $value = $stmt->fetchColumn();
            return $value ?? $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStats() {
        $stmt = $this->pdo->prepare('
            SELECT 
                status,
                COUNT(*) as count
            FROM email_queue
            GROUP BY status
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Purge failed emails older than X days
     */
    public function purgeFailedEmails($daysOld = 30) {
        $stmt = $this->pdo->prepare('
            DELETE FROM email_queue
            WHERE status = "failed" AND created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ');
        return $stmt->execute([$daysOld]);
    }
}

// Run processor if called directly
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/../config/database.php';
    $processor = new EmailQueueProcessor($pdo);
    $count = $processor->processPendingEmails();
    echo "Processed " . $count . " emails\n";
}
