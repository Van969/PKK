<?php
require __DIR__ . '/../db.php';

$stmt = $pdo->query("SELECT * FROM siswa ORDER BY nama ASC");
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

$bayar_per_minggu = 5000;

// Ambil semua pembayaran sekaligus
$stmt2 = $pdo->query("
  SELECT p.siswa_id, pm.minggu_ke, pm.bulan, pm.tahun, pm.status
  FROM pembayaran_mingguan pm
  JOIN pembayaran p ON p.id = pm.pembayaran_id
");

$pembayaran_data = [];
foreach ($stmt2 as $d) {
  $key = "{$d['tahun']}-{$d['bulan']}";
  $pembayaran_data[$key][$d['siswa_id']][$d['minggu_ke']] = $d['status'];
}


// === Bulan & Tahun Dinamis ===
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Otomatis pindah tahun jika bulan melewati batas
if ($bulan < 1) {
  $bulan = 12;
  $tahun--;
}
if ($bulan > 12) {
  $bulan = 1;
  $tahun++;
}

$namaBulan = ["","Januari","Februari","Maret","April","Mei","Juni",
              "Juli","Agustus","September","Oktober","November","Desember"];

?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Aksi Siswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">

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

      <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h3>Data Siswa & Aksi</h3>
          <button class="btn btn-success" id="btnTambah">
            <i class="fa fa-plus me-2"></i> Tambah Siswa
          </button>
        </div>

        <div class="container py-4">
  <div class="row row-cols-1 row-cols-md-2 g-4">

    <?php foreach ($namaBulan as $i => $b): if ($i == 0) continue; ?>
      <div class="col">
        <div class="card h-100 shadow-sm">
          <div class="card-header bg-primary text-white d-flex justify-content-between">
            <span><?= $b ?></span>
          </div>
          <div class="card-body p-2">

            <table class="table table-sm table-hover align-middle" style="font-size: 13px;">
              <thead class="table-light text-center">
                <tr>
                  <th>No</th>
                  <th>Nama</th>
                  <th>M1</th>
                  <th>M2</th>
                  <th>M3</th>
                  <th>M4</th>
                  <th>Total</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>

                <?php $no = 1; foreach ($siswa as $s): ?>
                  <?php
                    $belum = 0;
                    for ($m=1; $m<=4; $m++) {
                      $status = $pembayaran_data["$tahun-$i"][$s['id']][$m] ?? 0;
                      if (!$status) $belum++;
                    }
                  ?>
                  <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($s['nama']) ?></td>

                    <?php for ($m=1; $m<=4; $m++):
                      $status = $pembayaran_data["$tahun-$i"][$s['id']][$m] ?? 0;
                    ?>
                      <td class="text-center">
                        <input type="checkbox"
                          class="bayar-checkbox"
                          data-id="<?= $s['id'] ?>"
                          data-bulan="<?= $i ?>"
                          data-tahun="<?= $tahun ?>"
                          data-minggu="<?= $m ?>"
                          <?= $status ? 'checked' : '' ?>>
                      </td>
                    <?php endfor; ?>

                    <td class="text-center">
                      <?php if ($belum == 0): ?>
                        <span class="text-success fw-bold">Lunas</span>
                      <?php else: ?>
                        <span class="text-danger">Rp <?= number_format($belum * $bayar_per_minggu,0,',','.') ?></span>
                      <?php endif; ?>
                    </td>

                    <td class="text-center">
                      <button class="btn btn-warning btn-sm btnEdit" data-id="<?= $s['id'] ?>">Edit</button>
                      <button class="btn btn-danger btn-sm btnDelete" data-id="<?= $s['id'] ?>">Hapus</button>
                    </td>
                  </tr>
                <?php endforeach; ?>

              </tbody>
            </table>

          </div>
        </div>
      </div>
    <?php endforeach; ?>

  </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTambah">
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Siswa</label>
            <input type="text" name="nama" class="form-control" required placeholder="Masukkan nama siswa">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEdit">
        <input type="hidden" id="edit_id" name="id">
        <div class="modal-body">
          <div class="mb-3">
            <label>Nama Siswa</label>
            <input type="text" id="edit_nama" name="nama" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="script.js"></script>
</body>
</html>
