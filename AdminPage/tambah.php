<?php
require __DIR__ . '/../db.php';

// Proses simpan data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $jumlah_membayar = (int)$_POST['jumlah_membayar'];
    $belum_membayar = (int)$_POST['belum_membayar'];
    $total_tagihan = (int)$_POST['total_tagihan'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO pembayaran (name, jumlah_membayar, belum_membayar, total_tagihan, status) VALUES (?,?,?,?,?)");
    $stmt->execute([$name, $jumlah_membayar, $belum_membayar, $total_tagihan, $status]);

    header("Location: index.php");
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Pengguna</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="card shadow-sm">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0"><i class="fa fa-plus"></i> Tambah Pengguna</h5>
    </div>
    <div class="card-body">
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nama</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Jumlah Membayar</label>
          <input type="number" name="jumlah_membayar" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Belum Membayar</label>
          <input type="number" name="belum_membayar" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Total Tagihan</label>
          <input type="number" name="total_tagihan" class="form-control" value="0" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="Lunas">Lunas</option>
            <option value="Sebagian">Sebagian</option>
            <option value="Belum Bayar">Belum Bayar</option>
          </select>
        </div>
        <div class="d-flex justify-content-between">
          <a href="index.php" class="btn btn-secondary">Kembali</a>
          <button type="submit" class="btn btn-success" name="tambah">Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>
</body>
</html>
