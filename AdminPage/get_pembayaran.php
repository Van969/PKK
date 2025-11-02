<?php
require '../db.php';
session_start();

$bulan = (int)($_GET['bulan'] ?? date('n'));
$tahun = (int)($_GET['tahun'] ?? date('Y'));

$bayar_per_minggu = $_SESSION['bayar_per_minggu'] ?? 5000;

$siswa = $pdo->query("SELECT id, nama FROM siswa ORDER BY nama ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT pembayaran_id, minggu_ke, status FROM pembayaran_mingguan WHERE bulan=? AND tahun=?");
$stmt->execute([$bulan, $tahun]);

$data = [];
foreach ($stmt as $d){
    $data[$d['pembayaran_id']][$d['minggu_ke']] = (int)$d['status'];
}

$result = [];
foreach($siswa as $s){
    $id = $s['id'];
    $result[$id] = [
        'nama' => $s['nama'],
        'minggu' => []
    ];
    for($i=1;$i<=4;$i++){
        $result[$id]['minggu'][$i] = $data[$id][$i] ?? 0;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'bayar_per_minggu' => $bayar_per_minggu,
    'data' => $result
]);
