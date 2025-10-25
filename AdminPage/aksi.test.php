<?php
require __DIR__ . '/../db.php';

$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? null;

// ==============================
// 1️⃣  TAMPIL DATA PEMBAYARAN
// ==============================
if (!$aksi) {
    $bulan = date('n');
    $tahun = date('Y');

    $stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE bulan = ? AND tahun = ?");
    $stmt->execute([$bulan, $tahun]);
    $pembayaran = $stmt->fetchAll(PDO::FETCH_ASSOC);

    include 'index.php';
    exit;
}

// ==============================
// 2️⃣  TAMBAH SISWA
// ==============================
if ($aksi === 'tambah') {
    $nama = trim($_POST['name']);
    $kelas = trim($_POST['kelas'] ?? '');
    $bulan = date('n');
    $tahun = date('Y');

    if ($nama === '') {
        echo json_encode(['error' => 'Nama tidak boleh kosong']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO pembayaran (name, kelas, bulan, tahun) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nama, $kelas, $bulan, $tahun]);

    echo json_encode(['success' => true]);
    exit;
}

// ==============================
// 3️⃣  EDIT DATA SISWA
// ==============================
if ($aksi === 'edit') {
    $id = $_POST['id'] ?? null;
    $nama = trim($_POST['name']);
    $kelas = trim($_POST['kelas'] ?? '');

    if (!$id) {
        echo json_encode(['error' => 'ID tidak ditemukan']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE pembayaran SET name = ?, kelas = ? WHERE id = ?");
    $stmt->execute([$nama, $kelas, $id]);

    echo json_encode(['success' => true]);
    exit;
}

// ==============================
// 4️⃣  HAPUS DATA SISWA
// ==============================
if ($aksi === 'hapus') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        echo json_encode(['error' => 'ID tidak ditemukan']);
        exit;
    }

    // Hapus relasi mingguan dulu
    $pdo->prepare("DELETE FROM pembayaran_mingguan WHERE pembayaran_id = ?")->execute([$id]);
    // Baru hapus siswa
    $pdo->prepare("DELETE FROM pembayaran WHERE id = ?")->execute([$id]);

    echo json_encode(['success' => true]);
    exit;
}

// ==============================
// 5️⃣  UPDATE STATUS PEMBAYARAN MINGGUAN
// ==============================
if ($aksi === 'update_bayar') {
    $pembayaran_id = $_POST['pembayaran_id'] ?? null;
    $minggu_ke = $_POST['minggu_ke'] ?? null;
    $status = $_POST['status'] ?? 0;

    if (!$pembayaran_id || !$minggu_ke) {
        http_response_code(400);
        echo json_encode(['error' => 'Data tidak lengkap']);
        exit;
    }

    $bayar_per_minggu = 2000;

    $stmt = $pdo->prepare("SELECT id FROM pembayaran_mingguan WHERE pembayaran_id = ? AND minggu_ke = ?");
    $stmt->execute([$pembayaran_id, $minggu_ke]);
    $ada = $stmt->fetch();

    if ($ada) {
        $stmt = $pdo->prepare("
            UPDATE pembayaran_mingguan 
            SET status = ?, jumlah_bayar = ?, tanggal_bayar = CURRENT_TIMESTAMP
            WHERE pembayaran_id = ? AND minggu_ke = ?
        ");
        $stmt->execute([$status, $status ? $bayar_per_minggu : 0, $pembayaran_id, $minggu_ke]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO pembayaran_mingguan (pembayaran_id, minggu_ke, status, jumlah_bayar)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$pembayaran_id, $minggu_ke, $status, $status ? $bayar_per_minggu : 0]);
    }

    echo json_encode(['success' => true]);
    exit;
}

// ==============================
// 6️⃣  HITUNG TOTAL UNTUK SIDEBAR
// ==============================
if ($aksi === 'get_total') {
    $stmt = $pdo->query("SELECT COALESCE(SUM(total_pemasukan),0) FROM pemasukan");
    $total_pemasukan = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran");
    $total_pengeluaran = $stmt->fetchColumn();

    $saldo = $total_pemasukan - $total_pengeluaran;

    echo json_encode([
        'pemasukan' => $total_pemasukan,
        'pengeluaran' => $total_pengeluaran,
        'saldo' => $saldo
    ]);
    exit;
}

echo json_encode(['error' => 'Aksi tidak dikenali']);
exit;
?>
