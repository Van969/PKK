<?php
require __DIR__ . '/../db.php';

// Proses tambah data dari modal Tambah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['edit_id'])) {
    $name = $_POST['name'];
    $jumlah_membayar = (int)$_POST['jumlah_membayar'];
    $belum_membayar = (int)$_POST['belum_membayar'];
    $total_tagihan = (int)$_POST['total_tagihan'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO pembayaran (name, jumlah_membayar, belum_membayar, total_tagihan, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $jumlah_membayar, $belum_membayar, $total_tagihan, $status]);

    header("Location: aksi.php");
    exit;
}

// Proses edit data dari modal Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['edit_id'])) {
    $id = (int)$_GET['edit_id'];
    $name = $_POST['name'];
    $jumlah_membayar = (int)$_POST['jumlah_membayar'];
    $belum_membayar = (int)$_POST['belum_membayar'];
    $total_tagihan = (int)$_POST['total_tagihan'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE pembayaran SET name=?, jumlah_membayar=?, belum_membayar=?, total_tagihan=?, status=? WHERE id=?");
    $stmt->execute([$name, $jumlah_membayar, $belum_membayar, $total_tagihan, $status, $id]);

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
     .konten { transition: filter 0.3s ease; }
     /* Efek blur dan pointer-events dihapus agar modal bisa diklik dan tidak blur */
     .modal { pointer-events: auto; z-index: 1050; }
  </style>
</head>
<body>
<div class="d-flex konten">
  <!-- Sidebar -->
  <aside class="sidebar p-3 bg-light" style="width:220px;">
    <h5 class="mb-4">Menu Keuangan</h5>
    <ul class="nav flex-column">
      <li class="nav-item mb-2"><a class="nav-link" href="index.php"><i class="fa fa-home me-2"></i> Halaman Utama</a></li>
      <li class="nav-item mb-2"><a class="nav-link" href="#"><i class="fa fa-wallet me-2"></i> Pemasukan & Pengeluaran</a></li>
      <li class="nav-item mb-2"><a class="nav-link active" href="aksi.php"><i class="fa fa-plus me-2"></i> Tambah & Edit Pembayaran</a></li>
    </ul>
  </aside>

  <!-- Main content -->
  <main class="flex-fill p-4 bg-light">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Kelola Pembayaran Kas</h3>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTambah"><i class="fa fa-plus me-2"></i> Tambah Siswa</button>
      </div>

      <!-- Tabel Pembayaran -->
      <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">📊 Data Pembayaran Kas Kelas</div>
        <div class="card-body table-responsive">
          <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
              <tr>
                <th>No</th>
                <th>Nama</th>
                <th>Jumlah Membayar</th>
                <th>Belum Membayar</th>
                <th>Total Tagihan</th>
                <th>Status</th>
                <th width="150">Aksi</th>
              </tr>
            </thead>
            <tbody>
            <?php if(!empty($pembayaran)): ?>
              <?php $no=1; foreach($pembayaran as $row): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= htmlspecialchars($row['name']) ?></td>
                  <td class="text-success">Rp <?= number_format($row['jumlah_membayar'],0,',','.') ?></td>
                  <td class="text-danger">Rp <?= number_format($row['belum_membayar'],0,',','.') ?></td>
                  <td>Rp <?= number_format($row['total_tagihan'],0,',','.') ?></td>
                  <td>
                    <?php $badge = match($row['status']){'Lunas'=>'success','Sebagian'=>'secondary','Belum Bayar'=>'danger',default=>'secondary'}; ?>
                    <span class="badge bg-<?= $badge ?>"><?= $row['status'] ?></span>
                  </td>
                  <td>
                    <!-- Tombol Edit Modal -->
                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $row['id'] ?>"><i class="fa fa-edit"></i></button>
                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Yakin ingin menghapus data ini?')"><i class="fa fa-trash"></i></a>
                  </td>
                </tr>

                <!-- Modal Edit -->
                <div class="modal fade" id="modalEdit<?= $row['id'] ?>" tabindex="-1" aria-labelledby="modalEditLabel<?= $row['id'] ?>" aria-hidden="true">
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-lg">
                      <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title" id="modalEditLabel<?= $row['id'] ?>"><i class="fa fa-edit"></i> Edit Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <form method="POST" action="aksi.php?edit_id=<?= $row['id'] ?>" class="form-edit">
                        <div class="modal-body">
                          <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" value="<?= htmlspecialchars($row['name']) ?>" required></div>
                          <div class="mb-3"><label class="form-label">Jumlah Membayar</label><input type="number" name="jumlah_membayar" class="form-control" value="<?= $row['jumlah_membayar'] ?>" required></div>
                          <div class="mb-3"><label class="form-label">Belum Membayar</label><input type="number" name="belum_membayar" class="form-control" value="<?= $row['belum_membayar'] ?>" required></div>
                          <div class="mb-3"><label class="form-label">Total Tagihan</label><input type="number" name="total_tagihan" class="form-control" value="<?= $row['total_tagihan'] ?>" required></div>
                          <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                              <option value="Lunas" <?= $row['status']=='Lunas'?'selected':'' ?>>Lunas</option>
                              <option value="Sebagian" <?= $row['status']=='Sebagian'?'selected':'' ?>>Sebagian</option>
                              <option value="Belum Bayar" <?= $row['status']=='Belum Bayar'?'selected':'' ?>>Belum Bayar</option>
                            </select>
                          </div>
                        </div>
                        <div class="modal-footer">
                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                          <button type="submit" class="btn btn-warning btn-submit">
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                            <span class="btn-text">Simpan</span>
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="7" class="text-center text-muted">Belum ada data pembayaran.</td></tr>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal Tambah Siswa -->
<div class="modal fade" id="modalTambah" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow-lg">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalTambahLabel"><i class="fa fa-plus"></i> Tambah Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <div class="mb-3"><label class="form-label">Nama</label><input type="text" name="name" class="form-control" required></div>
          <div class="mb-3"><label class="form-label">Jumlah Membayar</label><input type="number" name="jumlah_membayar" class="form-control" value="0" required></div>
          <div class="mb-3"><label class="form-label">Belum Membayar</label><input type="number" name="belum_membayar" class="form-control" value="0" required></div>
          <div class="mb-3"><label class="form-label">Total Tagihan</label><input type="number" name="total_tagihan" class="form-control" value="0" required></div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="Lunas">Lunas</option>
              <option value="Sebagian">Sebagian</option>
              <option value="Belum Bayar">Belum Bayar</option>
            </select>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.form-edit').forEach(form => {
  form.addEventListener('submit', function() {
    const btn = this.querySelector('.btn-submit');
    const spinner = btn.querySelector('.spinner-border');
    const text = btn.querySelector('.btn-text');
    spinner.classList.remove('d-none');
    text.textContent = ' Menyimpan...';
    btn.disabled = true;
  });
});
</script>
</body>
</html>
