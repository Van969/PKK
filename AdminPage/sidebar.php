<?php
require __DIR__ . '/../db.php';

// === Hitung total pemasukan dan pengeluaran ===
// Ambil total dari tabel pemasukan bulanan + pembayaran mingguan
$total_pemasukan_bulanan = $pdo->query("SELECT COALESCE(SUM(total_pemasukan),0) FROM pemasukan")->fetchColumn();

// Karena tidak ada kolom jumlah_bayar, kita hitung berdasarkan status pembayaran
$bayar_per_minggu = 5000;
$total_bayar_aktif = $pdo->query("SELECT COUNT(*) FROM pembayaran_mingguan WHERE status = 1")->fetchColumn();
$total_pembayaran_mingguan = $total_bayar_aktif * $bayar_per_minggu;

$total_pemasukan = $total_pemasukan_bulanan + $total_pembayaran_mingguan;

// Total pengeluaran (pakai kolom amount)
$total_pengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// Saldo bersih
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// === Hitung pemasukan per minggu dan per bulan ===
$bulan = date('m');
$tahun = date('Y');
$total_mingguan = [];

for ($i = 1; $i <= 4; $i++) {
    $start_date = date('Y-m-d', strtotime("$tahun-$bulan-01 +".(($i-1)*7)." days"));
    $end_date = date('Y-m-d', strtotime("$start_date +6 days"));

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pembayaran_mingguan 
        WHERE status = 1 AND DATE(tanggal_bayar) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $jumlah_bayar_minggu = $stmt->fetchColumn();
    $total_mingguan[$i] = $jumlah_bayar_minggu * $bayar_per_minggu;
}

$total_bulan_ini = array_sum($total_mingguan);


// === Deteksi halaman aktif ===
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar p-3 bg-white shadow-sm" style="width: 250px;">
  <h5 class="mb-4 text-primary">Menu Keuangan</h5>
  <ul class="nav flex-column">
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" href="index.php">
        <i class="fa fa-home me-2"></i> Halaman Utama
      </a>
    </li>

    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page === 'profile.php' ? 'active' : '' ?>" href="profile.php">
        <i class="fa fa-user me-2"></i> Profil Siswa
      </a>
    </li>

    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page === 'aksi.php' ? 'active' : '' ?>" href="aksi.php">
        <i class="fa fa-money-bill me-2"></i> Tambah & Edit Pembayaran
      </a>
    </li>

    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page === 'pengeluaran.php' ? 'active' : '' ?>" href="pengeluaran.php">
        <i class="fa fa-arrow-down me-2"></i> Pengeluaran
      </a>
    </li>
  </ul>

  <div class="mt-4">
    <div class="card summary-card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Total Pemasukan</small>
          <div class="h5 mt-2 text-success">
            Rp <?= number_format($total_pemasukan, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-arrow-up text-success fs-4"></i>
      </div>
    </div>

    <div class="card summary-card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Total Pengeluaran</small>
          <div class="h5 mt-2 text-danger">
            Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-arrow-down text-danger fs-4"></i>
      </div>
    </div>

    <div class="card summary-card border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Saldo Bersih</small>
          <div class="h5 mt-2 <?= $saldo_bersih < 0 ? 'text-danger' : 'text-success' ?>">
            Rp <?= number_format($saldo_bersih, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-wallet <?= $saldo_bersih < 0 ? 'text-danger' : 'text-success' ?> fs-4"></i>
      </div>
    </div>

    <!-- === Pemasukan Mingguan / Bulanan === -->
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-body">
        <h6 class="text-muted mb-3">Pemasukan Kas <?= date('F Y') ?></h6>
        <ul class="list-group list-group-flush small">
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <li class="list-group-item d-flex justify-content-between">
              <span>Minggu <?= $i ?></span>
              <strong>Rp <?= number_format($total_mingguan[$i], 0, ',', '.') ?></strong>
            </li>
          <?php endfor; ?>
          <li class="list-group-item d-flex justify-content-between border-top">
            <strong>Total Bulan Ini</strong>
            <strong class="text-success">Rp <?= number_format($total_bulan_ini, 0, ',', '.') ?></strong>
          </li>
        </ul>
      </div>
    </div>
  </div>
</aside>
