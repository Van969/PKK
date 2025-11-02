<?php
require __DIR__ . '/../db.php';

if (!isset($_POST['nama']) || trim($_POST['nama']) === '') {
    echo "Nama wajib diisi";
    exit;
}

$nama = trim($_POST['nama']);

try {
    $pdo->beginTransaction();

    // ✅ Insert siswa
    $stmt = $pdo->prepare("INSERT INTO siswa (nama, created_at) VALUES (?, NOW())");
    $stmt->execute([$nama]);

    $pdo->commit();
    echo "ok";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage();
}
