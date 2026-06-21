<?php
/**
 * API Endpoint: Clearance Status
 * GET /api/clearance.php?form_id=123
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/WorkflowEngine.php';

$workflowEngine = new WorkflowEngine($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$formId = (int) ($_GET['form_id'] ?? 0);
$userId = $_SESSION['user_id'] ?? null;

if (!$formId) {
    http_response_code(400);
    echo json_encode(['error' => 'Form ID required']);
    exit;
}

try {
    // Verify user can access this form
    $stmt = $pdo->prepare('SELECT user_id FROM clearance_forms WHERE form_id = ?');
    $stmt->execute([$formId]);
    $form = $stmt->fetch();

    if (!$form || ($userId && $form['user_id'] != $userId && $_SESSION['role'] !== 'admin')) {
        http_response_code(403);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }

    // Get form data
    $stmt = $pdo->prepare('
        SELECT f.*, u.full_name, u.registration_number
        FROM clearance_forms f
        JOIN users u ON f.user_id = u.user_id
        WHERE f.form_id = ?
    ');
    $stmt->execute([$formId]);
    $formData = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get approval statuses
    $completedApprovals = $workflowEngine->getCompletedApprovals($formId);
    $pendingApprovals = $workflowEngine->getPendingApprovals($formId);

    // Calculate progress
    $progress = $workflowEngine->calculateProgress($formId);
    $allApproved = $workflowEngine->allDepartmentsApproved($formId);
    $hasRejection = $workflowEngine->hasAnyRejection($formId);

    echo json_encode([
        'success' => true,
        'form' => $formData,
        'approvals' => [
            'completed' => $completedApprovals,
            'pending' => $pendingApprovals
        ],
        'progress' => $progress,
        'status' => [
            'all_approved' => $allApproved,
            'has_rejection' => $hasRejection,
            'overall' => $formData['status']
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
