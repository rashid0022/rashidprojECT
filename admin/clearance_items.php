<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

function redirectBack()
{
    header('Location: clearance_items.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_item') {
            $item_name = trim($_POST['item_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $department_id = (int) ($_POST['department_id'] ?? 0);

            if ($item_name === '' || $department_id <= 0) {
                throw new Exception('Please provide an item name and department.');
            }

            $check = $pdo->prepare('SELECT COUNT(*) FROM clearance_items WHERE item_name = ? AND department_id = ?');
            $check->execute([$item_name, $department_id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('This clearance item already exists for the selected department.');
            }

            $stmt = $pdo->prepare('INSERT INTO clearance_items (item_name, description, department_id) VALUES (?, ?, ?)');
            $stmt->execute([$item_name, $description, $department_id]);
            $_SESSION['success'] = 'Clearance item added successfully.';
            redirectBack();
        }

        if ($action === 'edit_item') {
            $item_id = (int) ($_POST['item_id'] ?? 0);
            $item_name = trim($_POST['item_name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $department_id = (int) ($_POST['department_id'] ?? 0);
            $status = $_POST['status'] ?? 'active';

            if ($item_id <= 0 || $item_name === '' || $department_id <= 0) {
                throw new Exception('Please provide an item name and department.');
            }

            $check = $pdo->prepare('SELECT COUNT(*) FROM clearance_items WHERE item_name = ? AND department_id = ? AND item_id != ?');
            $check->execute([$item_name, $department_id, $item_id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Another clearance item with the same name exists for that department.');
            }

            $stmt = $pdo->prepare('UPDATE clearance_items SET item_name = ?, description = ?, department_id = ?, status = ? WHERE item_id = ?');
            $stmt->execute([$item_name, $description, $department_id, $status, $item_id]);
            $_SESSION['success'] = 'Clearance item updated successfully.';
            redirectBack();
        }

        if ($action === 'delete_item') {
            $item_id = (int) ($_POST['item_id'] ?? 0);

            if ($item_id <= 0) {
                throw new Exception('Invalid clearance item selected for deletion.');
            }

            $stmt = $pdo->prepare('DELETE FROM clearance_items WHERE item_id = ?');
            $stmt->execute([$item_id]);
            $_SESSION['success'] = 'Clearance item deleted successfully.';
            redirectBack();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirectBack();
    }
}

// Ensure clearance_items table exists before querying it.
try {
    $pdo->query('SELECT 1 FROM clearance_items LIMIT 1');
} catch (PDOException $e) {
    if ($e->getCode() === '42S02') {
        $pdo->exec("CREATE TABLE clearance_items (
            item_id INT AUTO_INCREMENT PRIMARY KEY,
            item_name VARCHAR(150) NOT NULL,
            description TEXT NULL,
            department_id INT NOT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_clearance_item_department FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE RESTRICT ON UPDATE CASCADE,
            UNIQUE KEY uniq_clearance_item_name_department (item_name, department_id)
        ) ENGINE=InnoDB");
    } else {
        throw $e;
    }
}

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll(PDO::FETCH_ASSOC);
$items = $pdo->prepare('SELECT ci.item_id, ci.item_name, ci.description, ci.status, ci.department_id, d.department_name FROM clearance_items ci JOIN departments d ON ci.department_id = d.department_id ORDER BY ci.item_name');
$items->execute();
$itemList = $items->fetchAll(PDO::FETCH_ASSOC);

$editItem = null;
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT item_id, item_name, description, department_id, status FROM clearance_items WHERE item_id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clearance Items · SUZA</title>
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
        textarea.form-control {
            resize: vertical;
            min-height: 80px;
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

        .badge-status {
            padding: 0.3rem 0.9rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .badge-status.active {
            background: #ddf5e9;
            color: #0d7a4e;
        }
        .badge-status.inactive {
            background: #f0f2f5;
            color: #6b7a8f;
        }

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
                <a href="users.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-people"></i> Users Management</div>
                </a>
                <a href="departments.php" class="nav-link">
                    <div class="nav-link-left"><i class="bi bi-building"></i> Departments</div>
                </a>
                <a href="clearance_items.php" class="nav-link active">
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
                    <h2 class="fw-bold text-dark h4 mb-1">📋 Clearance Items</h2>
                    <p class="text-muted small m-0">Manage all clearance checklist items and assign them to departments.</p>
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
                <div class="col-lg-4">
                    <div class="content-card shadow-sm">
                        <div class="card-header-custom">
                            <i class="bi <?= $editItem ? 'bi-pencil-square text-primary' : 'bi-plus-circle text-primary'; ?> fs-5"></i>
                            <?= $editItem ? 'Edit Clearance Item' : 'Add Clearance Item'; ?>
                        </div>
                        <form method="post" action="clearance_items.php">
                            <input type="hidden" name="action" value="<?= $editItem ? 'edit_item' : 'create_item'; ?>">
                            <?php if ($editItem): ?>
                                <input type="hidden" name="item_id" value="<?= htmlspecialchars($editItem['item_id']); ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-tag me-1"></i> Item Name</label>
                                <input type="text" name="item_name" class="form-control" 
                                       value="<?= htmlspecialchars($editItem['item_name'] ?? ''); ?>" 
                                       placeholder="e.g. Library Clearance" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-building me-1"></i> Department</label>
                                <select name="department_id" class="form-select" required>
                                    <option value="" disabled <?= $editItem ? '' : 'selected'; ?>>Select department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= $department['department_id']; ?>" 
                                            <?= isset($editItem['department_id']) && $editItem['department_id'] == $department['department_id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($department['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><i class="bi bi-file-text me-1"></i> Description</label>
                                <textarea name="description" class="form-control" rows="3" 
                                          placeholder="Brief description of this clearance item"><?= htmlspecialchars($editItem['description'] ?? ''); ?></textarea>
                            </div>

                            <?php if ($editItem): ?>
                                <div class="mb-3">
                                    <label class="form-label"><i class="bi bi-toggle-on me-1"></i> Status</label>
                                    <select name="status" class="form-select">
                                        <option value="active" <?= isset($editItem['status']) && $editItem['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?= isset($editItem['status']) && $editItem['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi <?= $editItem ? 'bi-check2' : 'bi-plus-lg'; ?> me-1"></i>
                                <?= $editItem ? 'Update Item' : 'Create Item'; ?>
                            </button>

                            <?php if ($editItem): ?>
                                <a href="clearance_items.php" class="btn btn-link w-100 mt-2">
                                    <i class="bi bi-x-circle me-1"></i> Cancel edit
                                </a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- RIGHT COLUMN: Items Table -->
                <div class="col-lg-8">
                    <div class="content-card shadow-sm">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <span><i class="bi bi-list-check text-primary me-2"></i> Clearance Item List</span>
                            <span class="badge bg-light text-dark rounded-pill px-3 py-1"><?= count($itemList); ?></span>
                        </div>

                        <?php if (empty($itemList)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-3 opacity-50"></i>
                                <p class="mb-0">No clearance items have been configured yet.</p>
                                <small>Use the form on the left to add your first item.</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">#</th>
                                            <th>Item</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th class="text-end" style="min-width: 140px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($itemList as $index => $item): ?>
                                            <tr>
                                                <td><?= $index + 1; ?></td>
                                                <td>
                                                    <span class="fw-semibold"><?= htmlspecialchars($item['item_name']); ?></span>
                                                    <?php if (!empty($item['description'])): ?>
                                                        <div class="text-muted small mt-1">
                                                            <i class="bi bi-file-text me-1"></i> <?= htmlspecialchars($item['description']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge-department">
                                                        <?= htmlspecialchars($item['department_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge-status <?= $item['status']; ?>">
                                                        <?= $item['status'] === 'active' ? '✅ Active' : '⏸ Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex gap-1 justify-content-end">
                                                        <a href="clearance_items.php?edit_id=<?= $item['item_id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="Edit Item">
                                                            <i class="bi bi-pencil"></i>
                                                        </a>
                                                        <form method="post" action="clearance_items.php" class="d-inline">
                                                            <input type="hidden" name="action" value="delete_item">
                                                            <input type="hidden" name="item_id" value="<?= $item['item_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="return confirm('Delete this clearance item? This action cannot be undone.')" 
                                                                    title="Delete Item">
                                                                <i class="bi bi-trash3"></i>
                                                            </button>
                                                        </form>
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