<?php
require_once __DIR__ . '/../auth.php';
require_role('admin');
require_once __DIR__ . '/../config/database.php';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

function redirectBack()
{
    header('Location: departments.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create_department') {
            $department_name = trim($_POST['department_name'] ?? '');

            if ($department_name === '') {
                throw new Exception('Please enter the department name.');
            }

            $check = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE department_name = ?');
            $check->execute([$department_name]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('A department with this name already exists.');
            }

            $stmt = $pdo->prepare('INSERT INTO departments (department_name) VALUES (?)');
            $stmt->execute([$department_name]);
            $_SESSION['success'] = 'Department created successfully.';
            redirectBack();
        }

        if ($action === 'edit_department') {
            $department_id = (int) ($_POST['department_id'] ?? 0);
            $department_name = trim($_POST['department_name'] ?? '');

            if ($department_id <= 0 || $department_name === '') {
                throw new Exception('Please enter the department name.');
            }

            $check = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE department_name = ? AND department_id != ?');
            $check->execute([$department_name, $department_id]);
            if ($check->fetchColumn() > 0) {
                throw new Exception('Another department with this name already exists.');
            }

            $stmt = $pdo->prepare('UPDATE departments SET department_name = ? WHERE department_id = ?');
            $stmt->execute([$department_name, $department_id]);
            $_SESSION['success'] = 'Department updated successfully.';
            redirectBack();
        }

        if ($action === 'delete_department') {
            $department_id = (int) ($_POST['department_id'] ?? 0);

            if ($department_id <= 0) {
                throw new Exception('Invalid department selected for deletion.');
            }

            $stmt = $pdo->prepare('DELETE FROM departments WHERE department_id = ?');
            $stmt->execute([$department_id]);
            $_SESSION['success'] = 'Department deleted successfully.';
            redirectBack();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        redirectBack();
    }
}

$departments = $pdo->query('SELECT department_id, department_name FROM departments ORDER BY department_name')->fetchAll(PDO::FETCH_ASSOC);

$editDepartment = null;
if (isset($_GET['edit_id'])) {
    $editId = (int) $_GET['edit_id'];
    $stmt = $pdo->prepare('SELECT department_id, department_name FROM departments WHERE department_id = ? LIMIT 1');
    $stmt->execute([$editId]);
    $editDepartment = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Department Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="sidebar">
        <div>
            <div class="sidebar-brand">
                <img src="../assets/images/logo.png" alt="SUZA Logo"> <div>
                    <h5>SUZA CLEARANCE SYSTEM</h5>
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
                <a href="departments.php" class="nav-link active">
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

    <div class="main-wrapper">
        <header class="top-navbar">
            <button class="btn p-0 border-0 fs-4"><i class="bi bi-list"></i></button>
            <div class="navbar-meta">
                <div><i class="bi bi-calendar3 me-1"></i> <?= date('F j, Y'); ?></div>
                <div><i class="bi bi-clock me-1"></i> <?= date('g:i A'); ?></div>
                <div class="user-profile">
                    <div class="user-avatar"><i class="bi bi-person"></i></div>
                    <div class="text-start d-none d-sm-block">
                        <div class="fw-bold text-dark" style="font-size: 0.85rem; line-height:1.2;">Admin User</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">System Administrator</div>
                    </div>
                </div>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="mb-4 d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div>
                    <h2 class="fw-bold text-dark h4 mb-1">Department Management</h2>
                    <p class="text-muted small m-0">Create, edit, or remove clearance departments.</p>
                </div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Admin Dashboard</a>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row gy-4">
                <div class="col-lg-4">
                    <div class="content-card shadow-sm">
                        <h5 class="card-header-custom"><?= $editDepartment ? 'Edit Department' : 'Create Department'; ?></h5>
                        <form method="post" action="departments.php">
                            <input type="hidden" name="action" value="<?= $editDepartment ? 'edit_department' : 'create_department'; ?>">
                            <?php if ($editDepartment): ?>
                                <input type="hidden" name="department_id" value="<?= htmlspecialchars($editDepartment['department_id']); ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Department Name</label>
                                <input type="text" name="department_name" class="form-control" value="<?= htmlspecialchars($editDepartment['department_name'] ?? ''); ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><?= $editDepartment ? 'Save Changes' : 'Add Department'; ?></button>
                            <?php if ($editDepartment): ?>
                                <a href="departments.php" class="btn btn-link w-100 mt-2">Cancel edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="content-card shadow-sm">
                        <div class="card-header-custom">
                            <h5>Existing Departments</h5>
                        </div>
                        <?php if (empty($departments)): ?>
                            <p class="text-muted">No departments are configured yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Department Name</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($departments as $index => $department): ?>
                                            <tr>
                                                <td><?= $index + 1; ?></td>
                                                <td><?= htmlspecialchars($department['department_name']); ?></td>
                                                <td class="text-end">
                                                    <a href="departments.php?edit_id=<?= $department['department_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="post" action="departments.php" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_department">
                                                        <input type="hidden" name="department_id" value="<?= $department['department_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this department? This may fail if there are related clearance records.');">Delete</button>
                                                    </form>
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
