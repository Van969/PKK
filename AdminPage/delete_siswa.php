<?php
require __DIR__ . '/../db.php';

$id = (int)($_POST['id'] ?? 0);
if (!$id) exit("Invalid ID");

try {
    $pdo->beginTransaction();

    // 1️⃣ Ambil semua ID pembayaran_baru milik siswa
    $stmt = $pdo->prepare("SELECT id FROM pembayaran_baru WHERE siswa_id = ?");
    $stmt->execute([$id]);
    $pembayaranBaru = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($pembayaranBaru) {
        $in = str_repeat('?,', count($pembayaranBaru) - 1) . '?';

        // 2️⃣ Hapus anak-anaknya dulu: pembayaran_mingguan_baru
        $pdo->prepare("DELETE FROM pembayaran_mingguan_baru WHERE pembayaran_id IN ($in)")
            ->execute($pembayaranBaru);

        // 3️⃣ Baru hapus pembayaran_baru
        $pdo->prepare("DELETE FROM pembayaran_baru WHERE id IN ($in)")
            ->execute($pembayaranBaru);
    }

    // 4️⃣ Ambil semua ID pembayaran lama
    $stmt = $pdo->prepare("SELECT id FROM pembayaran WHERE siswa_id = ?");
    $stmt->execute([$id]);
    $pembayaran = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($pembayaran) {
        $in = str_repeat('?,', count($pembayaran) - 1) . '?';

        // 5️⃣ Hapus anak-anak dari pembayaran lama
        $pdo->prepare("DELETE FROM pembayaran_mingguan WHERE pembayaran_id IN ($in)")
            ->execute($pembayaran);

        $pdo->prepare("DELETE FROM pembayaran_bulanan WHERE pembayaran_id IN ($in)")
            ->execute($pembayaran);

        $pdo->prepare("DELETE FROM pembayaran WHERE id IN ($in)")
            ->execute($pembayaran);
    }

    // 6️⃣ Terakhir, hapus siswa
    $pdo->prepare("DELETE FROM siswa WHERE id=?")->execute([$id]);

    $pdo->commit();
    echo "ok";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage();
}
