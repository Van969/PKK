<?php
require __DIR__ . '/../db.php'; // pastikan koneksi PDO benar

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');

    if ($nama === '') {
        echo "❌ Nama siswa tidak boleh kosong.";
        exit;
    }

    try {
        echo "<pre>";

        // Cek database aktif
        $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
        echo "Database aktif: $dbName\n";

        // Pastikan engine benar
        $engineSiswa = $pdo->query("SHOW TABLE STATUS WHERE Name = 'siswa'")->fetch();
        $enginePemb = $pdo->query("SHOW TABLE STATUS WHERE Name = 'pembayaran'")->fetch();
        echo "Engine siswa: " . $engineSiswa['Engine'] . " | pembayaran: " . $enginePemb['Engine'] . "\n\n";

        // Mulai transaksi agar konsisten
        $pdo->beginTransaction();

        echo "--- INSERT SISWA ---\n";
        $stmt = $pdo->prepare("INSERT INTO siswa (nama, created_at) VALUES (?, NOW())");
        $stmt->execute([$nama]);

        $siswa_id = $pdo->lastInsertId();
        echo "ID siswa baru: $siswa_id\n";

        // Pastikan benar-benar masuk
        $cek = $pdo->prepare("SELECT id, nama FROM siswa WHERE id = ?");
        $cek->execute([$siswa_id]);
        $row = $cek->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Insert siswa gagal, data tidak ditemukan setelah insert.");
        }
        echo "Data siswa terdaftar: " . json_encode($row) . "\n\n";

        echo "--- INSERT PEMBAYARAN ---\n";
        $stmtPemb = $pdo->prepare("
            INSERT INTO pembayaran (siswa_id, bulan, tahun, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $tahun = date('Y');

        for ($bulan = 1; $bulan <= 12; $bulan++) {
            echo "→ Tambah pembayaran bulan ke-$bulan untuk siswa_id $siswa_id ... ";
            $stmtPemb->execute([$siswa_id, $bulan, $tahun]);
            echo "OK\n";
        }

        // Commit transaksi
        $pdo->commit();
        echo "\n✅ Semua data berhasil ditambahkan!\n";
        echo "</pre>";
    }

    // Tangani error SQL
    catch (PDOException $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "</pre>";
        echo "❌ SQL Error: " . $e->getMessage() . "\n";
    }

    // Tangani error umum
    catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo "</pre>";
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
?>
