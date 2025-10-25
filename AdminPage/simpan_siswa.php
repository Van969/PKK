<?php
require __DIR__ . '/../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_POST['nama']) && trim($_POST['nama']) !== '') {
    $nama = trim($_POST['nama']);

    try {
        $pdo->beginTransaction();

        // 1) insert siswa
        $stmt = $pdo->prepare("INSERT INTO siswa (nama, created_at) VALUES (?, NOW())");
        $stmt->execute([$nama]);
        $siswa_id = $pdo->lastInsertId();

        // 2) insert ke pembayaran (kolom siswa_id)
        $stmt2 = $pdo->prepare("INSERT INTO pembayaran (id) VALUES (?)");
        $stmt2->execute([$siswa_id]);
        $pembayaran_id = $pdo->lastInsertId();

        // 3) buat 4 baris default di pembayaran_mingguan
        $stmt3 = $pdo->prepare("INSERT INTO pembayaran_mingguan (pembayaran_id, minggu_ke, status) VALUES (?, ?, ?)");
        for ($i=1; $i<=4; $i++) {
            $stmt3->execute([$pembayaran_id, $i, 0]); // status 0 = belum bayar
        }

        $pdo->commit();
        echo "ok";
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo "error: " . $e->getMessage();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "error: " . $e->getMessage();
    }
} else {
    echo "Nama siswa tidak boleh kosong.";
}
