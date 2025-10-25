<?php
$servername = "localhost";
$dbusername = "root"; // sesuaikan
$dbpassword = ""; // sesuaikan
$dbname = "go_kas";

// Koneksi ke database
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$username = $_POST['username'] ??'';
$password = $_POST['password'] ??'';

// Cari user di database
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->execute()) {
    echo json_encode(["status"=>"success","message"=>"Register berhasil"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Gagal register: " . $conn->error]);
}
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        if ($row['role'] === 'admin') {
            header("Location: admin.html");
        } else {
            header("Location: user.html");
        }
        exit;
    } else {
        echo "Password salah!";
    }
} else {
    echo "Username tidak ditemukan!";
}

$conn->close();
?>
