<?php
require __DIR__ . '/../db.php';

if(isset($_POST['id'])){
    $stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id=?");
    $stmt->execute([$_POST['id']]);
    echo "success";
    exit;
}

echo "error";
