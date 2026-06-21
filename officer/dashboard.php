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

// Fetch all requests for this department (most recent first)
$stmt = $pdo->prepare('SELECT cs.status_id, cs.status AS clearance_status, cs.comment, cs.updated_at AS last_updated,
    f.form_id, f.academic_session AS academic_year, f.date_applied, f.status AS overall_status,
    u.full_name AS student_name, u.registration_number, u.email
    FROM clearance_status cs
    JOIN clearance_forms f ON cs.form_id = f.form_id
    JOIN users u ON f.user_id = u.user_id
    WHERE cs.department_id = ?
    ORDER BY cs.status = "Pending" DESC, cs.updated_at DESC');
$stmt->execute([$departmentId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard | Clearance Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fb; }
        .card { border: none; border-radius: 1rem; box-shadow: 0 10px 30px rgba(15,23,42,0.06); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 fw-bold mb-0">Officer Dashboard</h1>
                <p class="text-muted mb-0"><i class="bi bi-building me-1"></i> <?= htmlspecialchars($department ?: 'Your Department'); ?></p>
            </div>
            <div>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card p-3">
            <div class="card-body">
                <h5 class="card-title">Clearance Requests</h5>
                <?php if (empty($requests)): ?>
                    <p class="text-muted">No clearance requests have been assigned to your department yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student</th>
                                    <th>Reg No.</th>
                                    <th>Academic Year</th>
                                    <th>Request Date</th>
                                    <th>Clearance Status</th>
                                    <th>Form Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $index => $request): ?>
                                    <tr>
                                        <td><?= $index + 1; ?></td>
                                        <td><?= htmlspecialchars($request['student_name']); ?></td>
                                        <td><?= htmlspecialchars($request['registration_number']); ?></td>
                                        <td><?= htmlspecialchars($request['academic_year']); ?></td>
                                        <td><?= htmlspecialchars($request['date_applied']); ?></td>
                                        <td><?= htmlspecialchars($request['clearance_status']); ?></td>
                                        <td><?= htmlspecialchars($request['overall_status']); ?></td>
                                        <td>
                                            <?php if ($request['clearance_status'] === 'Pending'): ?>
                                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approveModal<?= $request['status_id']; ?>">Approve</button>
                                                <button type="button" class="btn btn-sm btn-danger ms-1" data-bs-toggle="modal" data-bs-target="#rejectModal<?= $request['status_id']; ?>">Reject</button>

                                                <!-- Approve Modal -->
                                                <div class="modal fade" id="approveModal<?= $request['status_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-success text-white">
                                                                <h5 class="modal-title">Approve Clearance</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="approve_enhanced.php" method="post">
                                                                <div class="modal-body">
                                                                    <p class="text-muted small mb-3">Student: <strong><?= htmlspecialchars($request['student_name']); ?></strong></p>
                                                                    <label class="form-label">Comment (Optional)</label>
                                                                    <textarea name="comment" class="form-control" placeholder="Add a note for the student..." rows="3"></textarea>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-success">Approve</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Reject Modal -->
                                                <div class="modal fade" id="rejectModal<?= $request['status_id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header bg-danger text-white">
                                                                <h5 class="modal-title">Reject Clearance</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="reject.php" method="post">
                                                                <div class="modal-body">
                                                                    <p class="text-muted small mb-3">Student: <strong><?= htmlspecialchars($request['student_name']); ?></strong></p>
                                                                    <label class="form-label">Reason for Rejection</label>
                                                                    <textarea name="comment" class="form-control" placeholder="Explain why..." rows="3" required></textarea>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
