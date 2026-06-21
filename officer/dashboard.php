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

$stmt = $pdo->prepare('SELECT cs.status_id, cs.status AS clearance_status, cs.comment, cs.updated_at AS last_updated,
    f.form_id, f.academic_session AS academic_year, f.date_applied, f.status AS overall_status,
    u.full_name AS student_name, u.registration_number
    FROM clearance_status cs
    JOIN clearance_forms f ON cs.form_id = f.form_id
    JOIN users u ON f.user_id = u.user_id
    WHERE cs.department_id = ?
    ORDER BY cs.status = "Pending" DESC, cs.updated_at DESC');
$stmt->execute([$departmentId]);
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body style="font-family: Inter, sans-serif; background:#f3f7fb;">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1">Officer Dashboard</h1>
                <p class="text-muted mb-0">Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?>.</p>
                <p class="text-muted small">Department: <?= htmlspecialchars($department ?: 'Unassigned'); ?></p>
            </div>
            <div>
                <a href="../logout.php" class="btn btn-outline-secondary">Logout</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Clearance Requests for <?= htmlspecialchars($department ?: 'Your Department'); ?></h5>
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
                                                <form action="approve.php" method="post" class="d-inline">
                                                    <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                </form>
                                                <form action="reject.php" method="post" class="d-inline ms-1">
                                                    <input type="hidden" name="status_id" value="<?= $request['status_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Processed</span>
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
</body>
</html>
