<?php
session_start();
require_once 'config/database.php';

if (!isset($_POST['login_btn'])) {
    header('Location: index.php');
    exit();
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$selected_role = trim($_POST['role'] ?? '');

if ($email === '' || $password === '' || $selected_role === '') {
    $_SESSION['error'] = 'Please enter email, password and select your role.';
    header('Location: index.php');
    exit();
}

try {
    $stmt = $pdo->prepare('SELECT user_id, full_name, email, password, role, department_id FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        throw new Exception('Invalid email or password.');
    }

    if ($selected_role !== $user['role']) {
        throw new Exception('Selected role does not match this account. Please choose the correct role.');
    }

    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['department_id'] = $user['department_id'] ?? null;

    header('Location: dashboard.php');
    exit();
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: index.php');
    exit();
}
