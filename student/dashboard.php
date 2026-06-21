<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClearFlow | Student Dashboard</title>
    <!-- Bootstrap 5.3.2 + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <!-- Chart.js for optional analytics (soft visual) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            overflow-x: hidden;
        }
        /* modern scroll */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 10px;
        }
        /* sidebar premium */
        .sidebar {
            background: linear-gradient(180deg, #ffffff 0%, #fefefe 100%);
            border-right: 1px solid rgba(203, 213, 225, 0.6);
            backdrop-filter: blur(0px);
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.02);
        }
        .nav-link {
            color: #334155 !important;
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin-bottom: 0.3rem;
            border-radius: 14px;
            transition: all 0.2s ease;
        }
        .nav-link i {
            font-size: 1.25rem;
            transition: transform 0.1s;
        }
        .nav-link:hover {
            background-color: #f1f5f9;
            color: #0f172a !important;
            transform: translateX(4px);
        }
        .nav-link.active {
            background: linear-gradient(95deg, #eef2ff 0%, #e6edfc 100%);
            color: #1e40af !important;
            font-weight: 600;
            border-left: 3px solid #3b82f6;
            border-radius: 14px;
        }
        .card {
            border: none;
            border-radius: 1.25rem;
            background: #ffffff;
            box-shadow: 0 8px 20px -6px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.02);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.08);
        }
        .badge-custom {
            font-weight: 500;
            padding: 0.35rem 0.85rem;
            border-radius: 40px;
            letter-spacing: 0.01em;
            backdrop-filter: blur(2px);
        }
        .dept-pill {
            background: #f8fafc;
            border-radius: 80px;
            padding: 0.45rem 1rem;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .dept-pill:hover {
            background: white;
            border-color: #cbd5e1;
            transform: scale(1.02);
        }
        .stat-circle {
            width: 48px;
            height: 48px;
            background: #eff6ff;
            border-radius: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .border-dashed {
            border: 2px dashed #cbd5e1 !important;
            background: #fefefe;
        }
        .glass-footer {
            background: rgba(255,255,240,0.5);
            backdrop-filter: blur(4px);
        }
        .progress-thin {
            height: 6px;
            border-radius: 8px;
            background-color: #e2e8f0;
        }
        .btn-primary-custom {
            background: #0f172a;
            border: none;
            border-radius: 40px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-primary-custom:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }
        @media (max-width: 768px) {
            .sidebar {
                position: relative;
                min-height: auto;
            }
        }
        .tracking-tight {
            letter-spacing: -0.3px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-0">
    <div class="row g-0">
        <!-- SIDEBAR - elegant & sticky -->
        <aside class="col-md-3 col-lg-2 sidebar vh-100 position-sticky top-0 p-3 p-md-4 d-flex flex-column" style="z-index: 10;">
            <div class="d-flex align-items-center gap-2 mb-4 mt-1 px-2">
                <div class="bg-primary bg-opacity-10 p-2 rounded-3">
                    <i class="bi bi-shield-check text-primary fs-4"></i>
                </div>
                <span class="fw-bold fs-4 tracking-tight text-dark">Clear<span class="text-primary">Flow</span></span>
            </div>
            
            <ul class="nav flex-column mb-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="#">
                        <i class="bi bi-speedometer2 me-3"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="request_clearance.php">
                        <i class="bi bi-pencil-square me-3"></i> New Request
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-archive me-3"></i> History
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="bi bi-question-circle me-3"></i> Help Desk
                    </a>
                </li>
            </ul>
            
            <!-- user profile + logout -->
            <div class="mt-auto pt-4">
                <div class="card bg-light border-0 rounded-4 p-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-white rounded-circle p-2 shadow-sm">
                            <i class="bi bi-person-circle fs-4 text-secondary"></i>
                        </div>
                        <div class="flex-grow-1 text-truncate">
                            <p class="fw-semibold mb-0 lh-sm text-truncate"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Student User'); ?></p>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 mt-1" style="font-size: 0.7rem;"><?= htmlspecialchars($_SESSION['role'] ?? 'student'); ?></span>
                        </div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-outline-danger w-100 rounded-4 d-flex align-items-center justify-content-center gap-2 py-2">
                    <i class="bi bi-box-arrow-right"></i> Sign out
                </a>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <main class="col-md-9 col-lg-10 px-3 px-md-5 py-4 bg-light">
            <!-- header with stats summary (dynamic from forms data) -->
            <?php
            // Helper : badge class mapping (enhanced)
            function getStatusClass($status) {
                $status = strtolower(trim($status));
                switch($status) {
                    case 'approved':
                    case 'cleared':
                        return 'bg-success bg-opacity-15 text-success border-0';
                    case 'pending':
                        return 'bg-warning bg-opacity-15 text-warning-emphasis border-0';
                    case 'rejected':
                    case 'hold':
                        return 'bg-danger bg-opacity-15 text-danger border-0';
                    default:
                        return 'bg-secondary bg-opacity-10 text-secondary border-0';
                }
            }
            
            // compute global stats from $forms if exists
            $totalRequests = count($forms);
            $approvedCount = 0;
            $pendingCount = 0;
            $rejectHoldCount = 0;
            $totalDeptSteps = 0;
            $clearedDeptSteps = 0;
            
            foreach ($forms as $form) {
                $overall = strtolower(trim($form['overall_status']));
                if ($overall === 'approved' || $overall === 'cleared') $approvedCount++;
                elseif ($overall === 'pending') $pendingCount++;
                else $rejectHoldCount++;
                
                // calculate department wise progress
                $deptsRaw = explode('|', $form['department_statuses']);
                foreach ($deptsRaw as $deptEntry) {
                    if (strpos($deptEntry, ':') !== false) {
                        $totalDeptSteps++;
                        list(, $depStatus) = explode(':', $deptEntry, 2);
                        if (in_array(strtolower(trim($depStatus)), ['approved','cleared'])) $clearedDeptSteps++;
                    }
                }
            }
            $progressPercent = ($totalDeptSteps > 0) ? round(($clearedDeptSteps / $totalDeptSteps) * 100) : 0;
            ?>
            
            <!-- top welcome row -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-1">
                <div>
                    <h1 class="display-6 fw-bold tracking-tight">Student Dashboard</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item active text-muted small">Clearance overview & milestones</li>
                        </ol>
                    </nav>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="request_clearance.php" class="btn btn-primary-custom shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i> New Clearance
                    </a>
                    <a href="status.php" class="btn btn-outline-secondary shadow-sm">
                        <i class="bi bi-info-circle me-2"></i> View Status
                    </a>
                </div>
            </div>
            
            <!-- Statistics cards : Live data-driven -->
            <div class="row g-3 mb-5">
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-circle">
                                <i class="bi bi-files fs-3 text-primary"></i>
                            </div>
                            <div>
                                <span class="text-muted text-uppercase small fw-semibold">Total Requests</span>
                                <h2 class="fw-bold mb-0"><?= $totalRequests; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-circle" style="background:#e6f7ec;">
                                <i class="bi bi-check2-circle fs-3 text-success"></i>
                            </div>
                            <div>
                                <span class="text-muted text-uppercase small fw-semibold">Approved / Cleared</span>
                                <h2 class="fw-bold mb-0"><?= $approvedCount; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-circle" style="background:#fff3e0;">
                                <i class="bi bi-hourglass-split fs-3 text-warning"></i>
                            </div>
                            <div>
                                <span class="text-muted text-uppercase small fw-semibold">Pending</span>
                                <h2 class="fw-bold mb-0"><?= $pendingCount; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card p-3 h-100 border-0">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-circle" style="background:#ffe8e8;">
                                <i class="bi bi-exclamation-triangle fs-3 text-danger"></i>
                            </div>
                            <div>
                                <span class="text-muted text-uppercase small fw-semibold">On Hold / Rejected</span>
                                <h2 class="fw-bold mb-0"><?= $rejectHoldCount; ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Overall progress insight (multi-department clearance completion) -->
            <?php if($totalRequests > 0): ?>
            <div class="card mb-4 p-3 p-md-4">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-2">
                    <div>
                        <h5 class="fw-bold mb-1"><i class="bi bi-graph-up me-2"></i> Clearance completion trend</h5>
                        <p class="text-muted small mb-0">Aggregated department sign-offs across all active requests</p>
                    </div>
                    <div class="fw-semibold fs-5"><?= $progressPercent; ?>%</div>
                </div>
                <div class="progress-thin w-100">
                    <div class="progress-bar bg-primary rounded-pill" role="progressbar" style="width: <?= $progressPercent; ?>%;" aria-valuenow="<?= $progressPercent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="mt-3 small text-secondary d-flex justify-content-between">
                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> <?= $clearedDeptSteps; ?> departments cleared</span>
                    <span><i class="bi bi-clock me-1"></i> <?= $totalDeptSteps - $clearedDeptSteps; ?> remaining</span>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- LIST OF CLEARANCE FORMS - enhanced card design -->
            <?php if (empty($forms)): ?>
                <div class="card text-center py-5 border-dashed bg-white">
                    <div class="card-body">
                        <i class="bi bi-inbox text-muted display-1 mb-3 d-block"></i>
                        <h3 class="fw-semibold text-secondary">No clearance requests yet</h3>
                        <p class="text-muted mx-auto mb-4" style="max-width: 400px;">Start your graduation or departmental clearance process by submitting a new request. All departments will review digitally.</p>
                        <a href="request_clearance.php" class="btn btn-lg btn-primary rounded-pill px-4">
                            <i class="bi bi-send-plus me-2"></i> Initiate clearance
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="d-flex justify-content-between align-items-center mt-2 mb-3">
                    <h5 class="fw-bold mb-0"><i class="bi bi-card-list me-2"></i> Active clearance operations</h5>
                    <span class="badge bg-secondary bg-opacity-10 text-dark px-3 py-2 rounded-pill"><?= count($forms); ?> record(s)</span>
                </div>
                
                <?php foreach ($forms as $index => $form): 
                    // additional computed fields: department array & status stats for each form
                    $deptEntries = [];
                    $rawDeptStatuses = $form['department_statuses'] ?? '';
                    $deptList = explode('|', $rawDeptStatuses);
                    $approvedDeptCount = 0;
                    $totalDeptInForm = 0;
                    foreach ($deptList as $item) {
                        if (strpos($item, ':') !== false) {
                            list($dname, $dstat) = explode(':', $item, 2);
                            $deptEntries[] = ['name' => $dname, 'status' => trim($dstat)];
                            $totalDeptInForm++;
                            if (in_array(strtolower(trim($dstat)), ['approved','cleared'])) $approvedDeptCount++;
                        }
                    }
                    $formProgress = ($totalDeptInForm > 0) ? round(($approvedDeptCount / $totalDeptInForm) * 100) : 0;
                    $appliedDate = new DateTime($form['date_applied']);
                    $formattedDate = $appliedDate->format('M d, Y');
                ?>
                    <div class="card mb-4 overflow-hidden">
                        <!-- card header with status ribbon -->
                        <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex flex-wrap justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-light rounded-3 p-2 px-3 text-center">
                                    <span class="fw-bold text-primary">#CLR-<?= str_pad($form['form_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                </div>
                                <div>
                                    <h5 class="mb-1 fw-bold"><?= htmlspecialchars($form['academic_year']); ?></h5>
                                    <div class="d-flex gap-3 small text-muted">
                                        <span><i class="bi bi-calendar-week"></i> Applied: <?= $formattedDate; ?></span>
                                        <span><i class="bi bi-diagram-3"></i> <?= $totalDeptInForm; ?> department(s)</span>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-2 mt-sm-0">
                                <span class="fw-semibold me-2 text-secondary small">Overall</span>
                                <span class="badge badge-custom fs-6 px-3 py-2 <?= getStatusClass($form['overall_status']); ?>">
                                    <i class="bi bi-<?= strtolower(trim($form['overall_status'])) == 'approved' ? 'check-lg' : (strtolower(trim($form['overall_status'])) == 'pending' ? 'clock' : 'x-lg'); ?> me-1"></i>
                                    <?= htmlspecialchars($form['overall_status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- progress line for current form -->
                        <div class="px-4 pt-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span>Department clearance progress</span>
                                <span class="fw-semibold"><?= $formProgress; ?>% (<?= $approvedDeptCount; ?>/<?= $totalDeptInForm; ?>)</span>
                            </div>
                            <div class="progress-thin w-100">
                                <div class="progress-bar bg-info bg-opacity-75 rounded-pill" style="width: <?= $formProgress; ?>%;"></div>
                            </div>
                        </div>
                        
                        <!-- department status pills modernized -->
                        <div class="card-body pt-3 pb-4 px-4">
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <?php foreach ($deptEntries as $dept): 
                                    $statusLow = strtolower($dept['status']);
                                    $pillIcon = ($statusLow === 'approved' || $statusLow === 'cleared') ? 'bi-check-circle-fill text-success' : (($statusLow === 'pending') ? 'bi-hourglass-top text-warning' : 'bi-slash-circle text-danger');
                                ?>
                                    <div class="dept-pill d-inline-flex align-items-center gap-2 shadow-sm">
                                        <i class="bi <?= $pillIcon; ?>"></i>
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($dept['name']); ?></span>
                                        <span class="badge bg-white text-dark border rounded-pill px-2 py-0 fw-normal" style="font-size:0.7rem;"><?= htmlspecialchars($dept['status']); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <!-- extra: dynamic note based on pending status -->
                            <?php if (strtolower($form['overall_status']) == 'pending'): ?>
                                <div class="alert alert-light border mt-3 py-2 small d-flex align-items-center gap-2 rounded-3">
                                    <i class="bi bi-info-circle-fill text-primary"></i>
                                    Some departments are still reviewing. You'll receive email notifications upon status change.
                                </div>
                            <?php elseif (strtolower($form['overall_status']) == 'approved'): ?>
                                <div class="alert alert-success bg-opacity-10 border-0 mt-3 py-2 small rounded-3">
                                    <i class="bi bi-trophy-fill me-1"></i> Congratulations! Full clearance approved.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-transparent border-top-0 pb-3 px-4 d-flex justify-content-end">
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" disabled style="cursor: default;">
                                <i class="bi bi-eye-slash"></i> View details (demo)
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Quick insights: most recent request timeline note -->
                <div class="alert alert-primary bg-opacity-10 border-0 rounded-4 d-flex align-items-center gap-3">
                    <i class="bi bi-megaphone fs-4"></i>
                    <div class="small">
                        <strong>Pro-tip:</strong> Each department processes clearances within 3-5 business days. For urgent requests, please contact respective department coordinators.
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- bootstrap bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- tiny script for dynamic chart (optional if any forms exist) but lightweight -->
<script>
    (function() {
        // just a micro effect: add tooltip customization (bootstrap handles)
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    })();
</script>
</body>
</html>