<?php

session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek apakah mahasiswa sudah login
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'mahasiswa') {
    $_SESSION['message'] = 'Anda tidak memiliki akses ke halaman ini!';
    $_SESSION['message_type'] = 'error';

    // Redirect ke login.php
    header('Location: ../login.php');
    exit();
}

// Ambil data mahasiswa dari sesi login
$nim = $_SESSION['login']['nim'];
$mahasiswa = mysqli_query($connection, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
$dataMahasiswa = mysqli_fetch_assoc($mahasiswa);

$namaMahasiswa = $dataMahasiswa['nama'];
$semester = $dataMahasiswa['semester'];

// Cek apakah mahasiswa sudah mengisi tes wawancara
$queryWawancara = mysqli_query($connection, "SELECT * FROM tes_wawancara WHERE nim = '$nim'");
$dataWawancara = mysqli_fetch_assoc($queryWawancara);

if (!$dataWawancara) {
    $_SESSION['message'] = "Anda harus menyelesaikan tes wawancara terlebih dahulu!";
    $_SESSION['message_type'] = "warning";
    header("Location: tes_wawancara.php");
    exit;
}


// Cek apakah mahasiswa sudah mengisi tes kompetensi
$queryKompetensi = mysqli_query($connection, "SELECT * FROM tes_kompetensi WHERE nim = '$nim'");
$dataKompetensi = mysqli_fetch_assoc($queryKompetensi);

// Proses penyimpanan nilai tes kompetensi jika form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$dataKompetensi) {
    $pemahaman_materi = mysqli_real_escape_string($connection, $_POST['pemahaman_materi']);
    $kemampuan_mengajar = mysqli_real_escape_string($connection, $_POST['kemampuan_mengajar']);
    $kemampuan_analisis = mysqli_real_escape_string($connection, $_POST['kemampuan_analisis']);
    $kreativitas = mysqli_real_escape_string($connection, $_POST['kreativitas']);

    // Validasi input
    if (empty($pemahaman_materi) || empty($kemampuan_mengajar) || empty($kemampuan_analisis) || empty($kreativitas)) {
        $_SESSION['message'] = "Semua kolom wajib diisi!";
        $_SESSION['message_type'] = "warning";
        header("Location: tes_kompetensi.php"); // Ganti dengan halaman yang sesuai
        exit;
    }
     else {
        // Perbaikan Query Insert
        $query = "
            INSERT INTO tes_kompetensi (nim, nama_mahasiswa, semester, pemahaman_materi, kemampuan_mengajar, kemampuan_analisis, kreativitas)
            VALUES ('$nim', '$namaMahasiswa', '$semester', '$pemahaman_materi', '$kemampuan_mengajar', '$kemampuan_analisis', '$kreativitas')
        ";

        if (mysqli_query($connection, $query)) {
            $_SESSION['message'] = "Tes kompetensi berhasil disimpan!";
            $_SESSION['message_type'] = "success";
            header("Location: tes_kompetensi.php");
            exit;
        } else {
            $_SESSION['message'] = "Gagal menyimpan tes kompetensi. Error: " . mysqli_error($connection);
            $_SESSION['message_type'] = "error";
            header("Location: tes_kompetensi.php");
            exit;
        }
        
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
            <h2 class="mb-0">Pendaftaran Asisten Dosen | Tes Kompetensi</h2>
        </div>
        <div class="card-body">
            <!-- Form untuk tes kompetensi jika belum ada data -->
            <?php if (!$dataKompetensi): ?>
                <form method="POST">
                    <!-- Nama Mahasiswa -->
                    <div class="form-group mb-3">
                        <label><b>Nama Mahasiswa</b></label>
                        <input type="text" class="form-control" value="<?= $dataMahasiswa['nama'] ?>" readonly>
                    </div>

                    <!-- NIM -->
                    <div class="form-group mb-3">
                        <label><b>NIM</b></label>
                        <input type="text" class="form-control" value="<?= $dataMahasiswa['nim'] ?>" readonly>
                    </div>

                    <!-- Semester -->
                    <div class="form-group mb-3">
                        <label><b>Semester</b></label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($dataMahasiswa['semester']) ?>" readonly>
                    </div>

                  

                    <!-- Pemahaman Materi -->
                    <div class="form-group mb-3">
                        <label><b>Pemahaman Materi</b></label>
                        <select name="pemahaman_materi" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <!-- Kemampuan Mengajar -->
                    <div class="form-group mb-3">
                        <label><b>Kemampuan Mengajar</b></label>
                        <select name="kemampuan_mengajar" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <!-- Kemampuan Analisis -->
                    <div class="form-group mb-3">
                        <label><b>Kemampuan Analisis</b></label>
                        <select name="kemampuan_analisis" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <!-- Kreativitas -->
                    <div class="form-group mb-3">
                        <label><b>Kreativitas</b></label>
                        <select name="kreativitas" class="form-control" required>
                            <option value="">Pilih...</option>
                            <option value="Sangat Baik">Sangat Baik</option>
                            <option value="Baik">Baik</option>
                            <option value="Cukup Baik">Cukup Baik</option>
                            <option value="Kurang">Kurang</option>
                        </select>
                    </div>

                    <div class="text-center">
                        <button type="submit" name="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-save"></i> Simpan Tes Kompetensi
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <!-- Menampilkan hasil tes kompetensi -->
                <div class="card shadow-lg mt-4">
                    <div class="card-header bg-light text-dark">
                        <h4>Hasil Tes Kompetensi</h4>
                    </div>

                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr>
                                <td><b>Pemahaman Materi</b></td>
                                <td><?= $dataKompetensi['pemahaman_materi'] ?></td>
                            </tr>
                            <tr>
                                <td><b>Kemampuan Mengajar</b></td>
                                <td><?= $dataKompetensi['kemampuan_mengajar'] ?></td>
                            </tr>
                            <tr>
                                <td><b>Kemampuan Analisis</b></td>
                                <td><?= $dataKompetensi['kemampuan_analisis'] ?></td>
                            </tr>
                            <tr>
                                <td><b>Kreativitas</b></td>
                                <td><?= $dataKompetensi['kreativitas'] ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <div class="card-footer text-center">
                    <a href="../dashboard/mahasiswa.php" class="btn btn-primary">Kembali ke Dashboard Mahasiswa</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../layout/_bottom.php'; ?>