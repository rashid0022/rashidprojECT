<?php

/**
 * Audit Logger Class
 * Handles all audit logging for the clearance system
 */

class AuditLogger {
    private $pdo;
    private $userId;
    private $ipAddress;

    public function __construct($pdo, $userId = null) {
        $this->pdo = $pdo;
        $this->userId = $userId ?? ($_SESSION['user_id'] ?? null);
        $this->ipAddress = $this->getClientIp();
    }

    /**
     * Log an action to the audit table
     */
    public function log($action, $details = null, $entityType = null, $entityId = null, $oldValue = null, $newValue = null) {
        try {
            $stmt = $this->pdo->prepare('
                INSERT INTO audit_logs (
                    user_id, action, details, ip_address, entity_type, entity_id, old_value, new_value, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ');

            $stmt->execute([
                $this->userId,
                $action,
                $details,
                $this->ipAddress,
                $entityType,
                $entityId,
                $oldValue ? json_encode($oldValue) : null,
                $newValue ? json_encode($newValue) : null
            ]);

            return true;
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            // Fallback: some installations may not have the enhanced audit columns (entity_type, entity_id, old_value, new_value)
            // Try a minimal insert that only uses the original/basic columns.
            try {
                $fallback = $this->pdo->prepare('
                    INSERT INTO audit_logs (user_id, action, details, ip_address, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ');
                $fallback->execute([
                    $this->userId,
                    $action,
                    $details,
                    $this->ipAddress
                ]);
                return true;
            } catch (Exception $e2) {
                error_log("Audit fallback failed: " . $e2->getMessage());
                return false;
            }
        }
    }

    /**
     * Log clearance status change
     */
    public function logClearanceApproval($statusId, $formId, $departmentId, $decision, $comment = null) {
        return $this->log(
            "CLEARANCE_" . strtoupper($decision),
            $comment,
            "clearance_status",
            $statusId,
            null,
            [
                "form_id" => $formId,
                "department_id" => $departmentId,
                "status" => $decision,
                "timestamp" => date('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * Log certificate generation
     */
    public function logCertificateGeneration($certificateId, $formId, $clearanceNumber) {
        return $this->log(
            "CERTIFICATE_GENERATED",
            "Certificate created for clearance process",
            "clearance_certificates",
            $certificateId,
            null,
            [
                "form_id" => $formId,
                "clearance_number" => $clearanceNumber,
                "generated_at" => date('Y-m-d H:i:s')
            ]
        );
    }

    /**
     * Log certificate verification
     */
    public function logCertificateVerification($certificateId, $verified = true) {
        return $this->log(
            $verified ? "CERTIFICATE_VERIFIED" : "CERTIFICATE_VERIFICATION_FAILED",
            "QR code or document verification attempt",
            "clearance_certificates",
            $certificateId
        );
    }

    /**
     * Get recent audit logs
     */
    public function getRecentLogs($limit = 100, $entityType = null) {
        $query = 'SELECT * FROM audit_logs';
        $params = [];

        if ($entityType) {
            $query .= ' WHERE entity_type = ?';
            $params[] = $entityType;
        }

        $query .= ' ORDER BY created_at DESC LIMIT ?';
        $params[] = $limit;

        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get audit logs for a specific entity
     */
    public function getEntityHistory($entityType, $entityId) {
        $stmt = $this->pdo->prepare('
            SELECT * FROM audit_logs 
            WHERE entity_type = ? AND entity_id = ?
            ORDER BY created_at DESC
        ');
        $stmt->execute([$entityType, $entityId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get client IP address
     */
    private function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
    }

    /**
     * Generate audit report
     */
    public function generateAuditReport($startDate, $endDate, $userId = null, $action = null) {
        $query = 'SELECT * FROM audit_logs WHERE created_at BETWEEN ? AND ?';
        $params = [$startDate, $endDate];

        if ($userId) {
            $query .= ' AND user_id = ?';
            $params[] = $userId;
        }

        if ($action) {
            $query .= ' AND action = ?';
            $params[] = $action;
        }

        $query .= ' ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Purge old audit logs (keep last N days)
     */
    public function purgeOldLogs($daysToKeep = 90) {
        $stmt = $this->pdo->prepare('
            DELETE FROM audit_logs 
            WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ');
        return $stmt->execute([$daysToKeep]);
    }
}
