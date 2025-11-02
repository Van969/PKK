<?php
session_start();
require __DIR__ . '/../db.php';

header('Content-Type: application/json');

// Enable error reporting for debugging
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }

    $id     = $_POST['id'] ?? '';
    $title  = trim($_POST['title'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $note   = trim($_POST['note'] ?? '');

    if ($title === '' || $amount <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Judul dan jumlah wajib diisi!']);
        exit;
    }

    // UPDATE
    if ($id) {
        $stmt = $pdo->prepare("UPDATE pengeluaran SET title=?, amount=?, note=? WHERE id=?");
        $stmt->execute([$title, $amount, $note, $id]);
    }
    // INSERT
    else {
        $stmt = $pdo->prepare("INSERT INTO pengeluaran (title, amount, note, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$title, $amount, $note]);
    }

    echo json_encode(['status' => 'success']);
    exit;

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'DB ERROR: ' . $e->getMessage()
    ]);
    exit;
}
