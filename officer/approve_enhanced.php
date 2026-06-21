<?php
/**
 * Enhanced Officer Approve Handler
 * Now includes notifications and certificate generation
 */

require_once __DIR__ . '/../auth.php';
require_role('officer');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../includes/NotificationService.php';
require_once __DIR__ . '/../includes/CertificateGenerator.php';
require_once __DIR__ . '/../includes/WorkflowEngine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['status_id'])) {
    header('Location: dashboard.php');
    exit;
}

$statusId = (int) $_POST['status_id'];
$comment = trim($_POST['comment'] ?? '');
$auditLogger = new AuditLogger($pdo, $_SESSION['user_id']);
$notificationService = new NotificationService($pdo);
$certificateGenerator = new CertificateGenerator($pdo);
$workflowEngine = new WorkflowEngine($pdo);

try {
    $pdo->beginTransaction();

    // Get clearance status record
    $stmt = $pdo->prepare('SELECT form_id, department_id, status FROM clearance_status WHERE status_id = ? AND department_id = ? LIMIT 1');
    $stmt->execute([$statusId, $_SESSION['department_id']]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status) {
        throw new Exception('Clearance request not found or not authorized.');
    }

    if ($status['status'] !== 'Pending') {
        throw new Exception('This clearance request has already been processed.');
    }

    // Check workflow sequence
    if (!$workflowEngine->canDepartmentApprove($status['form_id'], $status['department_id'])) {
        throw new Exception('Previous departments must approve before this department.');
    }

    // Get form and student data
    $stmt = $pdo->prepare('SELECT user_id FROM clearance_forms WHERE form_id = ?');
    $stmt->execute([$status['form_id']]);
    $formData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get department info
    $stmt = $pdo->prepare('SELECT department_name FROM departments WHERE department_id = ?');
    $stmt->execute([$status['department_id']]);
    $departmentName = $stmt->fetchColumn();

    // Update clearance status
    $stmt = $pdo->prepare('
        UPDATE clearance_status 
        SET status = ?, officer_id = ?, comment = ?, approval_date = NOW(), updated_at = NOW()
        WHERE status_id = ?
    ');
    $stmt->execute(['Approved', $_SESSION['user_id'], $comment, $statusId]);

    // Calculate overall form status
    $stmt = $pdo->prepare('
        SELECT COUNT(*) AS total, 
               SUM(status = "Approved") AS approved, 
               SUM(status = "Rejected") AS rejected 
        FROM clearance_status 
        WHERE form_id = ?
    ');
    $stmt->execute([$status['form_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats['rejected'] > 0) {
        $overall = 'Rejected';
    } elseif ($stats['approved'] == $stats['total']) {
        $overall = 'Completed';
        $completedAt = date('Y-m-d');
    } else {
        $overall = 'In Progress';
        $completedAt = null;
    }

    // Update form status
    $stmt = $pdo->prepare('UPDATE clearance_forms SET status = ?, completed_at = ?, updated_at = NOW() WHERE form_id = ?');
    $stmt->execute([$overall, $completedAt ?? null, $status['form_id']]);

    // Log audit
    $auditLogger->logClearanceApproval(
        $statusId,
        $status['form_id'],
        $status['department_id'],
        'Approved',
        $comment
    );

    // Send notification to student
    $notificationService->notifyApproval(
        $formData['user_id'],
        $status['form_id'],
        $statusId,
        $departmentName,
        $status['department_id'],
        $_SESSION['user_id'],
        $_SESSION['full_name'],
        $comment
    );

    // If all approved, generate certificate
    if ($overall === 'Completed') {
        $certResult = $certificateGenerator->generateCertificate($status['form_id']);
        if ($certResult['success']) {
            $notificationService->notifyCompletion(
                $formData['user_id'],
                $status['form_id'],
                $certResult['clearance_number']
            );
        }
    }

    $pdo->commit();
    $_SESSION['success'] = 'Clearance approved successfully. Student has been notified.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
    $auditLogger->log('CLEARANCE_APPROVAL_FAILED', $e->getMessage(), 'clearance_status', $statusId);
}

header('Location: dashboard.php');
exit;
