<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare('SELECT f.form_id, f.academic_year, f.date_applied, f.status AS overall_status,
    GROUP_CONCAT(CONCAT(d.department_name, ": ", cs.status) ORDER BY d.department_name SEPARATOR " | ") AS department_statuses
    FROM clearance_forms f
    JOIN clearance_status cs ON f.form_id = cs.form_id
    JOIN departments d ON cs.department_id = d.department_id
    WHERE f.student_id = ?
    GROUP BY f.form_id
    ORDER BY f.created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body style="font-family: Inter, sans-serif; background:#f3f7fb;">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1">Student Dashboard</h1>
                <p class="text-muted mb-0">Welcome, <?= htmlspecialchars($_SESSION['full_name']); ?>.</p>
                <p class="text-muted small">Role: <?= htmlspecialchars($_SESSION['role']); ?></p>
            </div>
            <div>
                <a href="request_clearance.php" class="btn btn-primary me-2">Submit Clearance Request</a>
                <a href="../logout.php" class="btn btn-outline-secondary">Logout</a>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h5 class="card-title">Your Clearance Requests</h5>
                <?php if (empty($forms)): ?>
                    <p class="text-muted">You have not submitted any clearance request yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Academic Year</th>
                                    <th>Date Applied</th>
                                    <th>Overall Status</th>
                                    <th>Department Statuses</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($forms as $index => $form): ?>
                                    <tr>
                                        <td><?= $index + 1; ?></td>
                                        <td><?= htmlspecialchars($form['academic_year']); ?></td>
                                        <td><?= htmlspecialchars($form['date_applied']); ?></td>
                                        <td><?= htmlspecialchars($form['overall_status']); ?></td>
                                        <td><?= htmlspecialchars($form['department_statuses']); ?></td>
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
