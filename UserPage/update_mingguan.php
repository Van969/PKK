<?php
require __DIR__ . '/../db.php';

$id     = (int)($_POST['id'] ?? 0);
$minggu = (int)($_POST['minggu'] ?? 0);
$status = (int)($_POST['status'] ?? 0);
$bulan  = isset($_POST['bulan']) ? (int)$_POST['bulan'] : null;
$tahun  = isset($_POST['tahun']) ? (int)$_POST['tahun'] : null;



try {
  // === Pastikan pembayaran induk per siswa ada ===
  $stmt = $pdo->prepare("SELECT id FROM pembayaran WHERE siswa_id=?");
  $stmt->execute([$id]);
  $pembayaran_id = $stmt->fetchColumn();

  if (!$pembayaran_id) {
    // kalau belum ada, buat baru
    $ins = $pdo->prepare("INSERT INTO pembayaran (siswa_id, created_at) VALUES (?, NOW())");
    $ins->execute([$id]);
    $pembayaran_id = $pdo->lastInsertId();
  }

  // === Cek apakah minggu ini sudah ada datanya (spesifik bulan+tahun) ===
  $cek = $pdo->prepare("SELECT id FROM pembayaran_mingguan 
                        WHERE pembayaran_id=? AND minggu_ke=? AND bulan=? AND tahun=?");
  $cek->execute([$pembayaran_id, $minggu, $bulan, $tahun]);
  $ada = $cek->fetchColumn();

  if ($ada) {
    // update status jika sudah ada
    $upd = $pdo->prepare("UPDATE pembayaran_mingguan 
                          SET status=?, tanggal_bayar=NOW() 
                          WHERE id=?");
    $upd->execute([$status, $ada]);
  } else {
    // kalau belum ada, tambahkan
    $ins2 = $pdo->prepare("INSERT INTO pembayaran_mingguan 
                           (pembayaran_id, minggu_ke, bulan, tahun, status, tanggal_bayar) 
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $ins2->execute([$pembayaran_id, $minggu, $bulan, $tahun, $status]);
  }

  echo 'ok';
} catch (PDOException $e) {
  http_response_code(500);
  echo "Gagal memperbarui data: " . $e->getMessage();
}
