<?php
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');

    if ($nama !== '') {
        $stmt = $pdo->prepare("INSERT INTO siswa (nama) VALUES (?)");
        $stmt->execute([$nama]);
        echo "success";
    } else {
        echo "Nama kosong";
    }
}
