<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

function redirectBack()
{
    header('Location: users.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_officer') {
            $full_name = trim($_POST['full_name'] ?? '');
            $registration_number = trim($_POST['registration_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $password = $_POST['password'] ?? '';
            $department_id = (int) ($_POST['department_id'] ?? 0);

            if ($full_name === '' || $registration_number === '' || $email === '' || $phone === '' || $password === '' || $department_id <= 0) {
                throw new Exception('Please fill in all required officer fields.');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Please enter a valid email address.');
            }

            $check = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR registration_number = ?');
            $check->execute([$email, $registration_number]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Officer email or registration number already exists.');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (full_name, registration_number, email, phone, password, role, department_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$full_name, $registration_number, $email, $phone, $hashedPassword, 'officer', $department_id]);
            $_SESSION['success'] = 'Officer account created successfully.';
            redirectBack();
        }

        if ($action === 'edit_officer') {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $full_name = trim($_POST['full_name'] ?? '');
            $registration_number = trim($_POST['registration_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $department_id = (int) ($_POST['department_id'] ?? 0);

            if ($user_id <= 0 || $full_name === '' || $registration_number === '' || $email === '' || $phone === '' || $department_id <= 0) {
                throw new Exception('Please fill in all required officer fields.');
            }

            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE (email = ? OR registration_number = ?) AND user_id != ?');
            $stmt->execute([$email, $registration_number, $user_id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Another user already uses this email or registration number.');
            }

            $update = $pdo->prepare('UPDATE users SET full_name = ?, registration_number = ?, email = ?, phone = ?, department_id = ? WHERE user_id = ? AND role = ?');
            $update->execute([$full_name, $registration_number, $email, $phone, $department_id, $user_id, 'officer']);
            $_SESSION['success'] = 'Officer account updated successfully.';
            redirectBack();
        }

        if ($action === 'reset_password') {
            $user_id = (int) ($_POST['user_id'] ?? 0);
            $password = $_POST['password'] ?? '';

            if ($user_id <= 0 || $password === '') {
                throw new Exception('Please choose a new password.');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ? AND role = ?');
            $stmt->execute([$hashedPassword, $user_id, 'officer']);
            $_SESSION['success'] = 'Officer password reset successfully.';
            redirectBack();
        }

        if ($action === 'delete_officer') {
            $user_id = (int) ($_POST['user_id'] ?? 0);

            if ($user_id <= 0) {
                throw new Exception('Invalid officer selected for deletion.');
            }

            $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ? AND role = ?');
            $stmt->execute([$user_id, 'officer']);
            $_SESSION['success'] = 'Officer account deleted successfully.';
            redirectBack();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirectBack();
    }
}

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll(PDO::FETCH_ASSOC);
$officers = $pdo->prepare('SELECT u.user_id, u.full_name, u.registration_number, u.email, u.phone, u.department_id, d.department_name FROM users u LEFT JOIN departments d ON u.department_id = d.department_id WHERE u.role = ? ORDER BY u.full_name');
$officers->execute(['officer']);
$officerList = $officers->fetchAll(PDO::FETCH_ASSOC);

$editOfficer = null;
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT user_id, full_name, registration_number, email, phone, department_id FROM users WHERE user_id = ? AND role = ? LIMIT 1');
    $stmt->execute([$editId, 'officer']);
    $editOfficer = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Officer Management · SUZA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">

    <style>
        * { font-family: 'Inter', -apple-system, system-ui, sans-serif; }
        body {
            background: #f5f7fb;
            margin: 0;
            padding: 0;
        }

        /* --- Sidebar refinements --- */
        .sidebar {
            background: #ffffff;
            border-right: 1px solid rgba(0,0,0,0.03);
            box-shadow: 0 0 20px rgba(0,20,40,0.02);
        }
        .sidebar-brand h5 {
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #0b1f3b;
        }
        .sidebar-brand span {
            font-weight: 500;
            color: #6b7f98;
            font-size: 0.7rem;
            letter-spacing: 0.03em;
        }
        .nav-link {
            border-radius: 12px;
            padding: 0.55rem 0.9rem;
            font-weight: 500;
            color: #1f334f;
            transition: 0.15s ease;
        }
        .nav-link:hover { background: #f0f4fe; color: #0b1f3b; }
        .nav-link.active {
            background: #eef4ff;
            color: #1a4cff;
            box-shadow: inset 3px 0 0 #1a4cff;
        }
        .nav-link.active i { color: #1a4cff; }
        .nav-link i { color: #5e7391; }
        .badge-danger {
            background: #ff5a77;
            color: #fff;
            font-size: 0.6rem;
            font-weight: 600;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
        }
        .menu-section-title {
            color: #8b9bb3;
            font-weight: 600;
            letter-spacing: 0.04em;
        }
        .btn-logout {
            color: #5f7391;
            font-weight: 500;
        }
        .btn-logout:hover { background: #fef0f0; color: #c42b2b; }

        /* --- Top navbar --- */
        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid #edf2f8;
            backdrop-filter: blur(4px);
            padding: 0.6rem 2rem;
        }
        .user-profile {
            background: #f8fafe;
            border-radius: 40px;
            padding: 0.2rem 0.8rem 0.2rem 0.3rem;
        }
        .user-avatar {
            background: #e9eefa;
            color: #1a3d7a;
        }

        /* --- Cards --- */
        .content-card {
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #edf2f9;
            padding: 1.6rem 1.5rem 1.8rem;
            box-shadow: 0 2px 12px rgba(0,20,40,0.02);
            height: 100%;
            transition: 0.25s ease;
        }
        .content-card:hover { box-shadow: 0 8px 28px rgba(0,20,40,0.05); }

        .card-header-custom {
            font-weight: 600;
            font-size: 1.05rem;
            color: #0b1f3b;
            border-bottom: 2px solid #f0f4fc;
            padding-bottom: 0.75rem;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* --- Form elements --- */
        .form-control, .form-select {
            border-radius: 14px;
            border: 1px solid #e2e9f3;
            padding: 0.65rem 1rem;
            font-weight: 450;
            background: #fafcff;
            transition: 0.15s ease;
        }
        .form-control:focus, .form-select:focus {
            border-color: #1a4cff;
            box-shadow: 0 0 0 4px rgba(26,76,255,0.08);
            background: #ffffff;
        }
        .form-label {
            font-weight: 500;
            font-size: 0.85rem;
            color: #1f3a5e;
            margin-bottom: 0.4rem;
        }

        /* --- Buttons --- */
        .btn-primary {
            background: #1a4cff;
            border: none;
            font-weight: 600;
            padding: 0.7rem 0;
            border-radius: 14px;
            transition: 0.15s ease;
        }
        .btn-primary:hover { background: #0d3ed9; transform: translateY(-1px); }

        .btn-outline-primary {
            border-color: #d6e2f5;
            color: #1a3d7a;
            border-radius: 10px;
        }
        .btn-outline-primary:hover { background: #1a4cff; color: #fff; border-color: #1a4cff; }

        .btn-outline-danger {
            border-color: #f1d8dd;
            color: #b13e4b;
            border-radius: 10px;
        }
        .btn-outline-danger:hover { background: #d13a4a; color: #fff; border-color: #d13a4a; }

        .btn-outline-secondary {
            border-color: #dde3ed;
            color: #4a5f7a;
            border-radius: 10px;
        }
        .btn-outline-secondary:hover { background: #e9eefa; border-color: #bcc9db; }

        .btn-success {
            border-radius: 10px;
            font-weight: 500;
        }

        .btn-link {
            color: #5f7a9a;
            font-weight: 500;
            text-decoration: none;
        }
        .btn-link:hover { color: #1a3d7a; text-decoration: underline; }

        /* --- Table --- */
        .table {
            font-size: 0.88rem;
        }
        .table thead th {
            font-weight: 600;
            color: #1f3a5e;
            border-bottom: 2px solid #e7edf6;
            background: #f8fafe;
            padding: 0.7rem 0.5rem;
        }
        .table td { 
            vertical-align: middle; 
            padding: 0.6rem 0.5rem;
        }
        .table-bordered { border-color: #e7edf6; }
        .table-striped tbody tr:nth-of-type(odd) { background: #fafcff; }

        .badge-department {
            background: #eef3fa;
            color: #1f3f6d;
            padding: 0.3rem 0.9rem;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        /* --- Alerts --- */
        .alert {
            border-radius: 16px;
            border: none;
            font-weight: 500;
            padding: 0.9rem 1.2rem;
        }
        .alert-success { background: #e6f7ef; color: #0d7a4e; }
        .alert-danger { background: #fde8eb; color: #b13e4b; }

        /* --- Responsive --- */
        @media (max-width: 768px) {
            .top-navbar { padding: 0.6rem 1rem; }
            .dashboard-content { padding: 0.8rem; }
            .content-card { padding: 1.2rem; }
        }
    </style>
</head>
<body>

    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar">
        <div>
            <div class="sidebar-brand">
                <img src="../assets/images/logo.png" alt="SUZA Logo" onerror="this.style.display='none'">
                <div>
                    <h5>SUZA CLEARANCE</h5>
                    <span>Admin Dashboard</span>
                </div>
            </div>
            <div class="sidebar-menu">
                <a href="dashboard.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-grid-1x2-fill"></i> Dashboard</div>
                </a>
                <div class="menu-section-title">Management</div>
                <a href="users.php" class="nav-link active">
                    <div class="nav-link-left"><i class="bi bi-people"></i> Users Management</div>
                </a>
                <a href="departments.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-building"></i> Departments</div>
                </a>
                <a href="clearance_items.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-card-checklist"></i> Clearance Items</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-mortarboard"></i> Students</div>
                </a>
                <div class="menu-section-title">Clearance Management</div>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-file-earmark-text"></i> All Clearance Requests</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-hourglass-split"></i> Pending Approvals</div>
                    <span class="badge-danger">12</span>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-check-circle"></i> Completed Clearances</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-x-circle"></i> Rejected Clearances</div>
                </a>
                <div class="menu-section-title">System</div>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-bar-chart-line"></i> Reports & Statistics</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-journal-text"></i> Audit Logs</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-gear"></i> System Settings</div>
                </a>
                <a href="#" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-database"></i> Backup & Restore</div>
                </a>
            </div>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
    </div>

    <!-- ========== MAIN WRAPPER ========== -->
    <div class="main-wrapper">

        <!-- TOP NAVBAR -->
        <header class="top-navbar">
            <button class="btn p-0 border-0 fs-4"><i class="bi bi-list"></i></button>
            <div class="navbar-meta">
                <div><i class="bi bi-calendar3 me-1"></i> <?= date('F j, Y'); ?></div>
                <div><i class="bi bi-clock me-1"></i> <?= date('g:i A'); ?></div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="bi bi-person"></i></div>
                    <div class="text-start d-none d-sm-block">
                        <div class="fw-bold text-dark" style="font-size: 0.8rem; line-height:1.2;">Admin User</div>
                        <div style="font-size: 0.65rem; color: #6c819e;">System Administrator</div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </header>

        <!-- DASHBOARD CONTENT -->
        <div class="dashboard-content">

            <!-- Page Header -->
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="fw-bold text-dark h4 mb-1">👤 Officer Management</h2>
                    <p class="text-muted small m-0">Create, edit, delete and assign department officers.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>

            <!-- Flash Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center gap-2">
                    <i class="bi bi-check-circle-fill fs-5"></i> <?= htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center gap-2">
                    <i class="bi bi-exclamation-triangle-fill fs-5"></i> <?= htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Main Row -->
            <div class="row gy-4">

                <!-- LEFT COLUMN: Form -->
                <div class="col-lg-5">
                    <div class="content-card shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi <?= $editOfficer ? 'bi-pencil-square text-primary' : 'bi-person-plus text-primary'; ?> fs-5"></i>
                            <?= $editOfficer ? 'Edit Officer' : 'Create New Officer'; ?>
                        </div>
                        <form method="post" action="users.php">
                            <input type="hidden" name="action" value="<?= $editOfficer ? 'edit_officer' : 'create_officer'; ?>">
                            <?php if ($editOfficer): ?>
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($editOfficer['user_id']); ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-person me-1"></i> Full Name</label>
                                <input type="text" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($editOfficer['full_name'] ?? ''); ?>" 
                                       placeholder="e.g. Dr. Sarah Mwangi" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-card-heading me-1"></i> Officer ID / Reg. Number</label>
                                <input type="text" name="registration_number" class="form-control" 
                                       value="<?= htmlspecialchars($editOfficer['registration_number'] ?? ''); ?>" 
                                       placeholder="SU/2024/OF/001" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-envelope me-1"></i> Email</label>
                                <input type="email" name="email" class="form-control" 
                                       value="<?= htmlspecialchars($editOfficer['email'] ?? ''); ?>" 
                                       placeholder="officer@suza.ac.tz" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-phone me-1"></i> Phone</label>
                                <input type="text" name="phone" class="form-control" 
                                       value="<?= htmlspecialchars($editOfficer['phone'] ?? ''); ?>" 
                                       placeholder="+255 712 345 678" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-building me-1"></i> Department</label>
                                <select name="department_id" class="form-select" required>
                                    <option value="" disabled <?= $editOfficer ? '' : 'selected'; ?>>Select department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= $department['department_id']; ?>" 
                                            <?= isset($editOfficer['department_id']) && $editOfficer['department_id'] == $department['department_id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($department['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <?php if (!$editOfficer): ?>
                                <div class="mb-3">
                                    <label class="form-label"><i class="bi bi-key me-1"></i> Password</label>
                                    <input type="password" name="password" class="form-control" 
                                           placeholder="Min 8 characters" required>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi <?= $editOfficer ? 'bi-check2' : 'bi-plus-lg'; ?> me-1"></i>
                                <?= $editOfficer ? 'Save Changes' : 'Create Officer'; ?>
                            </button>

                            <?php if ($editOfficer): ?>
                                <a href="users.php" class="btn btn-link w-100 mt-2">
                                    <i class="bi bi-x-circle me-1"></i> Cancel edit
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Officers Table -->
                <div class="col-lg-7">
                    <div class="content-card shadow-sm">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-people-fill text-primary me-2"></i> Active Officers</span>
                            <span class="badge bg-light text-dark rounded-pill px-3 py-1"><?= count($officerList); ?></span>
                        </div>

                        <?php if (empty($officerList)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                <p class="mb-0">No officers have been created yet.</p>
                                <small>Use the form on the left to add your first officer.</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th class="text-center" style="min-width: 200px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($officerList as $index => $officer): ?>
                                            <tr>
                                                <td><?= $index + 1; ?></td>
                                                <td>
                                                    <span class="fw-semibold"><?= htmlspecialchars($officer['full_name']); ?></span>
                                                    <br><small class="text-muted"><?= htmlspecialchars($officer['registration_number'] ?? ''); ?></small>
                                                </td>
                                                <td><?= htmlspecialchars($officer['email']); ?></td>
                                                <td>
                                                    <span class="badge-department">
                                                        <?= htmlspecialchars($officer['department_name'] ?? 'Unassigned'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                                        <!-- Edit -->
                                                        <a href="users.php?edit_id=<?= $officer['user_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Edit Officer">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>

                                                        <!-- Delete -->
                                                        <form method="post" action="users.php" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_officer">
                                                            <input type="hidden" name="user_id" value="<?= $officer['user_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="return confirm('Delete this officer account? This action cannot be undone.')" 
                                                                    title="Delete Officer">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </form>

                                                        <!-- Reset Password Toggle -->
                                                        <button class="btn btn-sm btn-outline-secondary" type="button" 
                                                                data-bs-toggle="collapse" data-bs-target="#reset-<?= $officer['user_id']; ?>" 
                                                                title="Reset Password">
                                                            <i class="bi bi-key"></i>
                                                        </button>

                                                        <!-- Reset Password Form (collapsible) -->
                                                        <div class="collapse w-100 mt-1" id="reset-<?= $officer['user_id']; ?>">
                                                            <form method="post" action="users.php" class="d-flex gap-1">
                                                                <input type="hidden" name="action" value="reset_password">
                                                                <input type="hidden" name="user_id" value="<?= $officer['user_id']; ?>">
                                                                <input type="password" name="password" class="form-control form-control-sm" 
                                                                       placeholder="New password" required>
                                                                <button type="submit" class="btn btn-sm btn-success">
                                                                    <i class="bi bi-check2"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>