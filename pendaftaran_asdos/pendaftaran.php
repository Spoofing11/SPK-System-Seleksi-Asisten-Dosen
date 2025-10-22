<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../lib/fpdf.php';


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
$mahasiswa = mysqli_query($connection, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
$dataMahasiswa = mysqli_fetch_assoc($mahasiswa);

// Cek apakah mahasiswa sudah memiliki nilai
$queryNilai = mysqli_query($connection, "SELECT * FROM nilai WHERE nim = '$nim'");

if (mysqli_num_rows($queryNilai) == 0) {
    $_SESSION['message'] = 'Mohon Menunggu Data Nilai Anda Lengkap. Sambil Menunggu Lengkapi Data Diri Anda!';
    $_SESSION['message_type'] = 'warning';
    header("Location: ../dashboard/mahasiswa.php");
    exit;
}


// Cek data pendaftaran sebelumnya
$queryPendaftaran = mysqli_query($connection, "
    SELECT p.*, m.nama_matkul 
    FROM pendaftaran_asisten p
    JOIN matakuliah m ON p.kode_matkul = m.kode_matkul
    WHERE p.nim = '$nim'");
$dataPendaftaran = mysqli_fetch_assoc($queryPendaftaran);

// Ambil daftar matakuliah sesuai semester mahasiswa
$semester = $dataMahasiswa['semester'];
$matakuliah = mysqli_query($connection, "
    SELECT m.kode_matkul, m.nama_matkul 
    FROM matakuliah m 
    WHERE m.semester = '$semester'
");

// Menangani form submit
if (isset($_POST['submit'])) {
    // Mengambil data dari form
    $pengalaman = $_POST['pengalaman'];
    $ipk = isset($_POST['ipk']) ? $_POST['ipk'] : 0;
    $status = "Pending"; // Status pendaftaran masih menunggu
    $kode_matkul = $_POST['kode_matkul']; // Kode mata kuliah yang dipilih
    $file_nilai = ""; // Default untuk menghindari undefined variable

    // Validasi IPK
    if ($ipk < 3.00) {
        $_SESSION['message'] = 'Maaf, IPK Anda harus minimal 3.00 untuk dapat mendaftar.';
        $_SESSION['message_type'] = 'error';
        header("Location: ../dashboard/mahasiswa.php");
        exit; // Hentikan proses jika IPK di bawah 3.00
    }

    // Update IPK di tabel mahasiswa
    $updateIPKQuery = "UPDATE mahasiswa SET ipk = '$ipk' WHERE nim = '{$dataMahasiswa['nim']}'";
    mysqli_query($connection, $updateIPKQuery);

    // Pastikan kode mata kuliah dipilih
    if (empty($kode_matkul)) {
        $_SESSION['message'] = 'Kode matakuliah tidak boleh kosong!';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }

    // Ambil data dosen dan standarisasi nilai berdasarkan kode mata kuliah
    $getDosenQuery = "SELECT p.nidn, d.nama_dosen, p.standarisasi_nilai, p.bobot_standarisasi 
                      FROM pengajaran p
                      JOIN dosen d ON p.nidn = d.nidn
                      WHERE p.kode_matkul = '$kode_matkul'
                      LIMIT 1";
    $resultDosen = mysqli_query($connection, $getDosenQuery);

    if ($resultDosen && $rowDosen = mysqli_fetch_assoc($resultDosen)) {
        $nama_dosen = mysqli_real_escape_string($connection, $rowDosen['nama_dosen']);
        $nidn = mysqli_real_escape_string($connection, $rowDosen['nidn']);
        $standarisasi_nilai = $rowDosen['standarisasi_nilai'];
        $bobot_standarisasi = (float) $rowDosen['bobot_standarisasi']; // Bobot dalam bentuk angka
    } else {
        $_SESSION['message'] = 'Dosen atau standarisasi nilai untuk mata kuliah ini tidak ditemukan!';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }

    // Ambil nilai mahasiswa untuk mata kuliah yang dipilih
    $getNilaiQuery = "SELECT angka FROM nilai 
                      WHERE nim = '{$dataMahasiswa['nim']}' 
                      AND kode_matkul = '$kode_matkul'
                      LIMIT 1";
    $resultNilai = mysqli_query($connection, $getNilaiQuery);

    if ($resultNilai && $rowNilai = mysqli_fetch_assoc($resultNilai)) {
        $nilai_mahasiswa = (float) $rowNilai['angka']; // Nilai dalam bentuk angka
    } else {
        $_SESSION['message'] = 'Anda belum memiliki nilai untuk mata kuliah ini!';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }

    // ** Validasi nilai mahasiswa harus memenuhi standarisasi **  
    if ($nilai_mahasiswa < $bobot_standarisasi) {
        $_SESSION['message'] = "Nilai Anda Untuk Mata Kuliah ini Tidak Memenuhi Standarisasi!";
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }


    // Validasi kode matkul
    $getMatkulQuery = "SELECT nama_matkul FROM matakuliah WHERE kode_matkul = '$kode_matkul'";
    $resultMatkul = mysqli_query($connection, $getMatkulQuery);

    if ($resultMatkul && $row = mysqli_fetch_assoc($resultMatkul)) {
        $nama_matkul = $row['nama_matkul'];
    } else {
        $_SESSION['message'] = 'Matakuliah tidak ditemukan';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }

    // Validasi pilihan pengalaman
    $valid_pengalaman = ['Sangat Berpengalaman', 'Berpengalaman', 'Kurang Berpengalaman', 'Tidak Berpengalaman'];
    if (!in_array($pengalaman, $valid_pengalaman)) {
        $_SESSION['message'] = 'Pilihan pengalaman tidak valid!';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }

    // Proses upload file nilai akademik
    if (isset($_FILES['file_nilai']) && $_FILES['file_nilai']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['file_nilai'];
        $upload_dir = '../uploads/';
        $file_name = time() . '_' . basename($file['name']);
        $file_path = $upload_dir . $file_name;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_nilai = $file_name;
        } else {
            $_SESSION['message'] = 'Gagal mengunggah file nilai akademik!';
            $_SESSION['message_type'] = 'error';
            header("Location: pendaftaran.php");
            exit;
        }
    }

    // Insert data ke tabel pendaftaran_asisten
    $query = "
    INSERT INTO pendaftaran_asisten (
        nim, nama_mahasiswa, semester, kode_matkul, nama_matkul, pengalaman, ipk, status, file_nilai, nama_dosen, nidn
    ) 
    VALUES (
        '{$dataMahasiswa['nim']}', '{$dataMahasiswa['nama']}', '{$dataMahasiswa['semester']}', '$kode_matkul', '$nama_matkul', '$pengalaman', '$ipk', '$status', '$file_nilai', '$nama_dosen', '$nidn'
    )";

    $insert = mysqli_query($connection, $query);

    if ($insert) {
        $_SESSION['message'] = 'Pendaftaran berhasil! Lanjut ke tahap tes wawancara.';
        $_SESSION['message_type'] = 'success';
        header("Location: tes_wawancara.php");
        exit;
    } else {
        $_SESSION['message'] = 'Pendaftaran gagal! Silakan coba lagi.';
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php");
        exit;
    }
}



// Jika ada data pendaftaran, ambil data matakuliah
if ($dataPendaftaran) {
    $query = "
        SELECT nama_matkul, semester 
        FROM matakuliah 
        WHERE kode_matkul = '{$dataPendaftaran['kode_matkul']}'
    ";
    $result = mysqli_query($connection, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $dataMatkul = mysqli_fetch_assoc($result);
    } else {
        $_SESSION['message'] = "Matakuliah tidak ditemukan untuk kode: {$dataPendaftaran['kode_matkul']}";
        $_SESSION['message_type'] = 'error';
        header("Location: pendaftaran.php"); // Ganti dengan halaman yang sesuai
        exit;
    }
} else {
    $dataMatkul = null;
}
// Query untuk mengambil data mata kuliah
$query = "SELECT * FROM matakuliah ORDER BY semester ASC";
$result = mysqli_query($connection, $query);
$matakuliah = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Menyaring mata kuliah berdasarkan semester
$semesters = [];
foreach ($matakuliah as $row) {
    $semesters[$row['semester']][] = $row; // Kelompokkan mata kuliah berdasarkan semester
}

// Cek jika parameter 'id' ada di URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Query untuk mengambil file PDF dari database
    $query = "SELECT file_nilai FROM pendaftaran_asisten WHERE id = $id";
    $result = mysqli_query($connection, $query);

    // Jika file ditemukan
    if ($result && $row = mysqli_fetch_assoc($result)) {
        // Mengatur header agar browser mengenali file yang dikirim adalah PDF
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="file_nilai.pdf"');
        echo $row['file_nilai']; // Mengirimkan konten file PDF
        exit;
    } else {
        $_SESSION['message'] = "File tidak ditemukan!";
        $_SESSION['message_type'] = "error";
        header("Location: pendaftaran.php"); // Ganti dengan halaman tujuan yang sesuai
        exit;
    }
}
?>
<link rel="stylesheet" href="assets/css/components.css">

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

            .success {
                background-color: #28a745;
                color: white;
            }

            .error {
                background-color: #dc3545;
                color: white;
            }

            .warning {
                background-color: #ffc107;
                color: black;
            }

            .info {
                background-color: #17a2b8;
                color: white;
            }
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

    <?php unset($_SESSION['message'], $_SESSION['message_type']);
    endif; ?>
    <div class="card shadow-lg">
        <div class="card-header bg-primary text-white text-center">
            <h2 class="mb-0">Pendaftaran Asisten Dosen | Administrasi</h2>
        </div>
        <div class="card-body">
            <?php if (!$dataPendaftaran): ?>
                <!-- Formulir Pendaftaran -->
                <form method="POST" id="formPendaftaran" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Kolom Kiri -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label><b>Nama Mahasiswa</b></label>
                                <input type="text" class="form-control" value="<?= $dataMahasiswa['nama'] ?>" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label><b>IPK</b></label>
                                <?php
                                $queryIPK = mysqli_query($connection, "SELECT SUM(n.mutu) AS total_mutu, SUM(m.sks) AS total_sks
FROM nilai n 
JOIN matakuliah m ON n.kode_matkul = m.kode_matkul
WHERE n.nim = '$nim'");

                                $hasilIPK = mysqli_fetch_assoc($queryIPK);
                                $totalMutu = $hasilIPK['total_mutu'];
                                $totalSks = $hasilIPK['total_sks'];

                                $ipk = ($totalSks > 0) ? $totalMutu / $totalSks : 0.00;
                                $ipkFormatted = number_format($ipk, 2);
                                ?>
                                <input type="text" name="ipk" class="form-control" value="<?= $ipkFormatted ?>" readonly>

                            </div>
                            <div class="form-group mb-3">
                                <label><b>NIM</b></label>
                                <input type="text" class="form-control" value="<?= $dataMahasiswa['nim'] ?>" readonly>
                            </div>
                            <div class="form-group mb-3">
                                <label><b>Semester</b></label>
                                <input type="number" class="form-control" value="<?= $dataMahasiswa['semester'] ?>" readonly>
                            </div>

                            <div class="form-group mb-3">
                                <label><b>Nilai Standarisasi</b></label>
                                <input type="number" class="form-control" id="nilai_standarisasi" readonly>
                            </div>

                        </div>


                        <!-- Kolom Kanan -->
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label><b>Pilih Matakuliah Yang Ingin Didaftarkan Sebagai Asisten Dosen</b></label>
                                <select name="kode_matkul" id="kode_matkul" class="form-control" required>
                                    <option value="">-- Pilih Matakuliah --</option>
                                    <?php
                                    $currentSemester = $dataMahasiswa['semester']; // Semester mahasiswa yang login
                                    if (!empty($matakuliah)) {
                                        $semesters = [];
                                        // Kelompokkan mata kuliah berdasarkan semester
                                        foreach ($matakuliah as $row) {
                                            $semesters[$row['semester']][] = $row;
                                        }

                                        // Tampilkan mata kuliah untuk semester mahasiswa yang login
                                        if (isset($semesters[$currentSemester])) {
                                            echo "<optgroup label='Semester $currentSemester (Semester Anda)'>";
                                            foreach ($semesters[$currentSemester] as $matkul) {
                                                echo "<option value='" . $matkul['kode_matkul'] . "' 
                                                    data-nama='" . $matkul['nama_matkul'] . "' 
                                                    data-nilai='" . $matkul['nilai_standarisasi'] . "'>"
                                                    . $matkul['nama_matkul'] .
                                                    "</option>";
                                            }
                                            echo "</optgroup>";
                                        }

                                        // Tampilkan mata kuliah untuk semester-semetar sebelumnya
                                        foreach ($semesters as $semester => $matkuls) {
                                            if ($semester < $currentSemester) { // Hanya semester di bawah semester mahasiswa
                                                echo "<optgroup label='Semester $semester'>";
                                                foreach ($matkuls as $matkul) {
                                                    echo "<option value='" . $matkul['kode_matkul'] . "' 
                                                        data-nama='" . $matkul['nama_matkul'] . "' 
                                                        data-nilai='" . $matkul['nilai_standarisasi'] . "'>"
                                                        . $matkul['nama_matkul'] .
                                                        "</option>";
                                                }
                                                echo "</optgroup>";
                                            }
                                        }
                                    } else {
                                        echo "<option value='' disabled>Tidak ada mata kuliah tersedia</option>";
                                        $_SESSION['message'] = "Tidak ada mata kuliah yang tersedia.";
                                        $_SESSION['message_type'] = "warning";
                                    }

                                    ?>
                                </select>

                            </div>


                            <div class="form-group mb-3">
                                <label><b>Pengalaman</b></label>
                                <select name="pengalaman" class="form-control" required>
                                    <option value="Sangat Berpengalaman">Sangat Berpengalaman</option>
                                    <option value="Berpengalaman">Berpengalaman</option>
                                    <option value="Kurang Berpengalaman">Kurang Berpengalaman</option>
                                    <option value="Tidak Berpengalaman">Tidak Berpengalaman</option>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label><b>Upload Berkas Nilai Akademik</b></label>
                                <input type="file" name="file_nilai" class="form-control" accept=".pdf, .jpg, .jpeg, .png" required>
                                <small class="form-text text-muted">File harus berupa PDF atau gambar (JPG, JPEG, PNG)</small>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="text-center mt-4">
                        <button type="submit" name="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-paper-plane"></i> Daftar Sekarang
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <!-- Hasil Pendaftaran (Ditampilkan setelah berhasil mendaftar) -->
                <div id="hasilPendaftaran" style="display: block;">
                    <h3>Hasil Pendaftaran Asisten Dosen</h3>
                    <table class="table">
                        <tr>
                            <td><b>Nama Mahasiswa</b></td>
                            <td><?= $dataMahasiswa['nama'] ?></td>
                        </tr>
                        <tr>
                            <td><b>Semester Mahasiswa</b></td>
                            <td><?= $dataMahasiswa['semester'] ?></td>
                        </tr>
                        <tr>
                            <td><b>IPK</b></td>
                            <td><?= $dataMahasiswa['ipk'] ?></td>
                        </tr>
                        <tr>
                            <td><b>NIM</b></td>
                            <td><?= $dataMahasiswa['nim'] ?></td>
                        </tr>
                        <tr>
                            <td><b>Matakuliah Yang Didaftarkan</b></td>
                            <td>
                                <?php if (isset($dataMatkul) && $dataMatkul): ?>
                                    <?= htmlspecialchars($dataMatkul['nama_matkul']) ?> (Semester <?= htmlspecialchars($dataMatkul['semester']) ?>)
                                <?php else: ?>
                                    Data matakuliah atau semesternya tidak tersedia.
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><b>Pengalaman</b></td>
                            <td><?= $dataPendaftaran['pengalaman'] ?></td>
                        </tr>

                        <tr>
                            <td><b>Status Pendaftaran</b></td>
                            <td>
                                <?php if ($dataPendaftaran['status'] == 'Diterima'): ?>
                                    <div style="padding: 10px; background-color: #28a745; color: #fff; border-radius: 5px; text-align: center;">
                                        <i class="fas fa-check-circle"></i> <strong>Diterima</strong>
                                    </div>
                                <?php elseif ($dataPendaftaran['status'] == 'Ditolak'): ?>
                                    <div style="padding: 10px; background-color: #dc3545; color: #fff; border-radius: 5px; text-align: center;">
                                        <i class="fas fa-times-circle"></i> <strong>Ditolak</strong>
                                    </div>
                                <?php else: ?>
                                    <div style="padding: 10px; background-color: #6c757d; color: #fff; border-radius: 5px; text-align: center;">
                                        <i class="fas fa-clock"></i> <strong>Menunggu Verifikasi</strong>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <td>
                        <?php if ($dataPendaftaran): ?>
                            <a href="tes_wawancara.php" class="btn btn-primary">Lanjut ke Tahap Tes Wawancara</a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>Lengkapi Pendaftaran Terlebih Dahulu</button>
                        <?php endif; ?>
                    </td>

                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../layout/_bottom.php'; ?>
<script>
    document.getElementById('kode_matkul').addEventListener('change', function() {
        // Ambil nilai standarisasi dari opsi yang dipilih
        var selectedOption = this.options[this.selectedIndex];
        var nilaiStandarisasi = selectedOption.getAttribute('data-nilai');

        // Masukkan nilai ke dalam input nilai_standarisasi
        document.getElementById('nilai_standarisasi').value = nilaiStandarisasi ? nilaiStandarisasi : '';
    });
</script>