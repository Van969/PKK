<?php
require __DIR__ . '/../db.php';

$id = $_POST['id'] ?? 0;
$nama = trim($_POST['nama'] ?? '');

if (!$id || $nama === '') {
    echo "Invalid";
    exit;
}

try {
    $pdo->beginTransaction();

    // Ambil nama sebelumnya
    $old = $pdo->prepare("SELECT nama FROM siswa WHERE id=?");
    $old->execute([$id]);
    $oldNama = $old->fetchColumn();

    // Update siswa
    $pdo->prepare("UPDATE siswa SET nama=? WHERE id=?")
        ->execute([$nama, $id]);

    // Jika ada di tabel users → update username juga
    $pdo->prepare("UPDATE users SET username=? WHERE username=?")
        ->execute([$nama, $oldNama]);

    $pdo->commit();
    echo "ok";
} catch (Exception $e) {
    $pdo->rollBack();
    echo $e->getMessage();
}
