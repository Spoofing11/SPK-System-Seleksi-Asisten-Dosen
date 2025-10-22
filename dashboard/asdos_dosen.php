<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek akses hanya untuk dosen
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'dosen') {
    $_SESSION['message'] = "Anda tidak memiliki akses ke halaman ini!";
    $_SESSION['message_type'] = "error";
    header("Location: ../login.php");
    exit;
}

// Ambil NIDN dosen yang sedang login
$nidn = $_SESSION['login']['nidn'];

// Cek apakah ada mahasiswa yang mendaftar kepada dosen ini
$query_total = "SELECT COUNT(*) as total FROM pendaftaran_asisten WHERE nidn = ?";
$stmt_total = $connection->prepare($query_total);
$stmt_total->bind_param("s", $nidn);
$stmt_total->execute();
$result_total = $stmt_total->get_result();
$data_total = $result_total->fetch_assoc();
$total_pendaftar = $data_total['total']; // Jumlah total mahasiswa yang mendaftar ke dosen ini

// Cek apakah ada mahasiswa yang diterima oleh dosen ini
$query_diterima = "SELECT nim, nama_mahasiswa, nama_dosen, status 
                   FROM pendaftaran_asisten 
                   WHERE status = 'Diterima' AND nidn = ?";
$stmt_diterima = $connection->prepare($query_diterima);
$stmt_diterima->bind_param("s", $nidn);
$stmt_diterima->execute();
$result_diterima = $stmt_diterima->get_result();
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
    <div class="section-header d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Data Mahasiswa Yang Mendaftar</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="card-title mb-0">Mahasiswa Yang Mendaftar</h5>
                </div>
                <div class="card-body">

                    <?php if ($total_pendaftar == 0): ?>
                        <p class="alert alert-info text-center">Belum ada mahasiswa yang mendaftar kepada Anda.</p>
                    <?php elseif ($result_diterima->num_rows == 0): ?>
                        <p class="alert alert-warning text-center">Belum ada mahasiswa yang diterima. Status masih dalam proses.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-dark">
                                    <tr class="text-center">
                                        <th>NIM</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($row = $result_diterima->fetch_assoc()): ?>
                                        <tr>
                                            <td class="text-center"><?= htmlspecialchars($row['nim']); ?></td>
                                            <td><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                            <td class="text-center">
                                                <span class="badge bg-success"><?= htmlspecialchars($row['status']); ?></span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../layout/_bottom.php'; ?>
