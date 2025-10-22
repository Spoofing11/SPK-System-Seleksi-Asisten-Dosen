<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek akses hanya untuk mahasiswa
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'mahasiswa') {
    $_SESSION['message'] = 'Anda tidak memiliki akses ke halaman ini!';
    $_SESSION['message_type'] = 'error';

    // Redirect ke login.php
    header('Location: ../login.php');
    exit();
}

// Ambil data mahasiswa dari sesi login
$nim = $_SESSION['login']['nim'];
$mahasiswaQuery = "SELECT * FROM mahasiswa WHERE nim = '$nim'";
$mahasiswa = mysqli_query($connection, $mahasiswaQuery);
$dataMahasiswa = mysqli_fetch_assoc($mahasiswa);

// Cek apakah mahasiswa sudah mendaftar
$queryPendaftaran = mysqli_query($connection, "
    SELECT * FROM pendaftaran_asisten WHERE nim = '$nim'
");
$dataPendaftaran = mysqli_fetch_assoc($queryPendaftaran);

// Jika belum mendaftar, redirect ke halaman pendaftaran dengan pesan peringatan
if (!$dataPendaftaran) {
    $_SESSION['message'] = "Anda harus menyelesaikan pendaftaran terlebih dahulu!";
    $_SESSION['message_type'] = "warning";
    header("Location: pendaftaran.php");
    exit;
}


// Cek apakah data wawancara sudah ada
$dataWawancaraQuery = "SELECT nama_mahasiswa, semester, komunikasi, kepercayaan_diri, penguasaan_materi, attitude 
                       FROM tes_wawancara WHERE nim = '$nim'";

$dataWawancara = mysqli_query($connection, $dataWawancaraQuery);
$dataWawancara = mysqli_fetch_assoc($dataWawancara);

// Proses penyimpanan nilai wawancara jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$dataWawancara) {
    $penguasaan_materi = mysqli_real_escape_string($connection, $_POST['penguasaan_materi']);
    $komunikasi = mysqli_real_escape_string($connection, $_POST['komunikasi']);
    $kepercayaan_diri = mysqli_real_escape_string($connection, $_POST['kepercayaan_diri']);
    $attitude = mysqli_real_escape_string($connection, $_POST['attitude']);

    // Validasi input
    if (empty($penguasaan_materi) || empty($komunikasi) || empty($kepercayaan_diri) || empty($attitude)) {
        $_SESSION['message'] = "Semua kolom wajib diisi!";
        $_SESSION['message_type'] = "warning";
        header("Location: tes_wawancara.php");
        exit;
    }
     else {
        $query = "
    INSERT INTO tes_wawancara (
        nim, nama_mahasiswa, semester, komunikasi, kepercayaan_diri, penguasaan_materi, attitude
    ) VALUES (
        '$nim', '{$dataMahasiswa['nama']}', '{$dataMahasiswa['semester']}', '$komunikasi', '$kepercayaan_diri', '$penguasaan_materi', '$attitude'
    )
";


if (mysqli_query($connection, $query)) {
    $_SESSION['message'] = "Tes wawancara berhasil disimpan!";
    $_SESSION['message_type'] = "success";
} else {
    $_SESSION['message'] = "Gagal menyimpan tes wawancara: " . mysqli_error($connection);
    $_SESSION['message_type'] = "error";
}

header("Location: tes_wawancara.php");
exit;

    }
}

?>

<div class="container mt-5">
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

    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Pendaftaran Asisten Dosen | Tes Wawancara</h2>
        </div>
        <div class="card-body">
            <!-- Form untuk mengisi wawancara jika belum ada data wawancara -->
            <?php if (!$dataWawancara): ?>
                <form method="POST">
                    <!-- Nama Mahasiswa -->
                    <div class="form-group mb-3">
                        <label><b>Nama Mahasiswa</b></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($dataMahasiswa['nama']) ?>" readonly>
                    </div>

                    <!-- NIM -->
                    <div class="form-group mb-3">
                        <label><b>NIM</b></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($dataMahasiswa['nim']) ?>" readonly>
                    </div>

                    <!-- Semester -->
                    <div class="form-group mb-3">
                        <label><b>Semester</b></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($dataMahasiswa['semester']) ?>" readonly>
                    </div>

                    <!-- Penguasaan Materi -->
                    <div class="form-group mb-3">
                        <label><b>Penilaian Penguasaan Materi</b></label>
                        <select name="penguasaan_materi" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>

                    </div>

                    <!-- Komunikasi -->
                    <div class="form-group mb-3">
                        <label><b>Penilaian Komunikasi</b></label>
                        <select name="komunikasi" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <!-- Kepercayaan Diri -->
                    <div class="form-group mb-3">
                        <label><b>Penilaian Kepercayaan Diri</b></label>
                        <select name="kepercayaan_diri" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <!-- Attitude  -->
                    <div class="form-group mb-3">
                        <label><b>Attitude (Kedisiplinan & Etika)</b></label>
                        <select name="attitude" class="form-control" required>
                            <option value="">-- Pilih --</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>


                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Simpan Tes Wawancara
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Menampilkan hasil wawancara jika sudah ada data -->
                <div class="card shadow-lg mt-4">
                    <div class="card-header bg-light text-dark">
                        <h4>Hasil Tes Wawancara</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td><b>Penguasaan Materi</b></td>
                                <td><?= htmlspecialchars($dataWawancara['penguasaan_materi']) ?></td>
                            </tr>
                            <tr>
                                <td><b>Komunikasi</b></td>
                                <td><?= htmlspecialchars($dataWawancara['komunikasi']) ?></td>
                            </tr>
                            <tr>
                                <td><b>Kepercayaan Diri</b></td>
                                <td><?= htmlspecialchars($dataWawancara['kepercayaan_diri']) ?></td>
                            </tr>
                            <tr>
                                <td><b>Attitude</b></td>
                                <td><?= htmlspecialchars($dataWawancara['attitude']) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <a href='tes_kompetensi.php' class='btn btn-primary btn-lg'>
                    <i class='fas fa-arrow-right'></i> Lanjut ke Tes Kompetensi
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>


<?php require_once '../layout/_bottom.php'; ?>