<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require __DIR__ . '/../db.php';
date_default_timezone_set('Asia/Jakarta');

$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;

// Hitung total pembayaran_baru & mingguan (JOIN ke pembayaran_baru)
$total_mingguan = $pdo->query("
    SELECT COUNT(*) 
    FROM pembayaran_mingguan_baru pm
    JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
    WHERE pm.status = 1
")->fetchColumn();

$total_pembayaran_baru = $pdo->query("SELECT COUNT(*) FROM pembayaran_baru WHERE status = 1")->fetchColumn();

$total_rupiah = ($total_pembayaran_baru + $total_mingguan) * $bayar_per_minggu;

// Total pemasukan tambahan dari tabel pemasukan
$total_pemasukan_db = $pdo->query("SELECT COALESCE(SUM(total_pemasukan),0) FROM pemasukan")->fetchColumn();
$total_pemasukan = $total_pemasukan_db + $total_rupiah;

// Total pengeluaran
$total_pengeluaran = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM pengeluaran")->fetchColumn();

// Saldo bersih
$saldo_bersih = $total_pemasukan - $total_pengeluaran;

header('Content-Type: application/json');
echo json_encode([
    'total_pemasukan'   => (float)$total_pemasukan,
    'total_pengeluaran' => (float)$total_pengeluaran,
    'saldo_bersih'      => (float)$saldo_bersih,
    'bayar_per_minggu'  => (int)$bayar_per_minggu
]);
