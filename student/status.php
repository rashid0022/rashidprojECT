<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$stmt = $pdo->prepare('SELECT f.form_id, f.academic_session AS academic_year, f.date_applied, f.status AS overall_status,
    GROUP_CONCAT(CONCAT(d.department_name, ": ", cs.status) ORDER BY d.department_name SEPARATOR " | ") AS department_statuses
    FROM clearance_forms f
    JOIN clearance_status cs ON f.form_id = cs.form_id
    JOIN departments d ON cs.department_id = d.department_id
    WHERE f.user_id = ?
    GROUP BY f.form_id
    ORDER BY f.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

function getStatusClass($status) {
    switch (strtolower(trim($status))) {
        case 'completed':
            return 'bg-success bg-opacity-15 text-success border-0';
        case 'pending':
            return 'bg-warning bg-opacity-15 text-warning-emphasis border-0';
        case 'rejected':
            return 'bg-danger bg-opacity-15 text-danger border-0';
        case 'in progress':
            return 'bg-info bg-opacity-15 text-info border-0';
        default:
            return 'bg-secondary bg-opacity-10 text-secondary border-0';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Status | ClearFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fb; color: #0f172a; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 12px 32px rgba(15,23,42,0.08); }
        .badge-pill { border-radius: 999px; }
        .status-pill { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.45rem 0.9rem; border-radius: 999px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-xl-10">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
                    <div>
                        <h1 class="h3 fw-bold mb-1">Clearance Status</h1>
                        <p class="text-muted mb-0">Track your clearance requests and department sign-offs in one place.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                        </a>
                        <a href="request_clearance.php" class="btn btn-primary btn-sm rounded-pill">
                            <i class="bi bi-plus-circle me-1"></i> New Clearance
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (empty($forms)): ?>
                    <div class="card p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-inbox-fill text-muted" style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="h5 fw-semibold">No clearance requests found</h2>
                        <p class="text-muted mb-4">Start by submitting a clearance request and check back here for department updates.</p>
                        <a href="request_clearance.php" class="btn btn-primary rounded-pill px-4">Submit Request</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($forms as $form):
                        $deptEntries = [];
                        $rawDeptStatuses = $form['department_statuses'] ?? '';
                        $deptItems = explode('|', $rawDeptStatuses);
                        foreach ($deptItems as $deptItem) {
                            if (strpos($deptItem, ':') !== false) {
                                list($dname, $dstatus) = explode(':', $deptItem, 2);
                                $deptEntries[] = ['name' => trim($dname), 'status' => trim($dstatus)];
                            }
                        }
                        $appliedDate = new DateTime($form['date_applied']);
                        $formattedDate = $appliedDate->format('F j, Y');
                    ?>
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                    <div>
                                        <h5 class="fw-semibold mb-1">Request #CLR-<?= str_pad($form['form_id'], 4, '0', STR_PAD_LEFT); ?></h5>
                                        <div class="text-muted small">Academic year: <?= htmlspecialchars($form['academic_year']); ?> · Applied: <?= $formattedDate; ?></div>
                                    </div>
                                    <span class="badge <?= getStatusClass($form['overall_status']); ?> py-2 px-3"><?= htmlspecialchars($form['overall_status']); ?></span>
                                </div>
                                <hr>
                                <div class="row g-3">
                                    <?php foreach ($deptEntries as $dept):
                                        $status = strtolower($dept['status']);
                                        $badgeClass = $status === 'approved' ? 'bg-success text-white' : ($status === 'pending' ? 'bg-warning text-dark' : 'bg-danger text-white');
                                    ?>
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-4 border border-1 border-light">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="fw-semibold"><?= htmlspecialchars($dept['name']); ?></div>
                                                    <span class="status-pill <?= $badgeClass; ?>"><?= htmlspecialchars($dept['status']); ?></span>
                                                </div>
                                                <p class="small text-muted mb-0">Department processing status for this clearance request.</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
