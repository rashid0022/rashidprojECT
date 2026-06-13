<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$academic_year = '2025/2026';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = trim($_POST['academic_year'] ?? '');

    try {
        if ($academic_year === '') {
            throw new Exception('Please enter the academic year.');
        }

        $stmt = $pdo->prepare('SELECT department_id FROM departments WHERE department_name IN (?, ?, ?, ?) ORDER BY department_id');
        $stmt->execute(['Library', 'Finance', 'Accommodation', 'Department Office']);
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($departments) !== 4) {
            throw new Exception('Required clearance departments are not configured.');
        }

        $pdo->beginTransaction();

        $insertForm = $pdo->prepare('INSERT INTO clearance_forms (student_id, academic_year, date_applied, status) VALUES (?, ?, CURDATE(), ?)');
        $insertForm->execute([$_SESSION['user_id'], $academic_year, 'Pending']);
        $formId = $pdo->lastInsertId();

        $insertStatus = $pdo->prepare('INSERT INTO clearance_status (form_id, department_id) VALUES (?, ?)');
        foreach ($departments as $departmentId) {
            $insertStatus->execute([$formId, $departmentId]);
        }

        $pdo->commit();
        $_SESSION['success'] = 'Your clearance request has been submitted successfully.';
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Clearance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>
<body style="font-family: Inter, sans-serif; background:#f3f7fb;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h1 class="h4 mb-1">Submit Clearance Request</h1>
                                <p class="text-muted mb-0">Your clearance request will create status records for all core departments.</p>
                            </div>
                            <a href="dashboard.php" class="btn btn-outline-secondary">Back</a>
                        </div>

                        <?php if ($success): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="post" action="request_clearance.php">
                            <div class="mb-3">
                                <label class="form-label">Academic Year</label>
                                <input type="text" name="academic_year" class="form-control" value="<?= htmlspecialchars($academic_year); ?>" required>
                                <div class="form-text">Example: 2025/2026</div>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Request</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
