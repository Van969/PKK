<?php
require __DIR__ . '/../db.php';

// === Coba muat mPDF dari Composer, jika tidak ada tampilkan pesan ===
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('⚠️ File autoload.php tidak ditemukan. Jalankan <b>composer require mpdf/mpdf</b> atau pakai versi manual.');
}
require_once $autoloadPath;

use Mpdf\Mpdf;

// === Ambil data pengeluaran ===
$stmt = $pdo->query("SELECT * FROM pengeluaran ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === Fungsi ubah hari ke Bahasa Indonesia ===
function hariIndo($tanggal) {
    $namaHari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    return $namaHari[date('l', strtotime($tanggal))];
}

// === Buat tampilan HTML ===
$html = "
<style>
body { font-family: sans-serif; font-size: 12pt; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #444; padding: 6px; text-align: left; }
th { background-color: #f2f2f2; text-align: center; }
h3 { text-align: center; margin-bottom: 10px; }
</style>

<h3>Laporan Pengeluaran Kas Kelas</h3>
<table>
<tr>
    <th>Tanggal</th>
    <th>Judul</th>
    <th>Jumlah</th>
    <th>Keterangan</th>
</tr>";

if ($data) {
    foreach ($data as $row) {
        $tanggal = hariIndo($row['created_at']) . ', ' . date('d-m-Y', strtotime($row['created_at']));
        $html .= "
        <tr>
            <td>{$tanggal}</td>
            <td>" . htmlspecialchars($row['title']) . "</td>
            <td>Rp " . number_format($row['amount'], 0, ',', '.') . "</td>
            <td>" . htmlspecialchars($row['note'] ?: '-') . "</td>
        </tr>";
    }
} else {
    $html .= "<tr><td colspan='4' align='center'>Belum ada data</td></tr>";
}

$html .= "</table>";

// === Buat PDF ===
$mpdf = new Mpdf(['format' => 'A4']);
$mpdf->WriteHTML($html);
$mpdf->Output('Laporan_Pengeluaran.pdf', 'I'); // I = tampilkan di browser
