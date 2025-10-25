<?php
include "db.php";
header("Content-Type: application/json");

// Ambil data POST dengan aman
$username = $_POST['username'] ?? '';
$nickname = $_POST['nickname'] ?? '';
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'user'; // default 'user'

// Cek input kosong
if ($username === '' || $password === '' || $nickname === '') {
    echo json_encode(["status" => "error", "message" => "Data tidak boleh kosong"]);
    exit;
}

// Cek kalau role admin sudah ada
if ($role === "admin") {
    $cekAdmin = $conn->query("SELECT * FROM users WHERE role='admin'");
    if ($cekAdmin->num_rows > 0) {
        echo json_encode(["status"=>"error","message"=>"Admin sudah ada!"]);
        exit;
    }
}

// Cek username unik
$check = $conn->prepare("SELECT * FROM users WHERE username=?");
$check->bind_param("s", $username);
$check->execute();
$res = $check->get_result();
if ($res->num_rows > 0) {
    echo json_encode(["status"=>"error","message"=>"Username sudah terdaftar"]);
    exit;
}

// Hash password sebelum simpan (biar aman)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Simpan user baru
$stmt = $conn->prepare("INSERT INTO users (username, nickname, password, role) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $username, $nickname, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode(["status"=>"success","message"=>"Register berhasil"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Gagal register"]);
}
?>
