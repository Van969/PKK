<?php
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val = (int)($_POST['bayar_per_minggu'] ?? 0);
    if ($val <= 0) { echo "Invalid"; exit; }

    $stmt = $pdo->prepare("INSERT INTO setting(key_name,value) VALUES('bayar_per_minggu',?) 
        ON DUPLICATE KEY UPDATE value=?");
    $stmt->execute([$val, $val]);
    echo "OK";
}
