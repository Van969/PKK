<?php
require __DIR__ . '/../db.php';

if (isset($_POST['id'], $_POST['minggu'], $_POST['status'], $_POST['bulan'], $_POST['tahun'])) {
    $id     = (int)$_POST['id'];
    $minggu = (int)$_POST['minggu'];
    $status = (int)$_POST['status'];
    $bulan  = $_POST['bulan'];
    $tahun  = $_POST['tahun'];

    // Cek apakah sudah ada datanya untuk ID, minggu, bulan, dan tahun ini
    $cek = $pdo->prepare("SELECT id FROM pembayaran_mingguan WHERE pembayaran_id = ? AND minggu_ke = ? AND bulan = ? AND tahun = ?");
    $cek->execute([$id, $minggu, $bulan, $tahun]);

    if ($cek->rowCount() > 0) {
        // update status
        $stmt = $pdo->prepare("UPDATE pembayaran_mingguan 
                               SET status = ?, tanggal_bayar = NOW() 
                               WHERE pembayaran_id = ? AND minggu_ke = ? AND bulan = ? AND tahun = ?");
        $stmt->execute([$status, $id, $minggu, $bulan, $tahun]);
    } else {
        // buat data baru
        $stmt = $pdo->prepare("INSERT INTO pembayaran_mingguan (pembayaran_id, minggu_ke, bulan, tahun, jumlah_bayar, status, tanggal_bayar)
                               VALUES (?, ?, ?, ?, 5000, ?, NOW())");
        $stmt->execute([$id, $minggu, $bulan, $tahun, $status]);
    }

    echo "ok";
    exit;
}

echo "error";
