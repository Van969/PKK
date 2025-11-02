<?php
require __DIR__ . '/../db.php';
session_start();
date_default_timezone_set('Asia/Jakarta');

$id = (int)($_POST['id'] ?? 0); // siswa_id
$minggu = (int)($_POST['minggu'] ?? 0);
$status = (int)($_POST['status'] ?? 0);
$bulan = (int)($_POST['bulan'] ?? 0);
$tahun = (int)($_POST['tahun'] ?? 0);

if (!$id || !$minggu || !$bulan || !$tahun) {
    exit('❌ Data tidak lengkap');
}

// --- pastikan data pembayaran_baru ada ---
$stmt = $pdo->prepare("SELECT id FROM pembayaran_baru WHERE siswa_id = ? AND bulan = ? AND tahun = ?");
$stmt->execute([$id, $bulan, $tahun]);
$pembayaran_id = $stmt->fetchColumn();

if (!$pembayaran_id) {
    $ins = $pdo->prepare("INSERT INTO pembayaran_baru (siswa_id, bulan, tahun, created_at) VALUES (?,?,?,NOW())");
    $ins->execute([$id, $bulan, $tahun]);
    $pembayaran_id = $pdo->lastInsertId();
}

// --- periksa apakah minggu ini sudah ada di pembayaran_mingguan_baru ---
$stmt2 = $pdo->prepare("SELECT id FROM pembayaran_mingguan_baru WHERE pembayaran_id = ? AND minggu_ke = ?");
$stmt2->execute([$pembayaran_id, $minggu]);
$id_mingguan = $stmt2->fetchColumn();

if ($id_mingguan) {
    // update
    $upd = $pdo->prepare("UPDATE pembayaran_mingguan_baru SET status=? WHERE id=?");
    $upd->execute([$status, $id_mingguan]);
} else {
    // insert baru
    $ins2 = $pdo->prepare("INSERT INTO pembayaran_mingguan_baru (pembayaran_id, minggu_ke, status, bulan, tahun, siswa_id) VALUES (?,?,?,?,?,?)");
    $ins2->execute([$pembayaran_id, $minggu, $status, $bulan, $tahun, $id]);
}

echo 'ok';
