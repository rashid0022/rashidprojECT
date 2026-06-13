<?php
require_once __DIR__ . '/../auth.php';
require_role('officer');
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['status_id'])) {
    header('Location: dashboard.php');
    exit;
}

$statusId = (int) $_POST['status_id'];
$comment = trim($_POST['comment'] ?? '');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT form_id, department_id, status FROM clearance_status WHERE status_id = ? AND department_id = ? LIMIT 1');
    $stmt->execute([$statusId, $_SESSION['department_id']]);
    $status = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$status) {
        throw new Exception('Clearance request not found or not authorized.');
    }

    if ($status['status'] !== 'Pending') {
        throw new Exception('This clearance request has already been processed.');
    }

    $update = $pdo->prepare('UPDATE clearance_status SET status = ?, officer_id = ?, comment = ?, last_updated = NOW() WHERE status_id = ?');
    $update->execute(['Rejected', $_SESSION['user_id'], $comment, $statusId]);

    $stmt = $pdo->prepare('SELECT COUNT(*) AS total, SUM(status = "Approved") AS approved, SUM(status = "Rejected") AS rejected FROM clearance_status WHERE form_id = ?');
    $stmt->execute([$status['form_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stats['rejected'] > 0) {
        $overall = 'Rejected';
    } elseif ($stats['approved'] == $stats['total']) {
        $overall = 'Completed';
    } else {
        $overall = 'In Progress';
    }

    $completedAt = $overall === 'Completed' ? date('Y-m-d') : null;
    $updateForm = $pdo->prepare('UPDATE clearance_forms SET status = ?, completed_at = ?, updated_at = NOW() WHERE form_id = ?');
    $updateForm->execute([$overall, $completedAt, $status['form_id']]);

    $pdo->commit();
    $_SESSION['success'] = 'Clearance rejected successfully.';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: dashboard.php');
exit;
