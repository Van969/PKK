<?php
require 'db.php';

// Total pemasukan dari tabel pemasukan
$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pemasukan");
$total_pemasukan_db = $stmt->fetchColumn();

// Total jumlah membayar dari tabel pembayaran
$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_membayar),0) FROM pembayaran");
$total_pembayaran = $stmt->fetchColumn();

// Total pengeluaran
$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran");
$total_pengeluaran = $stmt->fetchColumn();

// Gabungkan pemasukan dari dua sumber
$total_pemasukan = $total_pemasukan_db + $total_pembayaran;

// Hitung saldo bersih
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// Ambil data pembayaran
$stmt = $pdo->query("SELECT * FROM pembayaran ORDER BY id DESC");
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Jika tidak ada data, tetap array kosong
if (!$pembayaran) {
    $pembayaran = [];
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Kas</title>

  <!-- Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- FontAwesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="d-flex">
  <!-- Sidebar -->
  <aside class="sidebar p-3">
    <h5 class="mb-4">Menu Keuangan</h5>
    <ul class="nav flex-column">
      <li class="nav-item mb-2">
        <a class="nav-link active" href="#"><i class="fa fa-home me-2"></i> Halaman Utama</a>
      </li>
      <li class="nav-item mb-2">
        <a class="nav-link" href="#"><i class="fa fa-wallet me-2"></i> Pemasukan & Pengeluaran</a>
      </li>
    </ul>

    <div class="mt-4">
      <div class="card summary-card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <small>Total Pemasukan</small>
            <div class="h5 mt-2 text-success">Rp <?= number_format($total_pemasukan,0,',','.') ?></div>
          </div>
          <i class="fa fa-arrow-up text-success fs-4"></i>
        </div>
      </div>
      <div class="card summary-card mb-3">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <small>Total Pengeluaran</small>
            <div class="h5 mt-2 text-danger">Rp <?= number_format($total_pengeluaran,0,',','.') ?></div>
          </div>
          <i class="fa fa-arrow-down text-danger fs-4"></i>
        </div>
      </div>
      <div class="card summary-card">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <small>Saldo Bersih</small>
            <div class="h5 mt-2 <?= $saldo_bersih<0?'text-danger':'text-success' ?>">
              Rp <?= number_format($saldo_bersih,0,',','.') ?>
            </div>
          </div>
          <i class="fa fa-wallet <?= $saldo_bersih<0?'text-danger':'text-success' ?> fs-4"></i>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main content -->
  <main class="flex-fill p-4 bg-light">
    <div class="container-fluid">

      <!-- Header + Tombol Tambah -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Pembayaran Kas Kelas</h3>
      </div>
      <p class="text-muted">Kelola Pembayaran Kas Kelas</p>

      <!-- Tabel Data -->
      <div class="table-responsive card p-3 shadow-sm">
        <table class="table table-borderless align-middle">
          <thead>
            <tr class="small text-muted">
              <th>Nama</th>
              <th>Jumlah Membayar</th>
              <th>Belum Membayar</th>
              <th>Total Tagihan</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach($pembayaran as $row): ?>
            <tr>
              <td><?= htmlspecialchars($row['name']) ?></td>
              <td class="text-success">Rp <?= number_format($row['jumlah_membayar'],0,',','.') ?></td>
              <td class="text-danger">Rp <?= number_format($row['belum_membayar'],0,',','.') ?></td>
              <td>Rp <?= number_format($row['total_tagihan'],0,',','.') ?></td>
              <td>
                <?php
                  $badge = match($row['status']){
                    'Lunas'=>'dark','Sebagian'=>'secondary','Belum Bayar'=>'danger',default=>'secondary'
                  };
                ?>
                <span class="badge bg-<?= $badge ?>"><?= $row['status'] ?></span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if(empty($pembayaran)): ?>
            <tr><td colspan="6" class="text-center text-muted">Belum ada data.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
