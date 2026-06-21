<?php
/**
 * Admin Reports Dashboard
 * View clearance statistics and performance metrics
 */

require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/WorkflowEngine.php';

$workflowEngine = new WorkflowEngine($pdo);

// Get overall statistics
$stmt = $pdo->prepare('
    SELECT 
        COUNT(*) as total_requests,
        SUM(status = "Completed") as completed,
        SUM(status = "Rejected") as rejected,
        SUM(status = "Pending") as pending,
        SUM(status = "In Progress") as in_progress
    FROM clearance_forms
');
$stmt->execute();
$overallStats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get department-wise statistics
$workflowStats = $workflowEngine->getWorkflowStats();

// Get recent requests
$stmt = $pdo->prepare('
    SELECT cf.form_id, cf.academic_session, cf.status, cf.date_applied, 
           u.full_name, u.registration_number,
           COUNT(cs.status_id) as total_depts,
           SUM(cs.status = "Approved") as approved_depts
    FROM clearance_forms cf
    JOIN users u ON cf.user_id = u.user_id
    LEFT JOIN clearance_status cs ON cf.form_id = cs.form_id
    GROUP BY cf.form_id
    ORDER BY cf.created_at DESC
    LIMIT 50
');
$stmt->execute();
$recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports | Clearance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f7fb; }
        .stat-card { border: none; border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .stat-number { font-size: 2.5rem; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container-fluid py-5">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 fw-bold">Clearance System Reports</h1>
                <p class="text-muted">System-wide statistics and performance metrics</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>

        <!-- Overall Statistics -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Total Requests</div>
                        <div class="stat-number"><?= $overallStats['total_requests']; ?></div>
                        <div class="text-muted small mt-2">All clearance requests</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Completed</div>
                        <div class="stat-number text-success"><?= $overallStats['completed'] ?? 0; ?></div>
                        <div class="text-muted small mt-2">Fully approved</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="text-muted small mb-2">In Progress</div>
                        <div class="stat-number text-warning"><?= $overallStats['in_progress'] ?? 0; ?></div>
                        <div class="text-muted small mt-2">Awaiting approvals</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card stat-card">
                    <div class="card-body">
                        <div class="text-muted small mb-2">Rejected</div>
                        <div class="stat-number text-danger"><?= $overallStats['rejected'] ?? 0; ?></div>
                        <div class="text-muted small mt-2">Not approved</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Performance -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Department Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Total</th>
                                        <th>Approved</th>
                                        <th>Rejected</th>
                                        <th>Pending</th>
                                        <th>Avg Days</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($workflowStats as $stat): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($stat['department_name']); ?></td>
                                        <td><?= $stat['total_requests']; ?></td>
                                        <td><span class="badge bg-success"><?= $stat['approved']; ?></span></td>
                                        <td><span class="badge bg-danger"><?= $stat['rejected']; ?></span></td>
                                        <td><span class="badge bg-warning text-dark"><?= $stat['pending']; ?></span></td>
                                        <td><?= round($stat['avg_processing_days'] ?? 0); ?> days</td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Chart -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="card-title mb-0">Status Distribution</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Recent Clearance Requests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Reg No.</th>
                                <th>Academic Year</th>
                                <th>Applied</th>
                                <th>Approval Progress</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $req): ?>
                            <tr>
                                <td><?= $req['form_id']; ?></td>
                                <td><?= htmlspecialchars($req['full_name']); ?></td>
                                <td><?= htmlspecialchars($req['registration_number']); ?></td>
                                <td><?= htmlspecialchars($req['academic_session']); ?></td>
                                <td><?= $req['date_applied']; ?></td>
                                <td>
                                    <?= $req['approved_depts'] ?? 0; ?>/<?= $req['total_depts']; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadge = match($req['status']) {
                                        'Completed' => '<span class="badge bg-success">Completed</span>',
                                        'Rejected' => '<span class="badge bg-danger">Rejected</span>',
                                        'In Progress' => '<span class="badge bg-info">In Progress</span>',
                                        default => '<span class="badge bg-warning text-dark">Pending</span>'
                                    };
                                    echo $statusBadge;
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Status Distribution Chart
        const ctx = document.getElementById('statusChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Pending', 'Rejected'],
                datasets: [{
                    data: [
                        <?= $overallStats['completed'] ?? 0; ?>,
                        <?= $overallStats['in_progress'] ?? 0; ?>,
                        <?= $overallStats['pending'] ?? 0; ?>,
                        <?= $overallStats['rejected'] ?? 0; ?>
                    ],
                    backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
