<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require __DIR__ . '/../db.php';

// 🔐 Cek login admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../LoginPage/HalamanLogin.html");
    exit();
}

$username = $_SESSION['username'];

// 📅 Variabel bulan & tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : date('Y');

// 🧮 Hitung total pemasukan, pembayaran, pengeluaran
$stmt = $pdo->query("SELECT COALESCE(SUM(total_pemasukan),0) FROM pemasukan");
$total_pemasukan_db = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran_mingguan");
$total_pembayaran = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran");
$total_pengeluaran = $stmt->fetchColumn();

$total_pemasukan = $total_pemasukan_db + $total_pembayaran;
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// 📄 Ambil data pembayaran utama
$stmt = $pdo->prepare("
    SELECT * FROM pembayaran
    WHERE bulan = ? AND tahun = ?
    ORDER BY name ASC
");
$stmt->execute([$bulan, $tahun]);
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dashboard Kas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="d-flex">
  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Konten Utama -->
  <main class="flex-fill p-4 bg-light">
    <div class="container-fluid">

      <!-- Header -->
      <?php include __DIR__.'/header.php'; ?>

      <!-- Judul -->
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Data Pembayaran Kas Kelas</h3>
      </div>

      <!-- Pencarian -->
      <div class="mb-3">
        <input type="text" id="cariNama" class="form-control" placeholder="Cari nama siswa...">
      </div>

      <!-- Tabel Pembayaran Mingguan -->
      <div class="table-responsive card p-3 shadow-sm border-0">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Pembayaran Kas Per Minggu - <?= date('F Y', strtotime("$tahun-$bulan-01")) ?></h5>
          <div>
            <button class="btn btn-outline-secondary btn-sm" id="prevMonth"><i class="fa fa-chevron-left"></i></button>
            <button class="btn btn-outline-secondary btn-sm" id="nextMonth"><i class="fa fa-chevron-right"></i></button>
          </div>
        </div>

        <table class="table table-bordered text-center align-middle">
          <thead class="table-light">
            <tr>
              <th>Nama</th>
              <th>Minggu 1</th>
              <th>Minggu 2</th>
              <th>Minggu 3</th>
              <th>Minggu 4</th>
              <th>Total Belum Bayar</th>
            </tr>
          </thead>
          <tbody>
  <?php if (!empty($pembayaran)): ?>
    <?php foreach ($pembayaran as $row): ?>
      <tr data-id="<?= $row['id'] ?>">
        <td><?= htmlspecialchars($row['name']) ?></td>

        <?php
          $total_bayar_siswa = 0;
          $total_belum_bayar = 0;
          $bayar_per_minggu = 2000; // nominal kas per minggu

          for ($i = 1; $i <= 4; $i++):
            // Cek status bayar tiap minggu
            $stmt2 = $pdo->prepare("
              SELECT status, jumlah_bayar 
              FROM pembayaran_mingguan 
              WHERE pembayaran_id = ? AND minggu_ke = ?
            ");
            $stmt2->execute([$row['id'], $i]);
            $rowMinggu = $stmt2->fetch(PDO::FETCH_ASSOC);

            $status = $rowMinggu['status'] ?? 0;
            $jumlah_bayar = $rowMinggu['jumlah_bayar'] ?? 0;
            $checked = $status ? 'checked' : '';

            // Hitung total yang sudah dibayar
            $total_bayar_siswa += $jumlah_bayar;

            // Hitung sisa belum bayar
            if (!$status) $total_belum_bayar += $bayar_per_minggu;
        ?>
          <td>
            <input type="checkbox" class="form-check-input bayar-checkbox"
                   data-id="<?= $row['id'] ?>" data-minggu="<?= $i ?>" <?= $checked ?>>
          </td>
        <?php endfor; ?>

        <td>
          <?php if ($total_belum_bayar > 0): ?>
            <span class="text-danger">Rp <?= number_format($total_belum_bayar, 0, ',', '.') ?></span>
            <small class="text-muted">/ Rp <?= number_format($bayar_per_minggu * 4, 0, ',', '.') ?></small>
          <?php else: ?>
            <span class="text-success fw-bold">Lunas</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  <?php else: ?>
    <tr><td colspan="6" class="text-muted text-center">Belum ada data.</td></tr>
  <?php endif; ?>
</tbody>

        </table>
      </div>

      <!-- Data Pengeluaran -->
      <div class="row mt-4">
        <div class="col-md-12">
          <div class="card p-3 shadow-sm border-0">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h5 class="mb-0">Data Pengeluaran</h5>
            </div>
            <table class="table table-bordered align-middle">
              <thead class="table-light">
                <tr>
                  <th>Judul</th>
                  <th>Jumlah</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM pengeluaran ORDER BY id DESC");
                $pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($pengeluaran)):
                  foreach ($pengeluaran as $row): ?>
                    <tr>
                      <td><?= htmlspecialchars($row['title']) ?></td>
                      <td class="text-danger">Rp <?= number_format($row['amount'], 0, ',', '.') ?></td>
                      <td><?= htmlspecialchars($row['note'] ?? '-') ?></td>
                    </tr>
                  <?php endforeach;
                else: ?>
                  <tr><td colspan="3" class="text-center text-muted">Belum ada data pengeluaran.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Footer -->
<?php include 'footer.php'; ?>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ✅ Checkbox update handler
document.querySelectorAll('.bayar-checkbox').forEach(cb => {
  cb.addEventListener('change', async () => {
    const id = cb.dataset.id;
    const minggu = cb.dataset.minggu;
    const status = cb.checked ? 1 : 0;
    await fetch('update_mingguan.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({id, minggu, status})
    });
  });
});

// 🔁 Navigasi bulan
document.getElementById('prevMonth').onclick = () => {
  const url = new URL(window.location.href);
  let bulan = parseInt(url.searchParams.get('bulan') || <?= date('n') ?>);
  let tahun = parseInt(url.searchParams.get('tahun') || <?= date('Y') ?>);
  bulan--; if (bulan < 1) { bulan = 12; tahun--; }
  url.searchParams.set('bulan', bulan);
  url.searchParams.set('tahun', tahun);
  window.location.href = url.toString();
};
document.getElementById('nextMonth').onclick = () => {
  const url = new URL(window.location.href);
  let bulan = parseInt(url.searchParams.get('bulan') || <?= date('n') ?>);
  let tahun = parseInt(url.searchParams.get('tahun') || <?= date('Y') ?>);
  bulan++; if (bulan > 12) { bulan = 1; tahun++; }
  url.searchParams.set('bulan', bulan);
  url.searchParams.set('tahun', tahun);
  window.location.href = url.toString();
};

// 🔍 Pencarian
document.addEventListener('DOMContentLoaded', function(){
  const inputCari = document.getElementById('cariNama');
  const table = document.querySelector('tbody');
  if(inputCari && table){
    inputCari.addEventListener('keyup', function(){
      const filter = this.value.toLowerCase();
      table.querySelectorAll('tr').forEach(tr=>{
        const nama = tr.cells[0]?.textContent.toLowerCase()||'';
        tr.style.display = nama.includes(filter)?'':'none';
      });
    });
  }
});
</script>

</body>
</html>
