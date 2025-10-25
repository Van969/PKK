<?php
// SetSession.php
session_start();

$username = $_GET['username'] ?? '';
$role = $_GET['role'] ?? '';

if ($username && $role) {
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $role;
    echo "Session di-set";
} else {
    http_response_code(400);
    echo "Data tidak lengkap";
}
