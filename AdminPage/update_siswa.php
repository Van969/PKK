<?php
require __DIR__ . '/../db.php';

$id = $_POST['id'] ?? 0;
$nama = trim($_POST['nama'] ?? '');

if(!$id || $nama === ''){
    echo "Invalid";
    exit;
}

$stmt = $pdo->prepare("UPDATE siswa SET nama=? WHERE id=?");
$stmt->execute([$nama, $id]);

echo "ok";
