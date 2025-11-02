<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $val = (int)($_POST['bayar_per_minggu'] ?? 0);
    if ($val > 0) $_SESSION['bayar_per_minggu'] = $val;
    echo "OK";
}
