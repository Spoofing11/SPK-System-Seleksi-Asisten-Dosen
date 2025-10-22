<?php
session_start();
require_once('helper/auth.php');  
require_once('helper/config.php');

// Pastikan user sudah login
isLogin();

// Ambil role user
$role = $_SESSION['login']['role'];

// Redirect berdasarkan role jika user masuk ke dashboard utama
switch ($role) {
    case 'admin':
        header('Location: ' . BASE_URL . 'dashboard/index.php');
        exit();
    case 'mahasiswa':
        header('Location: ' . BASE_URL . 'dashboard/mahasiswa.php');
        exit();
    case 'dosen':
        header('Location: ' . BASE_URL . 'dashboard/dosen.php');
        exit();
    case 'koordinator':
        header('Location: ' . BASE_URL . 'dashboard/koordinator_dosen.php');
        exit();
    default:
        header('Location: ' . BASE_URL . 'login.php');
        exit();
}
?>
