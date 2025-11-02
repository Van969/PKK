<?php
session_start();
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    header('Location: ../LoginPage/Login.php');
    exit;
}

$username = $_SESSION['username'];

// Ambil data user
$stmt = $pdo->prepare("SELECT id, username, nickname, role, password FROM users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h3 style='color:red;text-align:center;'>User tidak ditemukan.</h3>";
    exit;
}

// Ambil ID siswa dari tabel siswa berdasarkan nama
$stmtSiswa = $pdo->prepare("SELECT id FROM siswa WHERE nama = ?");
$stmtSiswa->execute([$user['username']]);
$siswa_id = $stmtSiswa->fetchColumn() ?: 0;

// Hitung total pembayaran siswa ini dari pembayaran_baru (status=1)
$stmtBayar = $pdo->prepare("
    SELECT COUNT(*) 
    FROM pembayaran_baru 
    WHERE siswa_id = ? AND status = 1
");
$stmtBayar->execute([$siswa_id]);
$total_minggu_bayar = $stmtBayar->fetchColumn();

// Ambil tarif per minggu dari session (default 5000)
$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;
$total_pemasukan_siswa = $total_minggu_bayar * $bayar_per_minggu;

// Hitung total siswa dan pengeluaran
$totalSiswa = $pdo->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
$totalPengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();
$beban_pengeluaran_per_siswa = ($totalSiswa > 0) ? ($totalPengeluaran / $totalSiswa) : 0;
$sisa_saldo_siswa = $total_pemasukan_siswa - $beban_pengeluaran_per_siswa;

// Pesan notifikasi
$message = '';
$alertType = 'info';

// ===============================
// Proses ubah nickname
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nickname'])) {
    $newNickname = trim($_POST['nickname']);
    if ($newNickname !== '') {
        $update = $pdo->prepare("UPDATE users SET nickname = ? WHERE username = ?");
        if ($update->execute([$newNickname, $username])) {
            $message = "Profil berhasil diperbarui!";
            $alertType = 'success';
            $user['nickname'] = $newNickname;
        } else {
            $message = "Gagal memperbarui profil.";
            $alertType = 'danger';
        }
    } else {
        $message = "Nama tidak boleh kosong.";
        $alertType = 'warning';
    }
}

// ===============================
// Proses ubah password
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['old_password'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($old === '' || $new === '' || $confirm === '') {
        $message = "Semua kolom password harus diisi.";
        $alertType = 'warning';
    } elseif (!password_verify($old, $user['password'])) {
        $message = "Password lama salah.";
        $alertType = 'danger';
    } elseif ($new !== $confirm) {
        $message = "Password baru dan konfirmasi tidak cocok.";
        $alertType = 'warning';
    } else {
        $hashed = password_hash($new, PASSWORD_BCRYPT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
        if ($update->execute([$hashed, $username])) {
            $message = "Password berhasil diubah!";
            $alertType = 'success';
        } else {
            $message = "Gagal mengubah password.";
            $alertType = 'danger';
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Saya</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<style>
    #edit-form { display: none; margin-top: 10px; }
    #password-form { display: none; margin-top: 15px; }
</style>
</head>
<body class="bg-light">

<div class="d-flex konten">
    <?php include __DIR__.'/sidebar.php'; ?>

    <main class="flex-fill p-4 bg-light">
        <?php include __DIR__.'/header.php'; ?>

        <div class="container py-5">
            <h3 class="text-center text-primary mb-4">Profil Saya</h3>

            <?php if ($message): ?>
                <div class="alert alert-<?= $alertType ?> text-center"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Username -->
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" 
                       value="<?= htmlspecialchars($user['username']) ?>" disabled>
            </div>

            <!-- Nickname -->
            <div class="mb-3">
                <label class="form-label">Nama / Nickname</label>
                <div class="d-flex align-items-center gap-2">
                    <span id="nickname-display"><?= htmlspecialchars($user['nickname']) ?></span>
                    <button id="edit-btn" class="btn btn-sm btn-outline-primary">Edit</button>
                </div>

                <form id="edit-form" method="POST" class="d-flex gap-2 mt-2">
                    <input type="text" name="nickname" class="form-control" 
                           value="<?= htmlspecialchars($user['nickname']) ?>" required>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </form>
            </div>

            <hr>

            <!-- Ganti Password -->
            <div class="mb-3">
                <label class="form-label">Password</label>
                <button id="change-pass-btn" class="btn btn-sm btn-outline-danger">Ubah Password</button>

                <form id="password-form" method="POST" class="mt-3">
                    <div class="mb-2">
                        <label>Password Lama</label>
                        <input type="password" name="old_password" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Simpan Password</button>
                </form>
            </div>

            <hr>

        </div>
    </main>
</div>

<script>
const editBtn = document.getElementById('edit-btn');
const editForm = document.getElementById('edit-form');
const changePassBtn = document.getElementById('change-pass-btn');
const passForm = document.getElementById('password-form');

editBtn.addEventListener('click', () => {
    editForm.style.display = editForm.style.display === 'flex' ? 'none' : 'flex';
});

changePassBtn.addEventListener('click', () => {
    passForm.style.display = passForm.style.display === 'block' ? 'none' : 'block';
});
</script>

</body>
</html>
