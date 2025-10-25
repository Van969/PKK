<?php
session_start();
header("Content-Type: application/json");
require __DIR__ . '/../db.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(["status" => "error", "message" => "Isi semua field."]);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    $redirect = $user['role'] === 'admin'
        ? 'http://localhost/Project_PKK/AdminPage/index.php'
        : 'http://localhost/Project_PKK/AdminPage/indexuser.php';

    echo json_encode([
        "status" => "success",
        "message" => "Login berhasil",
        "redirect" => $redirect
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Username atau password salah"]);
}
