<?php
session_start();
require_once 'config/database.php';

if (!isset($_POST['register_btn'])) {
    header('Location: register.php');
    exit();
}

$full_name = trim($_POST['full_name'] ?? '');
$registration_number = trim($_POST['registration_number'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

try {
    if ($full_name === '' || $registration_number === '' || $email === '' || $phone === '' || $password === '' || $confirm_password === '') {
        throw new InvalidArgumentException('Please fill in all fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Please enter a valid email address.');
    }

    if ($password !== $confirm_password) {
        throw new InvalidArgumentException('Passwords do not match.');
    }

    if (strlen($password) < 6) {
        throw new InvalidArgumentException('Password must be at least 6 characters long.');
    }

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? OR registration_number = ?');
    $stmt->execute([$email, $registration_number]);
    $exists = $stmt->fetchColumn();

    if ($exists > 0) {
        throw new RuntimeException('Registration number or email is already registered.');
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $faculty = trim($_POST['faculty'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = trim($_POST['year'] ?? '');

    if ($faculty === '' || $course === '' || $year === '') {
        throw new InvalidArgumentException('Please fill in faculty, course, and academic year.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('INSERT INTO users (full_name, registration_number, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$full_name, $registration_number, $email, $phone, $hashed_password, 'student']);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, faculty, course, academic_year) VALUES (?, ?, ?, ?)');
    $stmt->execute([$user_id, $faculty, $course, $year]);

    $pdo->commit();

    $_SESSION['success'] = 'Registration successful! You can now login with your email and password.';
    header('Location: index.php');
    exit();
} catch (InvalidArgumentException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('Location: register.php');
    exit();
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = $e->getMessage();
    header('Location: register.php');
    exit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = 'Something went wrong during registration. Please try again.';
    header('Location: register.php');
    exit();
}

