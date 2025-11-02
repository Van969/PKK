<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// ============================
// Tentukan bulan & tahun
// ============================
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : null;
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : null;

if (!$tahun) {
    $stmt = $pdo->query("SELECT tahun FROM pembayaran_baru ORDER BY tahun DESC LIMIT 1");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    $tahun = $last['tahun'] ?? (int)date('Y');
}

$namaBulan = ["","Januari","Februari","Maret","April","Mei","Juni",
              "Juli","Agustus","September","Oktober","November","Desember"];

// ============================
// Ambil semua siswa
// ============================
$stmt_siswa = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC");
$siswa_all = $stmt_siswa->fetchAll(PDO::FETCH_ASSOC);

// ============================
// Pastikan data pembayaran_baru & mingguan_baru ada
// ============================
foreach ($siswa_all as $s) {
    $sid = $s['id'];
    for ($bln = 1; $bln <= 12; $bln++) {
        $stmt = $pdo->prepare("SELECT id FROM pembayaran_baru WHERE siswa_id=? AND bulan=? AND tahun=?");
        $stmt->execute([$sid, $bln, $tahun]);
        $pembayaran_id = $stmt->fetchColumn();

        if (!$pembayaran_id) {
            $ins = $pdo->prepare("INSERT INTO pembayaran_baru (siswa_id, bulan, tahun, created_at) VALUES (?,?,?,NOW())");
            $ins->execute([$sid, $bln, $tahun]);
            $pembayaran_id = $pdo->lastInsertId();
        }

        for ($m = 1; $m <= 4; $m++) {
            $cek = $pdo->prepare("SELECT id FROM pembayaran_mingguan_baru WHERE pembayaran_id=? AND minggu_ke=?");
            $cek->execute([$pembayaran_id, $m]);
            if (!$cek->fetchColumn()) {
                $ins_m = $pdo->prepare("INSERT INTO pembayaran_mingguan_baru (pembayaran_id, minggu_ke, status) VALUES (?,?,0)");
                $ins_m->execute([$pembayaran_id, $m]);
            }
        }
    }
}

// ============================
// Jika GET bulan kosong
// ============================
if (!$bulan) {
    $stmt = $pdo->query("SELECT bulan FROM pembayaran_baru ORDER BY tahun DESC, bulan DESC LIMIT 1");
    $last = $stmt->fetch(PDO::FETCH_ASSOC);
    $bulan = $last['bulan'] ?? (int)date('n');
}

// ============================
// Ambil data dari pembayaran_mingguan_baru
// ============================
$stmt2 = $pdo->prepare("
    SELECT pb.siswa_id, pm.minggu_ke, pm.status
    FROM pembayaran_mingguan_baru pm
    JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
    WHERE pb.bulan = ? AND pb.tahun = ?
");
$stmt2->execute([$bulan, $tahun]);

$pembayaran_data = [];
foreach ($stmt2 as $row) {
    $pembayaran_data[$row['siswa_id']][$row['minggu_ke']] = $row['status'];
}

$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Rekap Kas Kelas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<style>.bayar-checkbox{width:18px;height:18px;cursor:pointer}</style>
</head>
<body class="bg-light">
<div class="d-flex konten">
<?php include 'sidebar.php'; ?>
<main class="flex-fill p-4 bg-light">
<?php include 'header.php'; ?>

<div class="container py-4">
    <h2 class="text-center mb-4">Rekap Pembayaran Kas Kelas</h2>

    <form id="formBulan" method="GET" action="aksi_fix.php" class="mb-3 d-flex gap-2 align-items-center justify-content-center">
        <select name="bulan" id="bulanSelect" class="form-select w-auto">
            <?php foreach ($namaBulan as $i => $b): if ($i==0) continue; ?>
                <option value="<?= $i ?>" <?= $i==$bulan?'selected':'' ?>><?= $b ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="tahun" value="<?= $tahun ?>" class="form-control w-auto" style="max-width:100px">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>

    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white text-center">
            <strong><?= $namaBulan[$bulan] ?> <?= $tahun ?></strong>
        </div>
        <div class="card-body p-2">
            <table class="table table-sm table-hover text-center align-middle" style="font-size:13px;">
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
                foreach($siswa_all as $s):
                    $sid = $s['id'];
                    $belum=0;
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-start"><?= htmlspecialchars($s['nama']) ?></td>
                        <?php for($m=1;$m<=4;$m++):
                            $status = $pembayaran_data[$sid][$m] ?? 0;
                            if(!$status) $belum++;
                        ?>
                            <td>
                                <input type="checkbox" class="bayar-checkbox"
                                    data-siswa="<?= $sid ?>"
                                    data-bulan="<?= $bulan ?>"
                                    data-tahun="<?= $tahun ?>"
                                    data-minggu="<?= $m ?>"
                                    <?= $status?'checked':'' ?>>
                            </td>
                        <?php endfor; ?>
                        <td>
                            <?= $belum==0
                                ? '<span class="text-success fw-bold">Lunas</span>'
                                : '<span class="text-danger">Rp '.number_format($belum*$bayar_per_minggu,0,',','.').'</span>' 
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// === Saat checkbox (bayar) diklik ===
document.querySelectorAll('.bayar-checkbox').forEach(cb => {
  cb.addEventListener('change', async e => {
    const id = cb.dataset.siswa;
    const minggu = cb.dataset.minggu;
    const bulan = cb.dataset.bulan;
    const tahun = cb.dataset.tahun;
    const status = cb.checked ? 1 : 0;

    try {
      // Kirim ke server untuk update status pembayaran
      const resp = await fetch('update_pembayaran_baru.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&minggu=${minggu}&bulan=${bulan}&tahun=${tahun}&status=${status}`
      });
      const text = await resp.text();
      console.log('Server:', text);

      if (text.includes('OK')) {
  // ✅ Update tampilan total per siswa
  updateRowTotal(cb.closest('tr'));

  // ✅ Auto reload halaman penuh biar sidebar ikut update
  location.reload();

  // (opsional, tetap kirim event global kalau mau dipakai tempat lain)
  window.dispatchEvent(new Event('sidebarUpdate'));
} else {
  alert('❌ ' + text);
  cb.checked = !status;
}

    } catch (err) {
      alert('⚠️ Error koneksi: ' + err.message);
      cb.checked = !status;
    }
  });
});

// === Hitung ulang total di baris siswa ===
function updateRowTotal(row) {
  const bayarPerMinggu = 5000; // bisa juga ambil dari session hidden input
  const belumBayar = [...row.querySelectorAll('.bayar-checkbox')]
    .filter(x => !x.checked).length;

  const tdTotal = row.querySelector('td:last-child');
  tdTotal.innerHTML = belumBayar === 0
    ? '<span class="text-success fw-bold">Lunas</span>'
    : '<span class="text-danger">Rp ' + (belumBayar * bayarPerMinggu).toLocaleString('id-ID') + '</span>';
}
</script>




</main>
</div>
</body>
</html>
