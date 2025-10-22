<?php
$page_title = "Mahasiswa - Universitas Pamulang ";
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('mahasiswa');

// Mengambil jumlah total mahasiswa
$mahasiswa = mysqli_query($connection, "SELECT COUNT(*) FROM mahasiswa");
$total_mahasiswa = mysqli_fetch_array($mahasiswa)[0];

// Ambil nim dari session login
$nim = $_SESSION['login']['nim'];

// Query untuk mengambil status pendaftaran asisten dosen
$queryStatus = mysqli_query($connection, "SELECT status FROM pendaftaran_asisten WHERE nim = '$nim'");

// Cek apakah query berhasil
if (!$queryStatus) {
    die("Query gagal: " . mysqli_error($connection));
}

// Ambil data status dari query
$statusData = mysqli_fetch_assoc($queryStatus);

?>

<section class="section">
    
    <?php if (isset($_SESSION['message'])) : ?>
    <style>
        .custom-alert {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 20px;
            border-radius: 5px;
            min-width: 300px;
            text-align: center;
            font-weight: bold;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1050;
            opacity: 1;
            transition: opacity 0.5s ease-in-out;
        }
        .success { background-color: #28a745; color: white; }
        .error { background-color: #dc3545; color: white; }
        .warning { background-color: #ffc107; color: black; }
        .info { background-color: #17a2b8; color: white; }
    </style>

    <div id="alertBox" class="custom-alert <?= $_SESSION['message_type']; ?>">
        <?= $_SESSION['message']; ?>
    </div>

    <script>
        setTimeout(() => {
            let alertBox = document.getElementById('alertBox');
            if (alertBox) {
                alertBox.style.opacity = "0";
                setTimeout(() => alertBox.remove(), 500);
            }
        }, 3000);
    </script>

<?php unset($_SESSION['message'], $_SESSION['message_type']); endif; ?>
  <div class="section-header">
    <h1>Dashboard</h1>
  </div>
  
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
      <div class="card card-statistic-1">
        <div class="card-icon bg-danger">
          <i class="far fa-user"></i>
        </div>
        <div class="card-wrap">
          <div class="card-header">
            <h4>Total Mahasiswa</h4>
          </div>
          <div class="card-body">
            <?= $total_mahasiswa ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Kotak Status Asisten Dosen -->
  <div class="row">
    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
        <div class="card card-statistic-1">
            <div class="card-icon bg-info">
                <i class="far fa-check-circle"></i>
            </div>
            <div class="card-wrap">
                <div class="card-header">
                    <h4>Status </h4>
                </div>
                <div class="card-body">
                    <?php
                    // Menampilkan status pendaftaran
                    if ($statusData) {
                        $status = $statusData['status'];
                        if ($status == 'Pending') {
                            echo "<div class='badge badge-warning' style='font-size: 1.2rem;'>Pending</div>";  // Jika status Pending
                        } else {
                            echo "<b>{$status}</b>";
                        }
                    } else {
                        echo "<div class='badge badge-secondary' style='font-size: 1.2rem;'>Belum Mendaftar</div>";  // Jika status tidak ditemukan
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Menambahkan CSS dalam tag style -->
<style>
    .badge {
        padding: 10px 20px;
        border-radius: 25px;
        font-weight: bold;
        text-transform: uppercase;
        display: inline-block;
    }

    .badge-warning {
        background-color: #ffcc00;
        color: #333;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }
</style>

</section>

<?php
require_once '../layout/_bottom.php';
?>
