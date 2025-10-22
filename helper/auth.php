<?php
// Mulai session jika belum dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi untuk memeriksa apakah user sudah login
function isLogin($role = null) {
    require_once('config.php'); // Load BASE_URL

    // Jika user belum login, redirect ke login.php
    if (!isset($_SESSION['login'])) {
        if (basename($_SERVER['PHP_SELF']) !== 'login.php') {
            header('Location: ' . BASE_URL . 'login.php');
            exit();
        }
    }

    // Jika ada role yang dicek dan tidak sesuai, kembalikan ke dashboard utama
    if ($role !== null && $_SESSION['login']['role'] !== $role) {
        header('Location: ' . BASE_URL . 'dashboard/index.php');
        exit();
    }
}
?>
