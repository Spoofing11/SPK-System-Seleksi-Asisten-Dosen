<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Pastikan dosen sudah login
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'dosen') {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!'); window.location='../login.php';</script>";
    exit;
}

// Ambil NIDN dosen dari session
$nidn_login = $_SESSION['login']['nidn'];  // Mengambil NIDN dari session

// Periksa apakah NIDN ditemukan di database
$query_dosen = "SELECT * FROM dosen WHERE nidn = '$nidn_login'";
$result_dosen = mysqli_query($connection, $query_dosen);

// Cek apakah query berhasil
if (!$result_dosen) {
    echo "<script>alert('Error: " . mysqli_error($connection) . "');</script>";
    exit;
}

$data_dosen = mysqli_fetch_assoc($result_dosen);

// Jika data dosen belum ada di tabel dosen, tampilkan form untuk melengkapi data
if (!$data_dosen) {
    $data_dosen = [
        'nidn' => $nidn_login,
        'nama_dosen' => '',
        'jenkel' => '',
        'fakultas' => '',
        'program_studi' => '',
        'gmail' => ''
    ];
}

// Proses jika form disubmit untuk update atau melengkapi data dosen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_dosen = mysqli_real_escape_string($connection, trim($_POST['nama_dosen']));
    $jenkel = mysqli_real_escape_string($connection, trim($_POST['jenkel']));
    $fakultas = mysqli_real_escape_string($connection, trim($_POST['fakultas']));
    $program_studi = mysqli_real_escape_string($connection, trim($_POST['program_studi']));
    $gmail = mysqli_real_escape_string($connection, trim($_POST['gmail']));

    // Jika data dosen sudah ada, lakukan update
    if ($data_dosen['nama_dosen'] !== '') {
        $update_query = "UPDATE dosen SET 
                            nama_dosen = '$nama_dosen', 
                            jenkel = '$jenkel', 
                            fakultas = '$fakultas', 
                            program_studi = '$program_studi',
                            gmail = '$gmail'
                         WHERE nidn = '$nidn_login'";
    } else {
        // Jika data dosen belum ada, lakukan insert
        $update_query = "INSERT INTO dosen (nidn, nama_dosen, jenkel, fakultas, program_studi,  gmail) VALUES 
                        ('$nidn_login', '$nama_dosen', '$jenkel', '$fakultas', '$program_studi', '$gmail')";
    }

    // Jalankan query update atau insert
    if (mysqli_query($connection, $update_query)) {
        $_SESSION['message'] = "Data berhasil disimpan!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menyimpan data: " . mysqli_error($connection);
        $_SESSION['message_type'] = "error";
    }
    header("Location: data_dosen.php");
    exit;
    
}
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
        <h1 class="h3 mb-0">Data Dosen</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 ">
            <div class="card shadow-lg border-light rounded">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Informasi Dosen</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <?php if (!empty($data_dosen['nidn'])): ?>
                            <table class="table table-bordered table-striped w-100">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Informasi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Nama</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['nama_dosen']) ?: 'Belum diisi' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>NIDN</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['nidn']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Jenis Kelamin</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['jenkel']) ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Fakultas</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['fakultas']) ?: 'Belum diisi' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Program Studi</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['program_studi']) ?: 'Belum diisi' ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong>Gmail</strong></td>
                                        <td><?= htmlspecialchars($data_dosen['gmail']) ?: 'Belum diisi' ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center">Data dosen belum tersedia.</p>
                        <?php endif; ?>

                        <!-- Tombol Ubah Data -->
                        <div class="d-flex justify-content-center mt-4">
                            <button class="btn btn-warning btn-lg" data-toggle="modal" data-target="#ubahDataModal">
                                <?= $data_dosen['nama_dosen'] ? 'Ubah Data' : 'Lengkapi Data' ?>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Modal Ubah/Lengkapi Data -->
<div class="modal fade" id="ubahDataModal" tabindex="-1" role="dialog" aria-labelledby="ubahDataModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ubahDataModalLabel"><?= $data_dosen['nama_dosen'] ? 'Ubah Data Dosen' : 'Lengkapi Data Dosen' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form untuk Ubah/Lengkapi Data Dosen -->
                <form method="POST" action="data_dosen.php">
                    <!-- NIDN hanya ditampilkan, tidak bisa diubah -->
                    <div class="form-group">
                        <label for="nidn">NIDN</label>
                        <input type="text" class="form-control" id="nidn" name="nidn" value="<?= htmlspecialchars($data_dosen['nidn']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nama_dosen">Nama</label>
                        <input type="text" class="form-control" id="nama_dosen" name="nama_dosen" value="<?= htmlspecialchars($data_dosen['nama_dosen']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="jenkel">Jenis Kelamin</label>
                        <select class="form-control" id="jenkel" name="jenkel" required>
                            <option value="" disabled <?= !$data_dosen['jenkel'] ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                            <option value="Laki-laki" <?= $data_dosen['jenkel'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="Perempuan" <?= $data_dosen['jenkel'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fakultas">Fakultas</label>
                        <input type="text" class="form-control" id="fakultas" name="fakultas" value="<?= htmlspecialchars($data_dosen['fakultas']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="program_studi">program_studi</label>
                        <input type="text" class="form-control" id="program_studi" name="program_studi" value="<?= htmlspecialchars($data_dosen['program_studi']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gmail">Gmail</label>
                        <input type="text" class="form-control" id="gmail" name="gmail" value="<?= htmlspecialchars($data_dosen['gmail']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block"><?= $data_dosen['nama_dosen'] ? 'Update Data' : 'Simpan Data' ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../layout/_bottom.php'; ?>