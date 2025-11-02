<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

// ===============================
// Ambil Bayar per Minggu
// ===============================
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;

// ===============================
// Hitung total dari pembayaran_baru
// ===============================
$total_pembayaran_baru = $pdo
    ->query("SELECT COUNT(*) FROM pembayaran_baru WHERE status = 1")
    ->fetchColumn();
$total_pembayaran_baru_rp = $total_pembayaran_baru * $bayar_per_minggu;

// ===============================
// Hitung total dari pembayaran_mingguan_baru
// ===============================
$total_pembayaran_mingguan_baru = $pdo
    ->query("SELECT COUNT(*) FROM pembayaran_mingguan_baru WHERE status = 1")
    ->fetchColumn();
$total_pembayaran_mingguan_rp = $total_pembayaran_mingguan_baru * $bayar_per_minggu;

// ===============================
// Hitung total dari pembayaran_bulanan (jika ada)
// ===============================
$total_pembayaran_bulanan = 0;
try {
    $total_pembayaran_bulanan = $pdo
        ->query("SELECT COUNT(*) FROM pembayaran_bulanan WHERE status = 1")
        ->fetchColumn();
} catch (Exception $e) {}
$total_pembayaran_bulanan_rp = $total_pembayaran_bulanan * ($bayar_per_minggu * 4);

// ===============================
// Ambil total pemasukan tambahan dari tabel pemasukan
// ===============================
$total_pemasukan_db = $pdo
    ->query("SELECT COALESCE(SUM(jumlah),0) FROM pemasukan")
    ->fetchColumn();

// ===============================
// Hitung total pemasukan keseluruhan
// ===============================
$total_pemasukan = $total_pemasukan_db + $total_pembayaran_baru_rp + $total_pembayaran_mingguan_rp + $total_pembayaran_bulanan_rp;

// ===============================
// Total pengeluaran
// ===============================
$total_pengeluaran = $pdo
    ->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")
    ->fetchColumn();

// ===============================
// Saldo bersih
// ===============================
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar p-3 bg-white shadow-sm" style="width: 250px;">
  <h5 class="mb-4 text-primary">Menu Keuangan</h5>
  <ul class="nav flex-column">
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page==='index.php'?'active':'' ?>" href="index.php">
        <i class="fa fa-home me-2"></i> Halaman Utama
      </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page==='profile.php'?'active':'' ?>" href="profile.php">
        <i class="fa fa-user me-2"></i> Profil Siswa
      </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page==='aksi_fix.php'?'active':'' ?>" href="aksi_fix.php">
        <i class="fa fa-money-bill me-2"></i> Tambah & Edit Pembayaran
      </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page==='pengeluaran.php'?'active':'' ?>" href="pengeluaran.php">
        <i class="fa fa-arrow-down me-2"></i> Pengeluaran
      </a>
    </li>
    <li class="nav-item mb-2">
      <a class="nav-link <?= $current_page==='bayar.php'?'active':'' ?>" href="bayar.php">
        <i class="fa fa-money-bill me-2"></i> Bayar Per-Minggu
      </a>
    </li>
  </ul>

  <div class="mt-4">
    <!-- Total Pemasukan -->
    <div class="card summary-card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Total Pemasukan</small>
          <div id="totalPemasukanSidebar" class="h5 mt-2 text-success">
            Rp <?= number_format($total_pemasukan, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-arrow-up text-success fs-4"></i>
      </div>
    </div>

    <!-- Total Pengeluaran -->
    <div class="card summary-card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Total Pengeluaran</small>
          <div id="totalPengeluaranSidebar" class="h5 mt-2 text-danger">
            Rp <?= number_format($total_pengeluaran, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-arrow-down text-danger fs-4"></i>
      </div>
    </div>

    <!-- Saldo Bersih -->
    <div class="card summary-card mb-3 border-0 shadow-sm">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <small>Saldo Bersih</small>
          <div id="saldoBersihSidebar" class="h5 mt-2 <?= $saldo_bersih < 0 ? 'text-danger' : 'text-success' ?>">
            Rp <?= number_format($saldo_bersih, 0, ',', '.') ?>
          </div>
        </div>
        <i class="fa fa-wallet <?= $saldo_bersih < 0 ? 'text-danger' : 'text-success' ?> fs-4"></i>
      </div>
    </div>
  </div>
</aside>

<script>
// 🔁 Auto reload halaman penuh setelah setiap aksi form di halaman lain
document.addEventListener('sidebarUpdate', () => {
  location.reload();
});
</script>
