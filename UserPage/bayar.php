<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');



// Hitung total siswa
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();

// Hitung total user
$totalUser = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

// Ambil tarif bayar per minggu
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;

// Hitung total pengeluaran kelas
$totalPengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// Beban pengeluaran tiap siswa
$beban_pengeluaran_per_siswa = ($totalSiswa > 0) ? ($totalPengeluaran / $totalSiswa) : 0;

// Ambil semua siswa + hitung total bayar mereka
$stmt = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC");
$siswaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

$dataSiswa = [];
foreach ($siswaData as $s) {
    $id = $s['id'];

    $stmtBayar = $pdo->prepare("SELECT COUNT(*) FROM pembayaran_baru WHERE siswa_id = ? AND status = 1");
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

    

   

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle text-center">
            <thead class="table-dark">
                <tr>
                    <th>No</th>
                    <th>Nama</th>
                    <th>Total Pembayaran</th>
                    <th>Kontribusi Pengeluaran</th>
                    <th>Sisa Saldo</th>
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
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>

</div>

</main>
</div>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="script.js"></script>

</body>
</html>
