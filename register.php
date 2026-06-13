<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SUZA Registration Portal</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body{
    font-family: Inter, sans-serif;
    background: linear-gradient(135deg,#e9f0f8,#f8fbff);
    min-height:100vh;
}

/* HEADER */
.header{
    background: linear-gradient(90deg,#0040A1,#001f4d);
    color:#fff;
    padding:15px 40px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* CARD */
.card-glass{
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(10px);
    border-radius:16px;
    box-shadow:0 10px 40px rgba(0,0,0,0.15);
    overflow:hidden;
    border:none;
}

/* LEFT BANNER */
.banner{
    background: linear-gradient(135deg,#002d72,#0040a1);
    color:#fff;
    padding:30px;
    text-align:center;
}

/* FORM */
.form-section{
    padding:30px;
}

.form-control, .form-select{
    border-radius:10px;
    padding:10px;
}

.form-control:focus, .form-select:focus{
    box-shadow:none;
    border-color:#0040A1;
}

/* BUTTON */
.btn-main{
    background: linear-gradient(90deg,#0040A1,#0d6efd);
    border:none;
    padding:12px;
    border-radius:10px;
    font-weight:600;
}

.btn-main:hover{
    opacity:0.9;
}

/* FOOTER */
footer{
    text-align:center;
    padding:15px;
    font-size:13px;
    color:#555;
}
</style>
</head>

<body>

<!-- HEADER -->
<div class="header">
    <div class="d-flex align-items-center gap-3">
        <img src="images/suza-logo.png" width="50">
        <div>
            <h6 class="m-0 fw-bold">THE STATE UNIVERSITY OF ZANZIBAR</h6>
            <small class="text-warning">Knowledge • Responsibility • Sincerity</small>
        </div>
    </div>
</div>

<!-- MAIN -->
<div class="container d-flex justify-content-center align-items-center py-5">

<div class="card card-glass w-100" style="max-width:900px;">

    <div class="banner">
        <h4><i class="bi bi-person-plus-fill"></i> Student Registration</h4>
        <small>Create account to start clearance process</small>
    </div>

    <div class="form-section">

        <!-- ALERTS -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <form action="register-process.php" method="POST">

        <div class="row g-3">

            <div class="col-md-6">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label>Registration Number</label>
                <input type="text" name="registration_number" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" required>
            </div>

            <hr class="my-3">

            <div class="col-md-6">
                <label>Password</label>
                <input type="password" id="pass" name="password" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label>Confirm Password</label>
                <input type="password" id="cpass" name="confirm_password" class="form-control" required>
            </div>

        </div>

        <button class="btn btn-main w-100 mt-4">
            <i class="bi bi-check-circle"></i> Register Now
        </button>

        <p class="text-center mt-3 small">
            Already have an account? <a href="index.php">Login</a>

        </form>
    </div>
</div>

</div>

<footer>
© 2026 SUZA Clearance System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>