<?php
require __DIR__ . '/../db.php';

// ✅ Pastikan file autoload mPDF bisa ditemukan meskipun struktur folder berbeda
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    // Coba kemungkinan lain (kalau vendor ada di luar satu folder lebih dalam)
    $autoload_alt = __DIR__ . '/../../vendor/autoload.php';
    if (file_exists($autoload_alt)) {
        $autoload = $autoload_alt;
    } else {
        die("⚠️ File <b>vendor/autoload.php</b> tidak ditemukan.<br>
        Jalankan perintah di terminal project root:<br><code>composer require mpdf/mpdf</code>");
    }
}
require_once $autoload;

use Mpdf\Mpdf;

// === Ambil data ===
$bayar_per_minggu = 5000;

// Data siswa
$stmt = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC");
$siswaData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Nama bulan
$bulanArr = [
    1=>"Jan",2=>"Feb",3=>"Mar",4=>"Apr",5=>"Mei",6=>"Jun",
    7=>"Jul",8=>"Agu",9=>"Sep",10=>"Okt",11=>"Nov",12=>"Des"
];

// === Mulai isi HTML ===
$html = "
<style>
body { font-family: sans-serif; font-size: 11pt; margin: 10px; }
h3 { text-align: center; margin-bottom: 15px; color: #333; }
table { border-collapse: collapse; width: 100%; margin-top: 10px; }
th, td { border: 1px solid #444; padding: 5px; text-align: center; }
th { background-color: #f2f2f2; font-weight: bold; }
td.text-start { text-align: left; }
.total { background: #198754; color: #fff; font-weight: bold; }
.footer { text-align: right; font-size: 10pt; margin-top: 20px; color: #555; }
</style>

<h3>Laporan Rekap Pembayaran Kas Per Bulan</h3>
<table>
<thead>
<tr>
    <th>Siswa</th>";
foreach ($bulanArr as $nb) {
    $html .= "<th>$nb</th>";
}
$html .= "<th>Total</th></tr>
</thead>
<tbody>
";

// === Isi tabel data siswa ===
foreach ($siswaData as $s) {
    $id = $s['id'];
    $html .= "<tr><td class='text-start'>" . htmlspecialchars($s['nama']) . "</td>";
    $totalTahun = 0;

    foreach ($bulanArr as $bulan => $namaB) {
        // Hitung jumlah minggu dibayar tiap bulan
        $q = $pdo->prepare("
            SELECT COUNT(*) 
            FROM pembayaran_mingguan_baru pm
            JOIN pembayaran_baru pb ON pm.pembayaran_id = pb.id
            WHERE pb.siswa_id = ? AND pb.bulan = ? AND pm.status = 1
        ");
        $q->execute([$id, $bulan]);
        $minggu = $q->fetchColumn();
        $jumlah = $minggu * $bayar_per_minggu;
        $totalTahun += $jumlah;

        $html .= "<td>Rp " . number_format($jumlah, 0, ',', '.') . "</td>";
    }

    $html .= "<td class='total'>Rp " . number_format($totalTahun, 0, ',', '.') . "</td></tr>";
}

$html .= "</tbody></table>";

// Tambahkan tanggal dan footer
$html .= "<div class='footer'>
Dicetak pada: " . date('d-m-Y H:i') . "
</div>";

// === Buat PDF ===
$mpdf = new Mpdf(['format' => 'A4-L']); // Landscape agar tabel muat
$mpdf->SetTitle("Laporan Rekap Pembayaran Per Bulan");
$mpdf->WriteHTML($html);
$mpdf->Output("Rekap_Pembayaran_Per_Bulan.pdf", "I"); // I = tampilkan di browser
