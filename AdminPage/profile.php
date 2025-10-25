<?php
session_start();
require __DIR__ . '/../db.php';

if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit;
}

$username = $_SESSION['username'];

$stmt = $pdo->prepare("SELECT username, nickname, role FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user) {
    echo "User tidak ditemukan.";
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil Siswa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
   <link rel="stylesheet" href="styles.css">
</head>
<body class="bg-light">

<div class="d-flex konten">
  <?php include 'sidebar_aksi.php'; ?>

  <main class="flex-fill p-4 bg-light">
    <div class="container-fluid">
      <?php include __DIR__.'/header.php'; ?>

      <div class="text-center my-4">
        <h3 class="text-primary fw-bold">Profil Siswa</h3>
      </div>

      <!-- ALERT TEMPAT PESAN -->
      <div id="alertBox" class="alert d-none" role="alert"></div>

      <div class="card shadow-sm p-4 w-100">
        <form id="formProfil">
          <div class="mb-3">
            <label class="form-label">Username (tidak bisa diubah)</label>
            <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
          </div>

          <div class="mb-3">
            <label class="form-label">Nama / Nickname</label>
            <input type="text" class="form-control" name="nickname" value="<?= htmlspecialchars($user['nickname']) ?>" required>
          </div>

          <hr class="my-4">
          <h5 class="mb-3 text-secondary">Ubah Password</h5>

          <div class="mb-3">
            <label class="form-label">Password Lama</label>
            <input type="password" class="form-control" name="current_password" placeholder="Masukkan password lama">
          </div>

          <div class="mb-3">
            <label class="form-label">Password Baru</label>
            <input type="password" class="form-control" name="new_password" placeholder="Masukkan password baru">
          </div>

          <div class="mb-4">
            <label class="form-label">Konfirmasi Password Baru</label>
            <input type="password" class="form-control" name="confirm_password" placeholder="Ulangi password baru">
          </div>

          <button type="submit" class="btn btn-primary px-4">
            <i class="fa fa-save me-2"></i> Simpan Perubahan
          </button>
        </form>
      </div>
    </div>
  </main>
</div>

<?php include 'footer.php'; ?>

<script>
// === FORM AJAX HANDLER ===
document.getElementById('formProfil').addEventListener('submit', async function(e) {
  e.preventDefault();

  const form = e.target;
  const formData = new FormData(form);
  const alertBox = document.getElementById('alertBox');

  try {
    const res = await fetch('update_profil_ajax.php', {
      method: 'POST',
      body: formData
    });

    const data = await res.json();

    alertBox.classList.remove('d-none', 'alert-success', 'alert-danger');
    alertBox.classList.add(data.success ? 'alert-success' : 'alert-danger');
    alertBox.textContent = data.message;

    if (data.success) {
      setTimeout(() => alertBox.classList.add('d-none'), 3000);
    }

  } catch (err) {
    alertBox.classList.remove('d-none', 'alert-success');
    alertBox.classList.add('alert-danger');
    alertBox.textContent = 'Terjadi kesalahan koneksi.';
  }
});
</script>

</body>
</html>
