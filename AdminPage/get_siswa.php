<?php
require __DIR__ . '/../db.php';

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id=?");
$stmt->execute([$id]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
