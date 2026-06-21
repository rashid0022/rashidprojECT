<?php

/**
 * Clearance Workflow Engine
 * Manages the multi-level approval workflow
 */

class WorkflowEngine {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get next pending department in workflow
     */
    public function getNextPendingDepartment($formId) {
        $stmt = $this->pdo->prepare('
            SELECT cs.*, d.department_name, cw.allow_parallel
            FROM clearance_status cs
            JOIN departments d ON cs.department_id = d.department_id
            LEFT JOIN clearance_workflow cw ON d.department_id = cw.department_id
            WHERE cs.form_id = ? AND cs.status = "Pending"
            ORDER BY cs.sequence_order ASC
            LIMIT 1
        ');
        $stmt->execute([$formId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all pending approvals for a form
     */
    public function getPendingApprovals($formId) {
        $stmt = $this->pdo->prepare('
            SELECT cs.*, d.department_name
            FROM clearance_status cs
            JOIN departments d ON cs.department_id = d.department_id
            WHERE cs.form_id = ? AND cs.status = "Pending"
            ORDER BY cs.sequence_order ASC
        ');
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get completed approvals
     */
    public function getCompletedApprovals($formId) {
        $stmt = $this->pdo->prepare('
            SELECT cs.*, d.department_name, u.full_name as officer_name
            FROM clearance_status cs
            JOIN departments d ON cs.department_id = d.department_id
            LEFT JOIN users u ON cs.officer_id = u.user_id
            WHERE cs.form_id = ? AND cs.status != "Pending"
            ORDER BY cs.sequence_order ASC
        ');
        $stmt->execute([$formId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if all departments have approved
     */
    public function allDepartmentsApproved($formId) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as total, 
                   SUM(status = "Approved") as approved,
                   SUM(status = "Rejected") as rejected
            FROM clearance_status
            WHERE form_id = ?
        ');
        $stmt->execute([$formId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['total'] > 0 
            && $result['total'] == $result['approved'] 
            && $result['rejected'] == 0;
    }

    /**
     * Check if any department has rejected
     */
    public function hasAnyRejection($formId) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM clearance_status
            WHERE form_id = ? AND status = "Rejected"
        ');
        $stmt->execute([$formId]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Calculate workflow progress
     */
    public function calculateProgress($formId) {
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) as total,
                   SUM(status != "Pending") as completed
            FROM clearance_status
            WHERE form_id = ?
        ');
        $stmt->execute([$formId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] == 0) return 0;
        return round(($result['completed'] / $result['total']) * 100);
    }

    /**
     * Get estimated completion time
     */
    public function getEstimatedCompletionTime($formId) {
        $stmt = $this->pdo->prepare('
            SELECT SUM(cw.time_limit_days) as total_days
            FROM clearance_status cs
            JOIN clearance_workflow cw ON cs.department_id = cw.department_id
            WHERE cs.form_id = ? AND cs.status = "Pending"
        ');
        $stmt->execute([$formId]);
        $days = $stmt->fetchColumn();
        
        if ($days) {
            return date('Y-m-d', strtotime("+$days days"));
        }
        return null;
    }

    /**
     * Get workflow statistics
     */
    public function getWorkflowStats() {
        $stmt = $this->pdo->prepare('
            SELECT 
                d.department_name,
                COUNT(cs.status_id) as total_requests,
                SUM(cs.status = "Approved") as approved,
                SUM(cs.status = "Rejected") as rejected,
                SUM(cs.status = "Pending") as pending,
                AVG(DATEDIFF(cs.approval_date, cs.created_at)) as avg_processing_days
            FROM clearance_status cs
            JOIN departments d ON cs.department_id = d.department_id
            WHERE cs.approval_date IS NOT NULL
            GROUP BY cs.department_id
            ORDER BY d.department_name
        ');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Check if department can approve based on sequence
     */
    public function canDepartmentApprove($formId, $departmentId) {
        // Get department sequence
        $stmt = $this->pdo->prepare('
            SELECT sequence_order FROM clearance_workflow
            WHERE department_id = ?
        ');
        $stmt->execute([$departmentId]);
        $deptSequence = $stmt->fetchColumn();

        if (!$deptSequence) {
            return true; // No workflow configured, allow approval
        }

        // Check if all previous departments have approved
        $stmt = $this->pdo->prepare('
            SELECT COUNT(*) FROM clearance_status cs
            JOIN clearance_workflow cw ON cs.department_id = cw.department_id
            WHERE cs.form_id = ? 
            AND cw.sequence_order < ?
            AND cs.status != "Approved"
        ');
        $stmt->execute([$formId, $deptSequence]);
        
        return $stmt->fetchColumn() == 0;
    }

    /**
     * Get pending approvals for officer
     */
    public function getOfficerPendingApprovals($officerId) {
        $stmt = $this->pdo->prepare('
            SELECT u.department_id, COUNT(cs.status_id) as pending_count
            FROM users u
            LEFT JOIN clearance_status cs ON u.department_id = cs.department_id 
                AND cs.status = "Pending"
            WHERE u.user_id = ?
            GROUP BY u.department_id
        ');
        $stmt->execute([$officerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
