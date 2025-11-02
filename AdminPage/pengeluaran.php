
<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

// ===============================
// Hitung Total Pemasukan (pakai kolom 'jumlah')
// ===============================
$total_pemasukan_db = $pdo->query("SELECT COALESCE(SUM(jumlah),0) FROM pemasukan")->fetchColumn();


// ===============================
// Hitung total dari pembayaran mingguan
// ===============================
$total_pembayaran_mingguan = $pdo->query("
    SELECT COALESCE(COUNT(*),0) * 5000
    FROM pembayaran_mingguan_baru
    WHERE status = 1
")->fetchColumn();

// ===============================
// Hitung total pembayaran bulanan (jika ada)
// ===============================
$total_pembayaran_bulanan = 0;
try {
    $total_pembayaran_bulanan = $pdo->query("
        SELECT COALESCE(COUNT(*),0) * (5000 * 4)
        FROM pembayaran_bulanan
        WHERE status = 1
    ")->fetchColumn();
} catch (Exception $e) {}

// ===============================
// Total pemasukan keseluruhan
// ===============================
$total_pemasukan = $total_pemasukan_db + $total_pembayaran_mingguan + $total_pembayaran_bulanan;

// ===============================
// Hitung Total Pengeluaran
// ===============================
$total_pengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// ===============================
// Hitung Saldo Bersih
// ===============================
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// ===============================
// Ambil Semua Data Pengeluaran
// ===============================
$stmt = $pdo->query("SELECT * FROM pengeluaran ORDER BY id DESC");
$pengeluaran = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Kelola Pengeluaran</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="bg-light">
<div class="d-flex">
  <?php include 'sidebar.php'; ?>

  <div class="content w-100">
    <?php include __DIR__.'/header.php'; ?>

    <main class="container mt-4">
      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="mb-0">Data Pengeluaran</h5>
          <div>
            <a href="pengeluaran_pdf.php" target="_blank" class="btn btn-danger btn-sm">
              <i class="fa fa-file-pdf"></i> Download PDF
            </a>
            <button class="btn btn-success btn-sm" id="btnTambah" data-bs-toggle="modal" data-bs-target="#modalPengeluaran">
              <i class="fa fa-plus"></i> Tambah
            </button>
          </div>
        </div>

        <div class="card-body">
          <table class="table table-bordered text-center align-middle">
            <thead class="table-light">
              <tr>
                <th>Tanggal</th>
                <th>Judul</th>
                <th>Jumlah</th>
                <th>Keterangan</th>
                <th width="100">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if ($pengeluaran): ?>
              <?php foreach ($pengeluaran as $row): ?>
              <tr>
                <td><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td class="text-danger fw-bold">Rp <?= number_format($row['amount'],0,',','.') ?></td>
                <td><?= htmlspecialchars($row['note'] ?: '-') ?></td>
                <td>
                  <button class="btn btn-warning btn-sm btnEdit"
                    data-id="<?= $row['id'] ?>"
                    data-title="<?= htmlspecialchars($row['title']) ?>"
                    data-amount="<?= $row['amount'] ?>"
                    data-note="<?= htmlspecialchars($row['note']) ?>"
                    data-bs-toggle="modal"
                    data-bs-target="#modalPengeluaran">
                    <i class="fa fa-edit"></i>
                  </button>
                  <button class="btn btn-danger btn-sm btnDelete" data-id="<?= $row['id'] ?>">
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-muted">Belum ada data</td></tr>
            <?php endif; ?>
            </tbody>
          </table>

          <div class="mt-3">
            <strong>Sisa Saldo:</strong>
            Rp <?= number_format($saldo_bersih,0,',','.') ?>
          </div>
           </main>
        </div>
        
      </div>
    

    <?php include __DIR__.'/footer.php'; ?>
  </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="modalPengeluaran">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="formPengeluaran">
        <div class="modal-header bg-danger text-white">
          <h5 id="judulModal">Tambah Pengeluaran</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
          <input type="hidden" name="id" id="edit_id">
          <label>Judul</label>
          <input type="text" name="title" id="title" class="form-control" required>

          <label class="mt-2">Jumlah</label>
          <input type="number" name="amount" id="amount" class="form-control" required>

          <label class="mt-2">Keterangan</label>
          <textarea name="note" id="note" class="form-control" rows="2"></textarea>
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-danger">Simpan</button>
        </div>
      </form>
    </div>
           
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const form = document.getElementById('formPengeluaran');

// === Simpan (Tambah / Edit) ===
form.addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(form);
  const res = await fetch('simpan_pengeluaran.php', { method: 'POST', body: fd });
  const data = await res.json();
  if (data.status === 'success') location.reload();
  else alert(data.message);
});

// === Edit Data ===
document.querySelectorAll('.btnEdit').forEach(btn => {
  btn.addEventListener('click', () => {
    judulModal.textContent = 'Edit Pengeluaran';
    edit_id.value = btn.dataset.id;
    title.value = btn.dataset.title;
    amount.value = btn.dataset.amount;
    note.value = btn.dataset.note;
  });
});

// === Tambah Data ===
btnTambah.addEventListener('click', () => {
  form.reset();
  edit_id.value = '';
  judulModal.textContent = 'Tambah Pengeluaran';
});

// === Hapus Data ===
document.querySelectorAll('.btnDelete').forEach(btn => {
  btn.addEventListener('click', async () => {
    if (!confirm('Hapus data ini?')) return;
    const fd = new FormData();
    fd.append('id', btn.dataset.id);
    const res = await fetch('delete_pengeluaran.php', { method: 'POST', body: fd });
    const text = await res.text();
    if (text === 'success') location.reload();
  });
});
</script>
</body>
</html>

ini kamu cek yg bnr knp muncul eror itu