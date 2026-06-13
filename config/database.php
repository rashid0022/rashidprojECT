<?php

$host = "localhost";
$dbname = "suza_clearance_system";
$username = "root";
$password = "1234";

try {

    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    // echo "Database Connected Successfully";

} catch (PDOException $e) {

    die("Connection Failed: " . $e->getMessage());

}
?>