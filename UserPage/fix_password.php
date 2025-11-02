<?php
require __DIR__ . '/../db.php';

$stmt = $pdo->query("SELECT id, password FROM users");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    // cek kalau password masih belum di-hash (tidak diawali dengan $2y$)
    if (strpos($user['password'], '$2y$') !== 0) {
        $newHash = password_hash($user['password'], PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([$newHash, $user['id']]);
        echo "✅ Password user ID {$user['id']} sudah di-hash.<br>";
    }
}
echo "Selesai!";
