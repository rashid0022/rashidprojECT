<?php
require_once __DIR__ . '/../auth.php';
require_role('student');
require_once __DIR__ . '/../config/database.php';

// Retrieve flash messages from session
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Prefill academic year with current session pattern
$academic_year = '2025/2026';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academic_year = trim($_POST['academic_year'] ?? '');

    try {
        // Validate input
        if ($academic_year === '') {
            throw new Exception('Please enter the academic year.');
        }

        // Validate academic year format (optional but helpful)
        if (!preg_match('/^\d{4}\/\d{4}$/', $academic_year)) {
            throw new Exception('Academic year must be in format YYYY/YYYY (e.g., 2025/2026).');
        }

        // Fetch required department IDs (core clearance departments)
        $stmt = $pdo->prepare('SELECT department_id FROM departments WHERE department_name IN (?, ?, ?, ?) ORDER BY department_id');
        $stmt->execute(['Library', 'Finance', 'Accommodation', 'Department Office']);
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($departments) !== 4) {
            throw new Exception('Required clearance departments (Library, Finance, Accommodation, Department Office) are not fully configured. Please contact administrator.');
        }

        // Begin transaction to ensure data consistency
        $pdo->beginTransaction();

        // Insert main clearance form
        $insertForm = $pdo->prepare('INSERT INTO clearance_forms (user_id, academic_session, date_applied, status) VALUES (?, ?, CURDATE(), ?)');
        $insertForm->execute([$_SESSION['user_id'], $academic_year, 'Pending']);
        $formId = $pdo->lastInsertId();

        // Insert clearance status for each department (initially all Pending)
        $insertStatus = $pdo->prepare('INSERT INTO clearance_status (form_id, department_id, status) VALUES (?, ?, ?)');
        foreach ($departments as $departmentId) {
            $insertStatus->execute([$formId, $departmentId, 'Pending']);
        }

        $pdo->commit();

        // Set success message and redirect to dashboard
        $_SESSION['success'] = 'Your clearance request has been submitted successfully. You can now track progress from your dashboard.';
        header('Location: dashboard.php');
        exit;
    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Clearance | ClearFlow</title>
    <!-- Bootstrap 5.3.2 + Icons + Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f4fa 0%, #e9eef5 100%);
            min-height: 100vh;
        }
        .card-custom {
            border: none;
            border-radius: 1.5rem;
            background: #ffffff;
            box-shadow: 0 20px 35px -12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }
        .card-custom:hover {
            transform: translateY(-2px);
        }
        .form-control, .form-select {
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            padding: 0.7rem 1rem;
            font-size: 0.95rem;
            transition: all 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        .btn-primary-custom {
            background: #0f172a;
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary-custom:hover {
            background: #1e293b;
            transform: translateY(-1px);
            box-shadow: 0 8px 16px -6px rgba(15,23,42,0.2);
        }
        .btn-outline-custom {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
        }
        .dept-badge {
            background: #f8fafc;
            border-radius: 2rem;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #e2e8f0;
        }
        .alert-modern {
            border-radius: 1rem;
            border: none;
            padding: 1rem 1.25rem;
        }
        .step-indicator {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.85rem;
        }
        .step.active {
            color: #0f172a;
            font-weight: 600;
        }
        .step .step-number {
            width: 28px;
            height: 28px;
            background: #f1f5f9;
            border-radius: 28px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .step.active .step-number {
            background: #0f172a;
            color: white;
        }
        hr {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container py-4 py-md-5">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <!-- Header with logo / breadcrumb -->
                <div class="mb-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white rounded-3 p-2 shadow-sm">
                            <i class="bi bi-shield-check text-primary fs-3"></i>
                        </div>
                        <div>
                            <h2 class="h4 fw-bold mb-0">Request New Clearance</h2>
                            <p class="text-muted small mb-0">Initiate your digital clearance process</p>
                        </div>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                    </a>
                </div>

                <!-- Main Form Card -->
                <div class="card card-custom p-3 p-md-4">
                    <div class="card-body p-2 p-md-3">
                        <!-- Simple step visual -->
                        <div class="step-indicator">
                            <div class="step active">
                                <span class="step-number">1</span>
                                <span>Request Details</span>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                            <div class="step">
                                <span class="step-number">2</span>
                                <span>Department Routing</span>
                            </div>
                            <i class="bi bi-chevron-right text-muted small"></i>
                            <div class="step">
                                <span class="step-number">3</span>
                                <span>Confirmation</span>
                            </div>
                        </div>
                        <hr class="my-3">

                        <!-- Flash Messages -->
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-modern d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-check-circle-fill fs-5"></i>
                                <div><?= htmlspecialchars($success); ?></div>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-modern d-flex align-items-center gap-2 mb-4">
                                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                                <div><?= htmlspecialchars($error); ?></div>
                            </div>
                        <?php endif; ?>

                        <!-- info card: departments overview -->
                        <div class="bg-light rounded-4 p-3 mb-4">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-building-gear text-primary"></i>
                                <span class="fw-semibold">Departments involved in clearance</span>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <span class="dept-badge"><i class="bi bi-book text-info"></i> Library</span>
                                <span class="dept-badge"><i class="bi bi-calculator-fill text-success"></i> Finance</span>
                                <span class="dept-badge"><i class="bi bi-house-door text-warning"></i> Accommodation</span>
                                <span class="dept-badge"><i class="bi bi-person-badge text-primary"></i> Department Office</span>
                            </div>
                            <p class="small text-muted mt-2 mb-0">
                                <i class="bi bi-info-circle"></i> Once submitted, each department will review your status independently.
                            </p>
                        </div>

                        <!-- Clearance Request Form -->
                        <form method="post" action="request_clearance.php" id="clearanceForm">
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Academic Year <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0 rounded-start-4">
                                        <i class="bi bi-calendar-event text-muted"></i>
                                    </span>
                                    <input type="text" name="academic_year" class="form-control border-start-0 rounded-end-4 ps-0" 
                                           value="<?= htmlspecialchars($academic_year); ?>" 
                                           placeholder="e.g., 2025/2026" required>
                                </div>
                                <div class="form-text">Format: YYYY/YYYY (e.g., 2025/2026 for the session starting in 2025)</div>
                            </div>

                            <!-- Additional note: Session info -->
                            <div class="alert alert-light border rounded-4 p-3 mb-4" style="background: #fefce8;">
                                <div class="d-flex gap-2">
                                    <i class="bi bi-megaphone fs-5 text-warning"></i>
                                    <div class="small">
                                        <strong>What happens after submission?</strong><br>
                                        Your request will be recorded with status "Pending". Each department will process your clearance and update their status. You can track real-time progress on your dashboard.
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 pt-2">
                                <button type="button" class="btn btn-outline-custom btn-outline-secondary px-4" onclick="window.location.href='dashboard.php'">
                                    <i class="bi bi-x-circle me-1"></i> Cancel
                                </button>
                                <button type="submit" class="btn btn-primary-custom px-5" id="submitBtn">
                                    <i class="bi bi-check2-circle me-2"></i> Submit Clearance Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Additional help card -->
                <div class="mt-4 text-center">
                    <p class="small text-muted">
                        <i class="bi bi-lock-fill me-1"></i> Secure request · All data encrypted
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Additional client-side validation -->
    <script>
        (function() {
            const form = document.getElementById('clearanceForm');
            const submitBtn = document.getElementById('submitBtn');
            const academicInput = document.querySelector('input[name="academic_year"]');
            
            // Real-time format helper (optional feedback)
            academicInput?.addEventListener('input', function(e) {
                let val = this.value.trim();
                // Suggest format if user types numbers but no slash
                if (/^\d{4}$/.test(val) && val.length === 4) {
                    // don't auto-fix, just hint
                    this.setCustomValidity('');
                } else if (val && !/^\d{4}\/\d{4}$/.test(val)) {
                    this.setCustomValidity('Use format: YYYY/YYYY (example: 2025/2026)');
                } else {
                    this.setCustomValidity('');
                }
            });
            
            form?.addEventListener('submit', function(e) {
                const academicYear = academicInput?.value.trim() || '';
                const yearPattern = /^\d{4}\/\d{4}$/;
                if (!yearPattern.test(academicYear)) {
                    e.preventDefault();
                    // Show a temporary error if not matching
                    let errorDiv = document.querySelector('.alert-danger');
                    if (!errorDiv) {
                        const alertHtml = `<div class="alert alert-danger alert-modern d-flex align-items-center gap-2 mb-4">
                            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                            <div>Academic year must follow the format YYYY/YYYY (e.g., 2025/2026).</div>
                        </div>`;
                        const formCard = document.querySelector('.card-body');
                        const flashContainer = document.querySelector('.alert-danger')?.parentNode;
                        if (formCard && !document.querySelector('.format-error-msg')) {
                            const tempDiv = document.createElement('div');
                            tempDiv.innerHTML = alertHtml;
                            tempDiv.firstChild.classList.add('format-error-msg');
                            formCard.insertBefore(tempDiv.firstChild, form.querySelector('form') || formCard.children[3]);
                            setTimeout(() => {
                                const errDiv = document.querySelector('.format-error-msg');
                                if(errDiv) errDiv.remove();
                            }, 4000);
                        }
                    }
                    academicInput?.focus();
                    return false;
                }
                // Disable button to avoid double submission
                if(submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Submitting...';
                }
                return true;
            });
        })();
    </script>
</body>
</html>