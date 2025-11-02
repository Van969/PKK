<?php
require __DIR__ . '/../db.php';
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: text/plain; charset=utf-8');

try {
    // Ambil data dari AJAX
    $siswa_id = (int)($_POST['id'] ?? 0);
    $minggu   = (int)($_POST['minggu'] ?? 0);
    $bulan    = (int)($_POST['bulan'] ?? 0);
    $tahun    = (int)($_POST['tahun'] ?? 0);
    $status   = (int)($_POST['status'] ?? 0);

    if (!$siswa_id || !$minggu || !$bulan || !$tahun) {
        exit('❌ Data tidak lengkap');
    }

    // 🔹 Cari atau buat data di pembayaran_baru
    $cek = $pdo->prepare("SELECT id FROM pembayaran_baru WHERE siswa_id = ? AND bulan = ? AND tahun = ?");
    $cek->execute([$siswa_id, $bulan, $tahun]);
    $pembayaran_id = $cek->fetchColumn();

    if (!$pembayaran_id) {
        $ins = $pdo->prepare("INSERT INTO pembayaran_baru (siswa_id, bulan, tahun, created_at) VALUES (?,?,?,NOW())");
        $ins->execute([$siswa_id, $bulan, $tahun]);
        $pembayaran_id = $pdo->lastInsertId();
    }

    // 🔹 Cek apakah minggu sudah ada di pembayaran_mingguan_baru
    $cek2 = $pdo->prepare("SELECT id FROM pembayaran_mingguan_baru WHERE pembayaran_id = ? AND minggu_ke = ?");
    $cek2->execute([$pembayaran_id, $minggu]);
    $mingguan_id = $cek2->fetchColumn();

    if ($mingguan_id) {
        // Update status
        $upd = $pdo->prepare("UPDATE pembayaran_mingguan_baru SET status = ?, tanggal_bayar = NOW() WHERE id = ?");
        $upd->execute([$status, $mingguan_id]);
    } else {
        // Insert baru
        $ins2 = $pdo->prepare("INSERT INTO pembayaran_mingguan_baru (pembayaran_id, minggu_ke, status, tanggal_bayar) VALUES (?,?,?,NOW())");
        $ins2->execute([$pembayaran_id, $minggu, $status]);
    }

    echo "OK";

} catch (PDOException $e) {
    echo "❌ SQL Error: " . $e->getMessage();
}
