<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

// Hilangkan cache
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

// Ambil bulan & tahun dari URL
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('n');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

$namaBulan = ["","Januari","Februari","Maret","April","Mei","Juni",
              "Juli","Agustus","September","Oktober","November","Desember"];

// Ambil siswa
$stmt = $pdo->query("SELECT * FROM siswa ORDER BY nama ASC");
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil data pembayaran sesuai bulan & tahun
$stmt2 = $pdo->prepare("
  SELECT siswa_id, minggu_ke, status 
  FROM pembayaran_baru 
  WHERE bulan = ? AND tahun = ?
");
$stmt2->execute([$bulan, $tahun]);
$pembayaran_data = [];
foreach ($stmt2 as $d) {
    $pembayaran_data[$d['siswa_id']][$d['minggu_ke']] = $d['status'];
}

// Ambil bayar per minggu dari session jika ada
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

    <!-- Form Pilih Bulan & Tahun -->
    <form id="formBulan" method="GET" action="aksi_fix.php" 
        class="mb-3 d-flex gap-2 align-items-center justify-content-center">
        <select name="bulan" id="bulanSelect" class="form-select w-auto">
            <?php foreach ($namaBulan as $i => $b): if ($i == 0) continue; ?>
                <option value="<?= $i ?>" <?= $i == $bulan ? 'selected' : '' ?>><?= $b ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" name="tahun" value="<?= $tahun ?>" 
            class="form-control w-auto" style="max-width:100px">
        <button type="submit" class="btn btn-primary">Tampilkan</button>
    </form>

    <!-- Input Bayar Per Minggu -->
    <div class="mb-3 d-flex justify-content-center align-items-center gap-2">
        <label for="bayarPerMinggu" class="fw-bold">Bayar per minggu:</label>
        <input type="number" id="bayarPerMinggu" value="<?= $bayar_per_minggu ?>" 
               class="form-control w-auto" min="1000" step="500">
        <button id="simpanBayar" class="btn btn-success">Simpan</button>
    </div>

    <!-- Header Bulan -->
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
                <?php $no = 1; foreach ($siswa as $s): 
                    $belum = 0;
                ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td class="text-start"><?= htmlspecialchars($s['nama']) ?></td>
                        <?php for ($m = 1; $m <= 4; $m++): 
                            $status = $pembayaran_data[$s['id']][$m] ?? 0;
                            if(!$status) $belum++;
                        ?>
                            <td>
                                <input type="checkbox" class="bayar-checkbox"
                                    data-siswa="<?= $s['id'] ?>"
                                    data-bulan="<?= $bulan ?>"
                                    data-tahun="<?= $tahun ?>"
                                    data-minggu="<?= $m ?>"
                                    <?= $status ? 'checked' : '' ?> >
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
// Ambil bayar per minggu dari input
let bayarPerMinggu = parseInt(document.getElementById('bayarPerMinggu').value) || <?= $bayar_per_minggu ?>;

// ============================
// UPDATE TOTAL TABEL
// ============================
function updateTotals() {
    const bayar = parseInt(document.getElementById('bayarPerMinggu').value) || bayarPerMinggu;
    document.querySelectorAll('tbody tr').forEach(tr => {
        let belum = 0;
        tr.querySelectorAll('input.bayar-checkbox').forEach(cb => {
            if (!cb.checked) belum++;
        });
        const tdTotal = tr.querySelector('td:last-child');
        tdTotal.innerHTML = belum === 0
            ? '<span class="text-success fw-bold">Lunas</span>'
            : '<span class="text-danger">Rp ' + (belum * bayar).toLocaleString() + '</span>';
    });
}

// ============================
// CHECKBOX LISTENER
// ============================
function attachCheckboxListeners() {
    document.querySelectorAll('.bayar-checkbox').forEach(cb => {
        cb.addEventListener('change', async function() {
            const siswa_id = this.dataset.siswa;
            const bulan = this.dataset.bulan;
            const tahun = this.dataset.tahun;
            const minggu = this.dataset.minggu;
            const status = this.checked ? 1 : 0;

            try {
                const resp = await fetch('update_pembayaran_baru.php', {
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:`id=${siswa_id}&bulan=${bulan}&tahun=${tahun}&minggu=${minggu}&status=${status}`
                });
                const text = await resp.text();
                if (text.includes('OK')) {
                    updateTotals(); // update total baris
                    window.dispatchEvent(new Event('sidebarUpdate')); // update sidebar
                } else {
                    alert('❌ ' + text);
                }
            } catch(err) {
                alert('Koneksi error: ' + err.message);
            }
        });
    });
}

// ============================
// SIMPAN BAYAR PER MINGGU
// ============================
document.getElementById('simpanBayar').addEventListener('click', async function(e){
    e.preventDefault();
    const val = parseInt(document.getElementById('bayarPerMinggu').value);
    if(isNaN(val) || val<=0){ alert('Masukkan angka valid!'); return; }

    try{
        await fetch('update_session_bayar.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:`bayar_per_minggu=${val}`
        });
        bayarPerMinggu = val;
        alert('💰 Bayar per minggu diubah menjadi Rp ' + val.toLocaleString());
        updateTotals();
        window.dispatchEvent(new Event('sidebarUpdate')); // update sidebar
    }catch(err){
        alert('Gagal update: '+err.message);
    }
});

// ============================
// AUTO SUBMIT PILIH BULAN
// ============================
document.getElementById('bulanSelect').addEventListener('change', ()=>document.getElementById('formBulan').submit());

// ============================
// INIT
// ============================
updateTotals();
attachCheckboxListeners();
</script>

</body>
</html>
