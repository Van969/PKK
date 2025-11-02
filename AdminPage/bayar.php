<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

// Ambil nilai bayar per minggu dari session (default: 5000)
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Atur Nominal Kas Per Minggu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
</head>

<body class="bg-light">

<div class="d-flex konten">

    <!-- ✅ Sidebar -->
    <?php include 'sidebar.php'; ?>

    <main class="flex-fill p-4 bg-light">

        <!-- ✅ Header -->
        <?php include 'header.php'; ?>

        <div class="container py-4">
            <h3 class="text-center mb-4 text-primary fw-bold">Atur Nominal Kas Per Minggu</h3>

            <div class="card shadow-sm p-4 mx-auto" style="max-width:400px;">
                <label class="fw-bold mb-2">Nominal Kas per Minggu (Rp):</label>
                <input type="number" id="bayarInput" class="form-control mb-3"
                       value="<?= $bayar_per_minggu ?>" min="1000" step="500">

                <button id="btnSimpan" class="btn btn-success w-100">✅ Simpan</button>
            </div>

            <p class="text-center mt-3">
                <a href="aksi_fix.php" class="btn btn-secondary">Kembali</a>
            </p>
        </div>

    </main>

</div>


<script>
document.getElementById('btnSimpan').addEventListener('click', async () => {
    const val = parseInt(document.getElementById('bayarInput').value);
    if(isNaN(val) || val <= 0){
        alert("Masukkan angka valid!");
        return;
    }

    try{
        const resp = await fetch("update_session_bayar.php", {
            method: "POST",
            headers: {"Content-Type": "application/x-www-form-urlencoded"},
            body: "bayar_per_minggu=" + val
        });

        alert("✅ Nominal berhasil disimpan!");
        location.reload();
    }catch(err){
        alert("❌ Error: " + err.message);
    }
});
</script>

</body>
</html>
