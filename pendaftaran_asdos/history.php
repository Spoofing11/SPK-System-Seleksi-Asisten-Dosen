<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek apakah mahasiswa sudah login
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'mahasiswa') {
    $_SESSION['message'] = 'Anda tidak memiliki akses ke halaman ini!';
    $_SESSION['message_type'] = 'error';
    header('Location: ../login.php');
    exit();
}

// Ambil nim mahasiswa dari sesi login
$nim = $_SESSION['login']['nim'];

// Ambil data riwayat pendaftaran dari tabel pendaftaran_asisten
$queryRiwayat = mysqli_query($connection, "
    SELECT 
        nama_mahasiswa,
        nama_matkul,
        nama_dosen,
        status,
        created_at
    FROM pendaftaran_asisten
    WHERE nim = '$nim'
    ORDER BY created_at DESC
") or die(mysqli_error($connection));
?>

<div class="container mt-5">
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Riwayat & Status Pendaftaran Asdos</h2>
        </div>
        <div class="card-body">
            <?php if (mysqli_num_rows($queryRiwayat) > 0) : ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="text-center bg-light">
                            <tr>
                                <th>Nama Mahasiswa</th>
                                <th>Nama Mata Kuliah</th>
                                <th>Nama Dosen</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($queryRiwayat)) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['nama_mahasiswa']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_matkul']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_dosen']) ?></td>
                                    <td class="text-center"><?= htmlspecialchars($row['status']) ?></td>
                                    <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else : ?>
                <div class="alert alert-info text-center">
                    Anda belum memiliki riwayat pendaftaran.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../layout/_bottom.php'; ?>
