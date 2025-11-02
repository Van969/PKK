<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

// ✅ Pastikan Admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../LoginPage/Login.php');
    exit;
}

// ==============================
// Hitung total siswa & user
// ==============================
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$totalUser  = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// ==============================
// Ambil tarif bayar per minggu
// ==============================
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;

// ==============================
// Hitung total pengeluaran kelas
// ==============================
$totalPengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// ==============================
// Beban pengeluaran per siswa
// ==============================
$beban_pengeluaran_per_siswa = ($totalSiswa > 0) ? ($totalPengeluaran / $totalSiswa) : 0;

// ==============================
// Ambil semua siswa
// ==============================
$stmt = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC");
$siswaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dataSiswa = [];
foreach ($siswaData as $s) {
    $id = $s['id'];

    // ✅ Total minggu dibayar (JOIN ke pembayaran_baru)
    $stmtBayar = $pdo->prepare("
        SELECT COUNT(*) 
        FROM pembayaran_mingguan_baru pm
        JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
        WHERE pb.siswa_id = ? AND pm.status = 1
    ");
    $stmtBayar->execute([$id]);
    $totalMinggu = $stmtBayar->fetchColumn();

    $totalBayar = $totalMinggu * $bayar_per_minggu;
    $sisaSaldo = $totalBayar - $beban_pengeluaran_per_siswa;

    $dataSiswa[] = [
        'id' => $id,
        'nama' => $s['nama'],
        'bayar' => $totalBayar,
        'pengeluaran' => $beban_pengeluaran_per_siswa,
        'sisa' => $sisaSaldo
    ];
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Data Kas Siswa</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="d-flex konten">
<?php include __DIR__.'/sidebar.php'; ?>

<main class="flex-fill p-4 bg-light">
<?php include __DIR__.'/header.php'; ?>

<div class="container py-4">
    <h3 class="text-center text-primary fw-bold mb-4">📊 Rekap Kas Per Siswa</h3>

    <!-- Statistik -->
    <div class="row justify-content-center g-4 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 border-primary border-2">
                <h6 class="text-muted mb-1">Total User</h6>
                <h3 class="fw-bold text-primary"><?= $totalUser ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center p-3 border-success border-2">
                <h6 class="text-muted mb-1">Total Siswa</h6>
                <h3 class="fw-bold text-success"><?= $totalSiswa ?></h3>
            </div>
        </div>
    </div>

    <!-- Tombol Tambah -->
    <div class="text-end mb-3">
        <button class="btn btn-primary" id="btnTambah">
            <i class="fa fa-plus"></i> Tambah Siswa
        </button>
    </div>

    <!-- 🧾 Rekap per siswa -->
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Total Pembayaran</th>
                    <th>Kontribusi Pengeluaran</th>
                    <th>Sisa Saldo</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody id="tabel-siswa">
                <?php $no=1; foreach ($dataSiswa as $row): ?>
                <tr data-id="<?= $row['id'] ?>">
                    <td><?= $no++ ?></td>
                    <td class="text-start"><?= htmlspecialchars($row['nama']) ?></td>
                    <td>Rp <?= number_format($row['bayar'],0,',','.') ?></td>
                    <td>Rp <?= number_format($row['pengeluaran'],0,',','.') ?></td>
                    <td class="<?= $row['sisa'] < 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
                        Rp <?= number_format($row['sisa'],0,',','.') ?>
                    </td>
                    <td>
                        <button class="btn btn-warning btn-sm btn-edit"
                            data-id="<?= $row['id'] ?>"
                            data-nama="<?= htmlspecialchars($row['nama']) ?>">
                            <i class="fa fa-pen"></i>
                        </button>

                        <button class="btn btn-danger btn-sm btn-hapus"
                            data-id="<?= $row['id'] ?>">
                            <i class="fa fa-trash"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <!-- ✅ Rekap Bulanan Per Siswa -->
    <hr>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center fw-bold">
    <span>📅 Rekap Pembayaran Per Bulan</span>
    <a href="rekap_pdf.php" target="_blank" class="btn btn-danger btn-sm">
        <i class="fa fa-file-pdf"></i> Download PDF
    </a>
</div>

        <div class="card-body table-responsive">
        <?php
        $bulanArr = [
            1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",5=>"Mei",6=>"Jun",
            7=>"Jul",8=>"Agu",9=>"Sep",10=>"Okt",11=>"Nov",12=>"Des"
        ];
        ?>
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Siswa</th>
                    <?php foreach($bulanArr as $b => $nb): ?>
                        <th><?= $nb ?></th>
                    <?php endforeach; ?>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($dataSiswa as $row): ?>
                <?php
                    $id = $row['id'];
                    $totalTahun = 0;
                ?>
                <tr>
                    <td class="fw-bold text-start"><?= htmlspecialchars($row['nama']) ?></td>
                    <?php foreach($bulanArr as $bulan => $namaB): ?>
                        <?php
                        // ✅ Ambil data pembayaran per bulan lewat JOIN ke pembayaran_baru
                        $q = $pdo->prepare("
                            SELECT COUNT(*) 
                            FROM pembayaran_mingguan_baru pm
                            JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
                            WHERE pb.siswa_id = ? AND pb.bulan = ? AND pm.status = 1
                        ");
                        $q->execute([$id, $bulan]);
                        $minggu = $q->fetchColumn();
                        $jumlah = $minggu * $bayar_per_minggu;
                        $totalTahun += $jumlah;
                        ?>
                        <td>Rp <?= number_format($jumlah,0,',','.') ?></td>
                    <?php endforeach; ?>
                    <td class="bg-success text-white fw-bold">
                        Rp <?= number_format($totalTahun,0,',','.') ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

</main>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah" tabindex="-1">
  <div class="modal-dialog">
    <form id="formTambah" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Tambah Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label>Nama Siswa</label>
        <input type="text" class="form-control" name="nama" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="modalEdit" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEdit" class="modal-content">
      <input type="hidden" name="id" id="edit_id">
      <div class="modal-header bg-warning">
        <h5 class="modal-title">Edit Nama Siswa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label>Nama Siswa</label>
        <input type="text" class="form-control" name="nama" id="edit_nama" required>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-success">Simpan</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>

</body>
</html>
