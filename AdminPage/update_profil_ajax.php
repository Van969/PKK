<?php
session_start();
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Anda belum login.']);
    exit;
}

$username = $_SESSION['username'];
$nickname = $_POST['nickname'] ?? '';
$current_password = $_POST['current_password'] ?? '';
$new_password = $_POST['new_password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

try {
    // ambil data user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
        exit;
    }

    // update nickname
    if (!empty($nickname)) {
        $stmt = $pdo->prepare("UPDATE users SET nickname = ? WHERE username = ?");
        $stmt->execute([$nickname, $username]);
    }

    // update password jika diisi
    if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
        if (!password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Password lama salah.']);
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Konfirmasi password baru tidak cocok.']);
            exit;
        }

        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->execute([$hashed, $username]);
    }

    echo json_encode(['success' => true, 'message' => 'Perubahan berhasil disimpan.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan server.']);
}
