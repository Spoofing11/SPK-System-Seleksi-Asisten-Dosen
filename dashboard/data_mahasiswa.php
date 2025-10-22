<?php
require_once '../layout/_top.php'; // Memastikan header atau layout bagian atas dipanggil
require_once '../helper/connection.php'; // Pastikan session_start sudah dipanggil di sini

// Validasi akses hanya untuk mahasiswa
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] !== 'mahasiswa') {
    echo "<script>alert('Anda tidak memiliki akses ke halaman ini!'); window.location='../login.php';</script>";
    exit;
}

// Ambil NIM dari session
$nim = $_SESSION['login']['nim'];

// Ambil data mahasiswa dari database
$query = "SELECT * FROM mahasiswa WHERE nim = ?";
$stmt = $connection->prepare($query);
$stmt->bind_param('s', $nim);
$stmt->execute();
$result = $stmt->get_result();
$mahasiswa = $result->fetch_assoc();

// Jika data mahasiswa belum ada di tabel mahasiswa, tampilkan pesan
if (!$mahasiswa) {
    echo "<script>alert('Data mahasiswa tidak ditemukan!');</script>";
    // Mengatur nilai default jika data mahasiswa tidak ditemukan
    $mahasiswa = [
        'nim' => $nim,
        'nama' => '',
        'jenis_kelamin' => '',
        'kota_kelahiran' => '',
        'tanggal_kelahiran' => '',
        'alamat' => '',
        'program_studi' => '',
        'semester' => '',
        'ipk' => '',
        'gmail' => '',
        'no_handphone' => ''
    ];
}

// Proses jika form disubmit untuk update data mahasiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = mysqli_real_escape_string($connection, trim($_POST['nama']));
    $jenis_kelamin = mysqli_real_escape_string($connection, trim($_POST['jenis_kelamin']));
    $kota_kelahiran = mysqli_real_escape_string($connection, trim($_POST['kota_kelahiran']));
    $tanggal_kelahiran = mysqli_real_escape_string($connection, trim($_POST['tanggal_kelahiran']));
    $alamat = mysqli_real_escape_string($connection, trim($_POST['alamat']));
    $program_studi = mysqli_real_escape_string($connection, trim($_POST['program_studi']));
    $semester = mysqli_real_escape_string($connection, trim($_POST['semester']));
    $ipk = mysqli_real_escape_string($connection, trim($_POST['ipk']));
    $gmail = mysqli_real_escape_string($connection, trim($_POST['gmail']));
    $no_handphone = mysqli_real_escape_string($connection, trim($_POST['no_handphone']));

    $update_query = "UPDATE mahasiswa SET 
        nama = ?,
        jenis_kelamin = ?,
        kota_kelahiran = ?,
        tanggal_kelahiran = ?,
        alamat = ?,
        program_studi = ?,
        semester = ?,
        ipk = ?,
        gmail = ?,
        no_handphone = ?
    WHERE nim = ?";

    $stmt_update = $connection->prepare($update_query);
    $stmt_update->bind_param('sssssssssss', $nama, $jenis_kelamin, $kota_kelahiran, $tanggal_kelahiran, $alamat, $program_studi, $semester,  $ipk, $gmail, $no_handphone, $nim);

    if ($stmt_update->execute()) {
        $_SESSION['message'] = 'Data berhasil diperbarui!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal memperbarui data: ' . $stmt_update->error;
        $_SESSION['message_type'] = 'error';
    }
    
    header('Location: data_mahasiswa.php');
    exit();
    
}

// Validasi data ketika form disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['program_studi'] !== 'Teknik Informatika') {
        die('Program Studi tidak valid.');
    }
}

// Nilai default program studi
$program_studi = 'Teknik Informatika';

