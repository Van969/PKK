<?php
require __DIR__ . '/../db.php';
session_start();
date_default_timezone_set('Asia/Jakarta');

// ============================
// Ambil bulan & tahun dari URL
// ============================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// ============================
// Nama bulan
// ============================
$namaBulan = [
  "", "Januari", "Februari", "Maret", "April", "Mei", "Juni",
  "Juli", "Agustus", "September", "Oktober", "November", "Desember"
];

// ============================
// Ambil semua siswa
// ============================
$stmt = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC");
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================
// Ambil data pembayaran mingguan dari tabel BARU
// ============================
$stmt2 = $pdo->prepare("
    SELECT pb.siswa_id, pm.minggu_ke, pm.status
    FROM pembayaran_mingguan_baru pm
    JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
    WHERE pb.bulan = ? AND pb.tahun = ?
");
$stmt2->execute([$bulan, $tahun]);

$pembayaran_data = [];
foreach ($stmt2 as $d) {
    $pembayaran_data[$d['siswa_id']][$d['minggu_ke']] = $d['status'];
}

// ============================
// Ambil pengeluaran bulan ini
// ============================
$stmt3 = $pdo->prepare("
    SELECT * FROM pengeluaran 
    WHERE MONTH(created_at) = ? AND YEAR(created_at) = ?
    ORDER BY created_at DESC
");
$stmt3->execute([$bulan, $tahun]);
$pengeluaran = $stmt3->fetchAll(PDO::FETCH_ASSOC);

// ============================
// Bayar per minggu (default 5000)
// ============================
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap Kas Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">
<div class="d-flex konten">
    <?php include 'sidebar.php'; ?>
    <main class="flex-fill p-4 bg-light">
        <?php include 'header.php'; ?>

        <div class="container py-4">
            <h2 class="text-center mb-4">Rekap Pembayaran Kas Kelas</h2>

            <!-- Form pilih bulan & tahun -->
            <form class="mb-3 d-flex justify-content-center gap-2" method="GET" action="index.php">
                <select name="bulan" class="form-select w-auto">
                    <?php foreach ($namaBulan as $i => $b): if($i==0) continue; ?>
                        <option value="<?= $i ?>" <?= $i==$bulan?'selected':'' ?>><?= $b ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="tahun" value="<?= $tahun ?>" class="form-control w-auto" style="max-width:100px">
                <button class="btn btn-primary">Tampilkan</button>
            </form>

            <!-- Info nominal -->
            <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
                <span class="fw-bold">Bayar per minggu:</span>
                <span class="badge bg-info text-dark" style="font-size:15px;">
                    Rp <?= number_format($bayar_per_minggu,0,',','.') ?>
                </span>
            </div>

            <!-- Rekap Pembayaran -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white text-center">
                    <strong><?= $namaBulan[$bulan] ?> <?= $tahun ?></strong>
                </div>
                <div class="card-body p-2">
                    <table class="table table-sm table-hover align-middle text-center" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <th>M1</th>
                                <th>M2</th>
                                <th>M3</th>
                                <th>M4</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no=1;
                            foreach($siswa as $s): 
                                $s_id = $s['id'];
                                $belum = 0;
                            ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td class="text-start"><?= htmlspecialchars($s['nama']) ?></td>
                                <?php for($m=1; $m<=4; $m++):
                                    $status = $pembayaran_data[$s_id][$m] ?? 0;
                                    if(!$status) $belum++;
                                ?>
                                    <td><?= $status ? '✅' : '❌' ?></td>
                                <?php endfor; ?>
                                <td>
                                    <?= $belum==0 
                                        ? '<span class="text-success fw-bold">Lunas</span>' 
                                        : '<span class="text-danger">- Rp '.number_format($belum*$bayar_per_minggu,0,',','.').'</span>'
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ✅ Tabel Pengeluaran Bulan Ini -->
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white text-center">
                    <strong>Pengeluaran Bulan <?= $namaBulan[$bulan] . " " . $tahun ?></strong>
                </div>
                <div class="card-body p-2">
                    <table class="table table-sm table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Judul</th>
                                <th>Jumlah</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if($pengeluaran): $no=1; ?>
                            <?php foreach($pengeluaran as $p): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d-m-Y', strtotime($p['created_at'])) ?></td>
                                <td><?= htmlspecialchars($p['title']) ?></td>
                                <td class="text-danger fw-bold">Rp <?= number_format($p['amount'],0,',','.') ?></td>
                                <td><?= htmlspecialchars($p['note'] ?: '-') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="5" class="text-muted">Tidak ada pengeluaran bulan ini</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>
</body>
</html>
