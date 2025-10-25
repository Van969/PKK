<?php
require __DIR__ . '/../db.php';

// ===== Hitung total pemasukan dari tabel pemasukan =====
$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pemasukan");
$total_pemasukan_db = $stmt->fetchColumn();

// ===== Hitung total jumlah membayar dari tabel pembayaran =====
$stmt = $pdo->query("SELECT COALESCE(SUM(jumlah_membayar),0) FROM pembayaran");
$total_pembayaran = $stmt->fetchColumn();

// ===== Hitung total pengeluaran =====
$stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran");
$total_pengeluaran = $stmt->fetchColumn();

// ===== Gabungkan total pemasukan =====
$total_pemasukan = $total_pemasukan_db + $total_pembayaran;

// ===== Hitung saldo bersih =====
$saldo_bersih = $total_pemasukan - $total_pengeluaran;


// ===== Proses tambah data dari modal Tambah =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['edit_id'])) {
  $name = $_POST['name'] ?? '';
  $jumlah_membayar = isset($_POST['jumlah_membayar']) ? (int)$_POST['jumlah_membayar'] : 0;
  $belum_membayar = isset($_POST['belum_membayar']) ? (int)$_POST['belum_membayar'] : 0;
  $total_tagihan = $jumlah_membayar + $belum_membayar;
  $status = ($belum_membayar === 0) ? 'Lunas' : 'Belum Bayar';

  $stmt = $pdo->prepare("INSERT INTO pembayaran (name, jumlah_membayar, belum_membayar, total_tagihan, status) VALUES (?,?,?,?,?)");
  $stmt->execute([$name, $jumlah_membayar, $belum_membayar, $total_tagihan, $status]);

  // Jika request AJAX (dari fetch)
  if (isset($_POST['ajax'])) {
    echo 'OK'; // 🟢 respon agar JS tahu sukses
    exit;
  }

  header("Location: aksi.php");
  exit;
}


// Proses edit data dari modal Edit
// ===== Proses edit data dari modal Edit =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['edit_id'])) {
  $id = (int)$_GET['edit_id'];
  $name = $_POST['name'] ?? '';
  $jumlah_membayar = isset($_POST['jumlah_membayar']) ? (int)$_POST['jumlah_membayar'] : 0;
  $belum_membayar = isset($_POST['belum_membayar']) ? (int)$_POST['belum_membayar'] : 0;
  $total_tagihan = $jumlah_membayar + $belum_membayar;
  $status = ($belum_membayar === 0) ? 'Lunas' : 'Belum Bayar';

  $stmt = $pdo->prepare("UPDATE pembayaran SET name=?, jumlah_membayar=?, belum_membayar=?, total_tagihan=?, status=? WHERE id=?");
  $stmt->execute([$name, $jumlah_membayar, $belum_membayar, $total_tagihan, $status, $id]);

  if (isset($_POST['ajax'])) {
    echo 'OK';
    exit;
  }

  header("Location: aksi.php");
  exit;
}


// Ambil semua data pembayaran
$stmt = $pdo->query("SELECT * FROM pembayaran ORDER BY id DESC");
$pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kelola Pembayaran Kas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
  <style>
    /* Hilangkan backdrop Bootstrap untuk modal */
    /*.modal-backdrop { display: none !important; }
    /* Modal tetap di atas konten */
    .modal { z-index: 1050; }
  </style>
</head>
<body>
  
<div class="d-flex konten">
  <?php include 'sidebar_aksi.php'; ?>

  <main class="flex-fill p-4 bg-light">
    <div class="container-fluid">
      <!-- Header -->
      <?php include __DIR__.'/header.php'; ?>
<div class="row">
  
  <!-- Tabel Pengeluaran -->
  <div class="col-md-12">
    <div class="card p-3 shadow-sm border-0">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Data Pengeluaran</h5>
        <button class="btn btn-success btn-sm" id="btnTambahPengeluaran">
          <i class="fa fa-plus"></i> Tambah
        </button>
      </div>
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>Judul</th>
            <th>Jumlah</th>
            <th>Keterangan</th>
            <th width="110">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $stmt = $pdo->query("SELECT * FROM pengeluaran ORDER BY id DESC");
          $pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
          if (!empty($pengeluaran)):
            foreach ($pengeluaran as $row): ?>
              <tr data-id="<?= $row['id'] ?>">
                <td data-col="judul"><?= htmlspecialchars($row['title']) ?></td>
                <td data-col="jumlah" class="text-danger">
                Rp <?= number_format($row['amount'], 0, ',', '.') ?>
              </td>
                <td data-col="keterangan"><?= htmlspecialchars($row['note'] ?? '-') ?></td>

                <td>
                  <button class="btn btn-warning btn-sm btnEditPengeluaran"
                          data-id="<?= $row['id'] ?>"
                          data-title="<?= htmlspecialchars($row['title']) ?>"
                          data-amount="<?= $row['amount'] ?>"
                          data-desc="<?= htmlspecialchars($row['note']) ?>">
                    <i class="fa fa-edit"></i>
                  </button>
                  <a href="delete_pengeluaran.php?id=<?= $row['id'] ?>"
                     class="btn btn-danger btn-sm"
                     onclick="return confirm('Yakin ingin hapus data ini?')">
                    <i class="fa fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach;
          else: ?>
            <tr><td colspan="4" class="text-center text-muted">Belum ada data pengeluaran.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<!-- ===================== Modal Tambah/Edit Pengeluaran ===================== -->
<div class="modal fade" id="modalPengeluaran" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title"><i class="fa fa-plus"></i> <span id="judulModalPengeluaran">Tambah Pengeluaran</span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formPengeluaran" method="POST" action="simpan_pengeluaran.php">
        <div class="modal-body">
          <input type="hidden" name="id" id="idPengeluaran">
          <div class="mb-3">
            <label class="form-label">Judul</label>
            <input type="text" name="title" id="titlePengeluaran" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Jumlah</label>
            <input type="number" name="amount" id="amountPengeluaran" class="form-control" required step="1000" min="0">
          </div>
          <div class="mb-3">
            <label class="form-label">Keterangan</label>
            <textarea name="description" id="descPengeluaran" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Simpan</button>
        </div>
      </form>
    </div>
  </div>
          </main>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>
