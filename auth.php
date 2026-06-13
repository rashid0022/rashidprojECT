<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function require_login()
{
    if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $_SESSION['error'] = 'Please login to access this page.';
        header('Location: index.php');
        exit;
    }
}

function require_role(string $role)
{
    require_login();

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        header('Location: dashboard.php');
        exit;
    }
}

function redirect_dashboard()
{
    require_login();

    switch ($_SESSION['role'] ?? 'student') {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'officer':
            header('Location: officer/dashboard.php');
            break;
        default:
            header('Location: student/dashboard.php');
            break;
    }

    exit;
}
