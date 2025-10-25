<?php
require __DIR__ . '/../db.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ambil ID dari POST atau GET
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
} else {
    $id = $_GET['id'] ?? 0;
}

if ($id) {
    // ===== DELETE menggunakan PDO =====
    $stmt = $pdo->prepare("DELETE FROM pengeluaran WHERE id = ?");
    $stmt->execute([$id]);

    if (isset($_POST['ajax'])) {
        echo "success";
        exit;
    } else {
        header("Location: index.php"); // redirect biasa
        exit;
    }
} else {
    echo "ID tidak ditemukan.";
}
