<?php
session_start();
// Hapa unaweza kuweka logic ya kuangalia kama user amesha-login ili kum-redirect moja kwa moja
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUZA Clearance Form Management System - Login</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <!-- Google Fonts (Inter au Roboto kwa muonekano safi) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/style.css">
   
</head>
<body>

    <!-- 1. TOP NAVBAR / HEADER -->
    <header class="suza-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3">
            <!-- Nembo ya SUZA ya Juu Kushoto -->
            <img src="./assets/images/logo.jpg" alt="SUZA Logo" class="header-logo">
            <div>
                <h1 class="header-title-main">THE STATE UNIVERSITY OF ZANZIBAR</h1>
                <p class="header-subtitle">Knowledge, Responsibility, Sincerity</p>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 text-white">
            <div class="header-right-title">
                SUZA CLEARANCE FORM<br>MANAGEMENT SYSTEM
            </div>
            <i class="bi bi-shield-check fs-3 ms-2 text-info"></i>
        </div>
    </header>

    <!-- 2. MAIN INTERFACE CARD -->
    <main class="main-content">
        <div class="card login-card">
            <div class="row g-0">
                
                <!-- Left Panel (Branding) -->
                <div class="col-lg-5 brand-panel">
                    <img src="./assets/images/logo.jpg" alt="SUZA Large Logo" class="brand-logo">
                    <h2>SUZA CLEARANCE<br>FORM MANAGEMENT SYSTEM</h2>
                    <div class="brand-divider"></div>
                    <p>A web-based system to manage and automate student clearance process efficiently.</p>
                </div>
                
                <!-- Right Panel (Form) -->
                <div class="col-lg-7 form-panel">
                    
                    <!-- Alert Messages for PHP Error Handling -->
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="text-center">
                        <div class="text-primary mb-2">
                            <i class="bi bi-person-circle" style="font-size: 3.5rem; color: var(--suza-blue);"></i>
                        </div>
                        <h3>Login to Your Account</h3>
                        <div class="form-title-divider"></div>
                        <p class="text-muted small" >Please enter your credentials to access the system</p>
                    </div>

                    <!-- LOGIN FORM -->
                    <form action="login-process.php" method="POST" class="mt-4">
                        
                        <!-- Email Address Input -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email address" required>
                            </div>
                        </div>

                        <!-- Password Input -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                                <span class="input-group-text password-toggle" id="togglePassword">
                                    <i class="bi bi-eye" id="eyeIcon"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Role Selection -->
                        <div class="mb-3">
                            <label for="role" class="form-label">Select Role</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                                <select name="role" id="role" class="form-select" required>
                                    <option value="">Choose role</option>
                                    <option value="student">Student</option>
                                    <option value="officer">Officer</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" name="login_btn" class="btn btn-primary btn-login w-100 rounded d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-box-arrow-in-right"></i> LOGIN
                        </button>

                        <!-- Remember Me -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="form-check">
                                <input type="checkbox" name="remember_me" class="form-check-input" id="rememberMe">
                                <label class="form-check-label small text-secondary" for="rememberMe">Remember me</label>
                            </div>
                            <a href="register.php" class="text-decoration-none small text-primary fw-medium">Don't have an account? Register</a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </main>

    <!-- 3. FOOTER -->
    <footer class="suza-footer">
        © 2026 The State University of Zanzibar. All rights reserved.
    </footer>

    <!-- Bootstrap 5 JavaScript Bundle w/ Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- JavaScript for Password Toggle Feature -->
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            // Badilisha aina ya input (text <-> password)
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            
            // Badilisha icon ya jicho (open eye <-> slashed eye)
            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
    </script>
</body>
</html>