<?php
session_start();
require __DIR__ . '/../db.php';

// ===== Hitung Total Pemasukan =====
$total_pemasukan_db = $pdo->query("SELECT COALESCE(SUM(total_pemasukan),0) FROM pemasukan")->fetchColumn();

$total_pembayaran = $pdo->query("
    SELECT COALESCE(COUNT(*),0) * 5000
    FROM pembayaran_mingguan
    WHERE status = 1
")->fetchColumn();

$total_pemasukan = $total_pemasukan_db + $total_pembayaran;

// ===== Hitung Total Pengeluaran =====
$total_pengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// ===== Saldo Bersih =====
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

// ===== Ambil Semua Pengeluaran =====
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

<body class="bg-light">

<div class="d-flex">

    <?php include 'sidebar.php'; ?>

    <div class="content w-100">

        <?php include __DIR__.'/header.php'; ?>

        <main class="container mt-4">

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between">
                    <h5>Data Pengeluaran</h5>
                    <button class="btn btn-success btn-sm" id="btnTambah" data-bs-toggle="modal" data-bs-target="#modalPengeluaran">
                        <i class="fa fa-plus"></i> Tambah
                    </button>
                </div>

                <div class="card-body">

                    <table class="table table-bordered text-center align-middle">
                        <thead class="table-light">
                            <tr>
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
                            <tr><td colspan="4" class="text-muted">Belum ada data</td></tr>
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

// ✅ Submit
form.addEventListener('submit', function(e){
    e.preventDefault();
    const fd = new FormData(form);

    fetch('simpan_pengeluaran.php', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        console.log(res);
        if(res.status === 'success') location.reload();
        else alert(res.message);
    });
});

// ✅ Edit
document.querySelectorAll('.btnEdit').forEach(btn =>{
    btn.addEventListener('click', ()=>{
        judulModal.textContent = 'Edit Pengeluaran';
        edit_id.value = btn.dataset.id;
        title.value = btn.dataset.title;
        amount.value = btn.dataset.amount;
        note.value = btn.dataset.note;
    });
});

// ✅ Tambah
btnTambah.addEventListener('click', ()=>{
    form.reset();
    edit_id.value = "";
    judulModal.textContent = "Tambah Pengeluaran";
});

// ✅ Delete
document.querySelectorAll('.btnDelete').forEach(btn =>{
    btn.addEventListener('click', ()=>{
        if(!confirm("Hapus data ini?")) return;
        const fd = new FormData();
        fd.append("id",btn.dataset.id);

        fetch("delete_pengeluaran.php",{
            method:"POST",
            body:fd
        }).then(r => r.text())
          .then(res =>{
              if(res==="success") location.reload();
          });
    });
});
const amountInput = document.getElementById('amount');

// Step 1000 tiap klik tombol naik/turun
amountInput.setAttribute("step", "1000");
amountInput.setAttribute("min", "0");

// Bulatkan input otomatis ke ribuan
amountInput.addEventListener("input", function () {
    let val = parseInt(this.value || 0);
    if (val < 0) val = 0;
    val = Math.round(val / 1000) * 1000;
    this.value = val;
});

</script>

</body>
</html>
