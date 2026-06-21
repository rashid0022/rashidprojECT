<?php
require_once __DIR__ . '/../auth.php';
require_role('officer');
require_once __DIR__ . '/../config/database.php';

$departmentId = $_SESSION['department_id'] ?? null;
if ($departmentId === null) {
    $_SESSION['error'] = 'Your account is not assigned to a department.';
    header('Location: ../logout.php');
    exit;
}

$stmt = $pdo->prepare('SELECT department_name FROM departments WHERE department_id = ? LIMIT 1');
$stmt->execute([$departmentId]);
$department = $stmt->fetchColumn();

// Fetch ONLY pending requests for this department
$stmt = $pdo->prepare('SELECT cs.status_id, cs.status AS clearance_status, cs.comment, cs.created_at,
    f.form_id, f.academic_session AS academic_year, f.date_applied, f.status AS overall_status,
    u.full_name AS student_name, u.registration_number, u.email
    FROM clearance_status cs
    JOIN clearance_forms f ON cs.form_id = f.form_id
    JOIN users u ON f.user_id = u.user_id
    WHERE cs.department_id = ? AND cs.status = "Pending"
    ORDER BY cs.created_at ASC');
$stmt->execute([$departmentId]);
$pendingRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Clearance Requests | Officer Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fb; }
        .card { border: none; border-radius: 1.25rem; box-shadow: 0 12px 32px rgba(15,23,42,0.08); }
        .badge-pending { background: #fff3cd; color: #856404; }
        .request-card { transition: all 0.2s; }
        .request-card:hover { transform: translateY(-2px); box-shadow: 0 20px 40px rgba(15,23,42,0.12) !important; }
    </style>
</head>
<body>
    <div class="container py-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h1 class="h3 fw-bold mb-1">Pending Clearance Requests</h1>
                <p class="text-muted mb-0">
                    <i class="bi bi-building me-1"></i> <?= htmlspecialchars($department ?: 'Your Department'); ?>
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm rounded-pill">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i> <?= htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i> <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Count Badge -->
        <div class="mb-4">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">
                <i class="bi bi-hourglass-split me-1"></i> <?= count($pendingRequests); ?> request(s) awaiting review
            </span>
        </div>

        <!-- Pending Requests Grid -->
        <?php if (empty($pendingRequests)): ?>
            <div class="card p-5 text-center">
                <i class="bi bi-inbox-fill text-muted mb-3" style="font-size: 3rem;"></i>
                <h3 class="h5 fw-semibold">No Pending Requests</h3>
                <p class="text-muted mb-4">All clearance requests for your department have been processed.</p>
                <a href="dashboard.php" class="btn btn-primary rounded-pill px-4">View All Requests</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($pendingRequests as $request): ?>
                    <div class="col-lg-6">
                        <div class="card request-card p-4">
                            <!-- Student Info -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($request['student_name']); ?></h5>
                                        <p class="text-muted small mb-0">Reg: <?= htmlspecialchars($request['registration_number']); ?></p>
                                    </div>
                                    <span class="badge badge-pending">Pending</span>
                                </div>
                                <p class="text-muted small mb-2">
                                    <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($request['email']); ?>
                                </p>
                            </div>

                            <hr>

                            <!-- Request Details -->
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="text-muted small">Academic Year</label>
                                    <p class="fw-semibold mb-0"><?= htmlspecialchars($request['academic_year']); ?></p>
                                </div>
                                <div class="col-6">
                                    <label class="text-muted small">Request Date</label>
                                    <p class="fw-semibold mb-0"><?= htmlspecialchars($request['date_applied']); ?></p>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2 pt-2">
                                <button type="button" class="btn btn-success rounded-pill" data-bs-toggle="modal" data-bs-target="#approveModal<?= $request['status_id']; ?>">
                                    <i class="bi bi-check-circle me-1"></i> Approve
                                </button>
                                <button type="button" class="btn btn-danger rounded-pill" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $request['status_id']; ?>">
                                    <i class="bi bi-x-circle me-1"></i> Reject
                                </button>
                            </div>

                            <!-- Approve Modal -->
                            <div class="modal fade" id="approveModal<?= $request['status_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-success text-white border-0">
                                            <h5 class="modal-title">Approve Clearance</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="approve_enhanced.php" method="post">
                                            <div class="modal-body">
                                                <p class="text-muted small mb-3">
                                                    <strong>Student:</strong> <?= htmlspecialchars($request['student_name']); ?>
                                                </p>
                                                <label class="form-label fw-semibold">Comment (Optional)</label>
                                                <textarea name="comment" class="form-control rounded-3" placeholder="Add any notes..." rows="3"></textarea>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success rounded-pill px-4">
                                                    <i class="bi bi-check me-1"></i> Approve
                                                </button>
                                                <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Reject Modal -->
                            <div class="modal fade" id="rejectModal<?= $request['status_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white border-0">
                                            <h5 class="modal-title">Reject Clearance</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form action="reject.php" method="post">
                                            <div class="modal-body">
                                                <p class="text-muted small mb-3">
                                                    <strong>Student:</strong> <?= htmlspecialchars($request['student_name']); ?>
                                                </p>
                                                <label class="form-label fw-semibold">Reason for Rejection</label>
                                                <textarea name="comment" class="form-control rounded-3" placeholder="Explain why..." rows="3" required></textarea>
                                            </div>
                                            <div class="modal-footer border-0">
                                                <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger rounded-pill px-4">
                                                    <i class="bi bi-x me-1"></i> Reject
                                                </button>
                                                <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