$queryIPK = $connection->prepare("SELECT SUM(n.mutu) AS total_mutu, SUM(m.sks) AS total_sks
                                  FROM nilai n 
                                  JOIN matakuliah m ON n.kode_matkul = m.kode_matkul
                                  WHERE n.nim = ?");
$queryIPK->bind_param('s', $nim);
$queryIPK->execute();
$resultIPK = $queryIPK->get_result();
$hasilIPK = $resultIPK->fetch_assoc();
$totalMutu = $hasilIPK['total_mutu'];
$totalSks = $hasilIPK['total_sks'];

$ipk = ($totalSks > 0) ? $totalMutu / $totalSks : 0.00;
if (empty($ipk)) {
    $ipkFormatted = 'IPK akan terisi setelah melengkapi data akademik';
} else {
    $ipkFormatted = number_format($ipk, 2);
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
        <h1 class="h3 mb-0">Data Mahasiswa</h1>
    </div>
    

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card-header bg-primary text-white text-center">
                    <h5 class="card-title mb-0">Informasi Mahasiswa</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped w-100">
                            <tbody>
                                <tr>
                                    <th style="width: 30%;">NIM</th>
                                    <td style="width: 70%;"><?= htmlspecialchars($mahasiswa['nim']) ?></td>
                                </tr>
                                <tr>
                                    <th>Nama</th>
                                    <td><?= htmlspecialchars($mahasiswa['nama']) ?></td>
                                </tr>
                                <tr>
                                    <th>Jenis Kelamin</th>
                                    <td>
                                        <?php
                                        if ($mahasiswa['jenis_kelamin'] === 'L') {
                                            echo 'Laki-laki';
                                        } elseif ($mahasiswa['jenis_kelamin'] === 'P') {
                                            echo 'Perempuan';
                                        } else {
                                            echo 'Belum diisi';
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Kota Kelahiran</th>
                                    <td><?= htmlspecialchars($mahasiswa['kota_kelahiran']) ? htmlspecialchars($mahasiswa['kota_kelahiran']) : 'Belum diisi' ?></td>
                                </tr>
                                <tr>
                                    <th>Tanggal Kelahiran</th>
                                    <td><?= htmlspecialchars($mahasiswa['tanggal_kelahiran']) ? htmlspecialchars($mahasiswa['tanggal_kelahiran']) : 'Belum diisi' ?></td>
                                </tr>
                                <tr>
                                    <th>Alamat</th>
                                    <td><?= htmlspecialchars($mahasiswa['alamat']) ? htmlspecialchars($mahasiswa['alamat']) : 'Belum diisi' ?></td>
                                </tr>
                                <tr>
                                    <th>Program Studi</th>
                                    <td><?= ($mahasiswa['program_studi'] === 'Teknik Informatika') ? htmlspecialchars($mahasiswa['program_studi']) : 'Teknik Informatika' ?></td>
                                </tr>
                                <tr>
                                    <th>Semester</th>
                                    <td><?= htmlspecialchars($mahasiswa['semester'])? htmlspecialchars($mahasiswa['semester']) : 'Belum diisi' ?></td>
                                </tr>
                                <tr>
                                    <th>IPK</th>
                                    <td><?= $ipkFormatted ?></td>
                                </tr>
                                <tr>
                                    <th>Gmail</th>
                                    <td><?= htmlspecialchars($mahasiswa['gmail']) ? htmlspecialchars($mahasiswa['gmail']) : 'Belum diisi' ?></td>
                                </tr>
                                <tr>
                                    <th>No Handphone</th>
                                    <td><?= htmlspecialchars($mahasiswa['no_handphone']) ? htmlspecialchars($mahasiswa['no_handphone']) : 'Belum diisi' ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Tombol untuk membuka modal -->
                    <div class="mt-4 text-center">
                        <button class="btn btn-warning btn-lg" data-toggle="modal" data-target="#ubahDataModal">
                            Ubah Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Ubah Data -->
<div class="modal fade" id="ubahDataModal" tabindex="-1" role="dialog" aria-labelledby="ubahDataModalLabel" aria-hidden="true">

    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ubahDataModalLabel">Ubah Data Mahasiswa</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Form untuk Ubah Data Mahasiswa -->
                <form method="POST" action="data_mahasiswa.php">
                    <div class="form-group">
                        <label for="nim">NIM</label>
                        <input type="text" class="form-control" id="nim" name="nim" value="<?= htmlspecialchars($mahasiswa['nim']) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($mahasiswa['nama']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="jenis_kelamin">Jenis Kelamin</label>
                        <select id="jenis_kelamin" name="jenis_kelamin" class="form-control" required>
                            <option value="" selected>Pilih Jenis Kelamin</option>
                            <option value="L" <?= ($mahasiswa['jenis_kelamin'] === 'L') ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= ($mahasiswa['jenis_kelamin'] === 'P') ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="kota_kelahiran">Kota Kelahiran</label>
                        <input type="text" class="form-control" id="kota_kelahiran" name="kota_kelahiran" value="<?= htmlspecialchars($mahasiswa['kota_kelahiran']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="tanggal_kelahiran">Tanggal Kelahiran</label>
                        <input type="date" class="form-control" id="tanggal_kelahiran" name="tanggal_kelahiran" value="<?= htmlspecialchars($mahasiswa['tanggal_kelahiran']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="alamat">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" required><?= htmlspecialchars($mahasiswa['alamat']) ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="program_studi">Program Studi</label>
                        <input type="text" class="form-control" id="program_studi" name="program_studi" value="<?= htmlspecialchars($program_studi) ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label for="semester">Semester</label>
                        <select class="form-control" id="semester" name="semester" required>
                            <option value="" disabled selected>Pilih Semester</option>
                            <?php
                            for ($i = 3; $i <= 6; $i++) {
                                $selected = ($mahasiswa['semester'] == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>Semester $i</option>";
                            }
                            ?>
                        </select>
                    </div>

                   <div class="form-group">
                        <label for="ipk">IPK</label>
                        <input type="number" class="form-control" id="ipk" name="ipk" value="<?= $ipkFormatted ?>" step="0.01" min="0" max="4" readonly placeholder="IPK akan terisi setelah melengkapi data akademik">
                    </div>
                    <div class="form-group">
                        <label for="gmail">Gmail</label>
                        <input type="email" class="form-control" id="gmail" name="gmail" value="<?= htmlspecialchars($mahasiswa['gmail']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="no_handphone">No Handphone</label>
                        <input type="text" class="form-control" id="no_handphone" name="no_handphone" value="<?= htmlspecialchars($mahasiswa['no_handphone']) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </form>
            </div>
        </div>
    </div>
</div>


<?php
require_once '../layout/_bottom.php';
?>