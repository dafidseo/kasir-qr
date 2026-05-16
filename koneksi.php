<?php
// koneksi.php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'kasir_qr';

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");
?>