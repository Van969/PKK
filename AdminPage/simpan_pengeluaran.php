<?php
require __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['id'] ?? '';
  $title = trim($_POST['title'] ?? '');
  $amount = (int)($_POST['amount'] ?? 0);
  $description = trim($_POST['description'] ?? '');

  if ($id) {
    // Update data
    $stmt = $pdo->prepare("UPDATE pengeluaran SET title=?, amount=?, note=? WHERE id=?");
    $stmt->execute([$title, $amount, $description, $id]);
  } else {
    // Tambah data baru
    $stmt = $pdo->prepare("INSERT INTO pengeluaran (title, amount, note) VALUES (?,?,?)");
    $stmt->execute([$title, $amount, $description]);
  }

  if (isset($_POST['ajax'])) {
    echo 'OK';
    exit;
  }

  header('Location: aksi.php');
  exit;
}
?>
