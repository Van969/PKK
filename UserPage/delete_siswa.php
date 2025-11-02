<?php
require __DIR__ . '/../db.php';

$id = $_POST['id'] ?? null;
if (!$id) {
    echo "Error: ID siswa tidak ada";
    exit;
}

try {
    // Hapus semua pembayaran terkait
    $stmt = $pdo->prepare("DELETE FROM pembayaran_mingguan WHERE pembayaran_id = ?");
    $stmt->execute([$id]);

    // Hapus data siswa
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->execute([$id]);

    echo "ok"; // respon ke JS
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
