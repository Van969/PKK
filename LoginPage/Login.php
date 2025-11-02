<?php
session_start();
require __DIR__ . '/../db.php';

// Jalankan hanya jika method = POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Kalau dibuka langsung di browser (GET), tampilkan pesan biasa
    header("Content-Type: text/html; charset=UTF-8");
    echo "<h3 style='color:gray;text-align:center;margin-top:50px;'>Halaman ini hanya bisa diakses melalui form login.</h3>";
    exit;
}

// Kalau dari form login atau fetch() POST, baru kirim JSON
header("Content-Type: application/json");

// 🧩 Bisa terima JSON, x-www-form-urlencoded, atau FormData()
$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
$username = '';
$password = '';

if (isset($input['username']) && isset($input['password'])) {
    // JSON body
    $username = trim($input['username']);
    $password = trim($input['password']);
} elseif (isset($_POST['username']) && isset($_POST['password'])) {
    // Form biasa
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
} else {
    // FormData edge case
    parse_str($raw, $parsed);
    $username = trim($parsed['username'] ?? '');
    $password = trim($parsed['password'] ?? '');
}

// 🔐 Validasi
if (!$username || !$password) {
    echo json_encode(["status" => "error", "message" => "Isi semua field."]);
    exit;
}

// 🔍 Ambil user
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// 🔑 Verifikasi password
if ($user && password_verify($password, $user['password'])) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];

    $redirect = $user['role'] === 'admin'
        ? 'http://localhost/Project_PKK/AdminPage/index.php'
        : 'http://localhost/Project_PKK/UserPage/indexuser.php';

    echo json_encode([
        "status" => "success",
        "message" => "Login berhasil",
        "redirect" => $redirect
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Username atau password salah"]);
}
