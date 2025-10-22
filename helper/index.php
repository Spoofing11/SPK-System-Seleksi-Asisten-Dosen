<?php
session_start();

// IP admin yang diizinkan
$allowed_ips = ['127.0.0.1', '::1', '114.10.29.110']; // Tambahkan IP lain jika perlu

// Cek apakah user adalah admin atau memiliki IP yang diizinkan
// Cek apakah user adalah admin atau mahasiswa
if (!isset($_SESSION['login']) || !in_array($_SESSION['login']['role'], ['admin', 'mahasiswa'])) {
    if (empty($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
        http_response_code(403);
        echo "403 Forbidden - Akses tidak diizinkan!";
        exit();
    }
}


// Tambahkan header keamanan
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");

// Jika lolos, akses helper diperbolehkan
echo "Selamat datang di helper, Admin!";
?>
