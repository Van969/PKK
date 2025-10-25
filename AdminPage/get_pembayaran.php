<?php
require '../db.php';

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

// Ambil semua siswa
$siswa = $pdo->query("SELECT id FROM siswa")->fetchAll(PDO::FETCH_ASSOC);

// Ambil pembayaran bulan ini
$stmt = $pdo->prepare("SELECT pembayaran_id, minggu_ke, status FROM pembayaran_mingguan WHERE bulan=? AND tahun=?");
$stmt->execute([$bulan, $tahun]);
$data = [];
foreach ($stmt as $d){
    $data[$d['pembayaran_id']][$d['minggu_ke']] = (int)$d['status'];
}

// Gabungkan supaya setiap siswa punya 4 minggu
$result = [];
foreach($siswa as $s){
    $id = $s['id'];
    $result[$id] = [];
    for($i=1;$i<=4;$i++){
        $result[$id][$i] = $data[$id][$i] ?? 0;
    }
}

header('Content-Type: application/json');
echo json_encode([
    "$tahun-$bulan" => $result
]);
