<?php
require __DIR__ . '/../db.php'; // sesuaikan path-nya jika perlu

$id = (int)($_POST['id'] ?? 0); // id siswa
$minggu = (int)($_POST['minggu'] ?? 0);
$status = (int)($_POST['status'] ?? 0);
$bulan = (int)($_POST['bulan'] ?? date('n'));
$tahun = (int)($_POST['tahun'] ?? date('Y'));

if (!$id || !$minggu) {
  http_response_code(400);
  exit('error');
}

try {
  // === Pastikan ada data pembayaran induk ===
  $stmt = $pdo->prepare("SELECT id FROM pembayaran WHERE siswa_id=? AND bulan=? AND tahun=?");
  $stmt->execute([$id, $bulan, $tahun]);
  $pembayaran_id = $stmt->fetchColumn();

  // Jika belum ada, buat baru
  if (!$pembayaran_id) {
    $ins = $pdo->prepare("INSERT INTO pembayaran (siswa_id, bulan, tahun, created_at) VALUES (?, ?, ?, NOW())");
    $ins->execute([$id, $bulan, $tahun]);
    $pembayaran_id = $pdo->lastInsertId();
  }

  // === Cek data mingguan ===
  $cek = $pdo->prepare("SELECT id FROM pembayaran_mingguan 
                        WHERE pembayaran_id=? AND minggu_ke=? AND bulan=? AND tahun=?");
  $cek->execute([$pembayaran_id, $minggu, $bulan, $tahun]);
  $ada = $cek->fetch();

  if ($ada) {
    // Update status jika sudah ada
    $upd = $pdo->prepare("UPDATE pembayaran_mingguan 
                          SET status=?, tanggal_bayar=NOW() 
                          WHERE pembayaran_id=? AND minggu_ke=? AND bulan=? AND tahun=?");
    $upd->execute([$status, $pembayaran_id, $minggu, $bulan, $tahun]);
  } else {
    // Tambahkan data baru
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
