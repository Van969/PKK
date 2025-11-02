<?php
require __DIR__ . '/../db.php';
header("Content-Type: application/json");

// Ambil data POST
$username = $_POST['username'] ?? '';
$nickname = $_POST['nickname'] ?? '';
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'user';

// Validasi input
if ($username === '' || $password === '' || $nickname === '') {
    echo json_encode(["status" => "error", "message" => "Data tidak boleh kosong"]);
    exit;
}

// Cek jika admin sudah ada
if ($role === "admin") {
    $cekAdmin = $pdo->query("SELECT id FROM users WHERE role='admin'");
    if ($cekAdmin->fetch()) {
        echo json_encode(["status"=>"error","message"=>"Admin sudah ada!"]);
        exit;
    }
}

// Cek username unik
$stmt = $pdo->prepare("SELECT id FROM users WHERE username=?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo json_encode(["status"=>"error","message"=>"Username sudah terdaftar"]);
    exit;
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $pdo->prepare("INSERT INTO users (username, nickname, password, role) VALUES (?, ?, ?, ?)");
$ok = $stmt->execute([$username, $nickname, $hashedPassword, $role]);

if ($ok) {
    echo json_encode(["status"=>"success","message"=>"Register berhasil"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Gagal register"]);
}
