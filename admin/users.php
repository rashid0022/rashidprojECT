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
    <title>Officer Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body style="font-family: Inter, sans-serif; background:#f3f7fb;">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h4 mb-1">Officer Management</h1>
                <p class="text-muted mb-0">Create, edit, delete and assign department officers.</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline-secondary">Back to Admin Dashboard</a>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row gy-4">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?= $editOfficer ? 'Edit Officer' : 'Create New Officer'; ?></h5>
                        <form method="post" action="users.php">
                            <input type="hidden" name="action" value="<?= $editOfficer ? 'edit_officer' : 'create_officer'; ?>">
                            <?php if ($editOfficer): ?>
                                <input type="hidden" name="user_id" value="<?= htmlspecialchars($editOfficer['user_id']); ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($editOfficer['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Officer ID / Reg. Number</label>
                                <input type="text" name="registration_number" class="form-control" value="<?= htmlspecialchars($editOfficer['registration_number'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($editOfficer['email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($editOfficer['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <select name="department_id" class="form-select" required>
                                    <option value="" disabled <?= $editOfficer ? '' : 'selected'; ?>>Choose a department</option>
                                    <?php foreach ($departments as $department): ?>
                                        <option value="<?= $department['department_id']; ?>" <?= isset($editOfficer['department_id']) && $editOfficer['department_id'] == $department['department_id'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($department['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if (!$editOfficer): ?>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100"><?= $editOfficer ? 'Save Changes' : 'Create Officer'; ?></button>
                            <?php if ($editOfficer): ?>
                                <a href="users.php" class="btn btn-link w-100 mt-2">Cancel edit</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Active Officers</h5>
                        <?php if (empty($officerList)): ?>
                            <p class="text-muted">No officers have been created yet.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($officerList as $index => $officer): ?>
                                            <tr>
                                                <td><?= $index + 1; ?></td>
                                                <td><?= htmlspecialchars($officer['full_name']); ?></td>
                                                <td><?= htmlspecialchars($officer['email']); ?></td>
                                                <td><?= htmlspecialchars($officer['department_name'] ?? 'Unassigned'); ?></td>
                                                <td>
                                                    <a href="users.php?edit_id=<?= $officer['user_id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                                    <form method="post" action="users.php" class="d-inline">
                                                        <input type="hidden" name="action" value="delete_officer">
                                                        <input type="hidden" name="user_id" value="<?= $officer['user_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete officer account?');">Delete</button>
                                                    </form>
                                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#reset-<?= $officer['user_id']; ?>">Reset</button>
                                                    <div class="collapse mt-2" id="reset-<?= $officer['user_id']; ?>">
                                                        <form method="post" action="users.php" class="d-flex gap-2">
                                                            <input type="hidden" name="action" value="reset_password">
                                                            <input type="hidden" name="user_id" value="<?= $officer['user_id']; ?>">
                                                            <input type="password" name="password" class="form-control form-control-sm" placeholder="New password" required>
                                                            <button type="submit" class="btn btn-sm btn-success">Save</button>
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
