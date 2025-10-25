<?php
require __DIR__ . '/../db.php';


if(isset($_POST['id'], $_POST['nama'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];

    try {
        $stmt = $pdo->prepare("UPDATE siswa SET nama=? WHERE id=?");
        if($stmt->execute([$nama, $id])) {
            echo 'ok';
        } else {
            echo 'gagal';
        }
    } catch(PDOException $e) {
        echo 'error: '.$e->getMessage();
    }
} else {
    echo 'data kosong';
}
