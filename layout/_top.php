<?php
ob_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/system-seleksiasistendosen/helper/auth.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Perbaiki cara mendapatkan role agar tidak selalu 'Guest'
$role = isset($_SESSION['login']['role']) ? $_SESSION['login']['role'] : 'Guest';

// Jika page_title sudah ada, tambahkan role di belakangnya
$page_title = isset($page_title) ? "$page_title ($role)" : "Universitas Pamulang ($role)";

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
  <link rel="icon" type="image/x-icon" href="../assets/img/favicon/logo.png">

  <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?></title>


  <!-- General CSS Files -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.2/css/all.css" integrity="sha384-fnmOCqbTlWIlj8LyTjo7mOUStjsKC4pOpQbqyi7RrhN7udi9RwhKkMHpvLbHG9Sr" crossorigin="anonymous">

  <!-- CSS Libraries -->
  <link rel="stylesheet" href="../assets/modules/jqvmap/dist/jqvmap.min.css">
  <link rel="stylesheet" href="../assets/modules/summernote/summernote-bs4.css">
  <link rel="stylesheet" href="../assets/modules/owlcarousel2/dist/assets/owl.carousel.min.css">
  <link rel="stylesheet" href="../assets/modules/owlcarousel2/dist/assets/owl.theme.default.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/datatables.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/DataTables-1.10.16/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/modules/datatables/Select-1.2.4/css/select.bootstrap4.min.css">
  <link rel="stylesheet" href="../assets/modules/izitoast/css/iziToast.min.css">

  <!-- Template CSS -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/components.min.css">
  <style>
    /* Layar Loading */
    #loading-screen {
        position: fixed;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    /* Animasi Logo Berputar */
    .loading-logo {
        width: 80px;
        height: 80px;
        animation: spin 1.5s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>


</head>

<body>
<body>
<body>
    <div id="loading-screen">
        <img src="../assets/img/favicon/logo.png" class="loading-logo">
    </div>


  <div id="app">
    <div class="main-wrapper main-wrapper-1">
      <?php
      require_once '_header.php';  // Memanggil Header
      require_once '_sidenav.php';  // Memanggil Sidebar
      ?>
      <!-- Main Content -->
      <div class="main-content">
        
