<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../helper/connection.php';

// Validasi akses hanya untuk koordinator
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'koordinator') {
    $_SESSION['message'] = "Anda tidak memiliki akses ke halaman ini!";
    $_SESSION['message_type'] = "error";
    header("Location: ../login.php");
    exit;
}


// Ambil daftar matakuliah berdasarkan dosen (AJAX Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_matakuliah']) && isset($_POST['nama_dosen'])) {
    $nama_dosen = htmlspecialchars_decode(trim($_POST['nama_dosen']), ENT_QUOTES);

    $query = "SELECT DISTINCT nama_matkul FROM pengajaran WHERE nama_dosen = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $nama_dosen);
    $stmt->execute();
    $result = $stmt->get_result();

    $matkul_list = [];
    while ($row = $result->fetch_assoc()) {
        $matkul_list[] = $row['nama_matkul'];
    }

    echo json_encode($matkul_list);
    exit;
}

// Ambil daftar dosen dari tabel pendaftaran_asisten
$dosen_list = [];

$query_dosen = "SELECT DISTINCT nama_dosen FROM pendaftaran_asisten";
$result_dosen = $connection->query($query_dosen);
if ($result_dosen) {
    while ($row = $result_dosen->fetch_assoc()) {
        $dosen_list[] = htmlspecialchars($row['nama_dosen']);
    }
} else {
    $_SESSION['message'] = "Gagal mengambil daftar dosen!";
    $_SESSION['message_type'] = "error";
    header("Location: perhitungan_pm.php"); // Ganti dengan halaman yang sesuai
    exit;
}


// Inisialisasi variabel untuk daftar mahasiswa
$mahasiswa_list = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_dosen']) && isset($_POST['nama_matkul'])) {
    $nama_dosen = trim($_POST['nama_dosen']);
    $nama_matkul = trim($_POST['nama_matkul']);

    if (!empty($nama_dosen) && !empty($nama_matkul)) {
        $query = "SELECT nama_mahasiswa, semester FROM pendaftaran_asisten WHERE nama_dosen = ? AND nama_matkul = ?";
        $stmt = $connection->prepare($query);
        if ($stmt) {
            $stmt->bind_param('ss', $nama_dosen, $nama_matkul);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $mahasiswa_list[] = [
                    'nama_mahasiswa' => htmlspecialchars($row['nama_mahasiswa']),
                    'semester' => htmlspecialchars($row['semester'])
                ];
            }
        } else {
            $_SESSION['message'] = "Gagal menyiapkan statement!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Nama dosen dan matakuliah tidak boleh kosong!";
        $_SESSION['message_type'] = "error";
    }
}

// Ambil nama dosen dari session atau input form
$nama_dosen = $_POST['nama_dosen'] ?? ''; // Sesuai dengan pilihan form

// Ambil data pengalaman dari tabel pendaftaran_asisten berdasarkan nama_dosen
$query_pengalaman = "SELECT nim, nama_mahasiswa, semester, pengalaman 
                     FROM pendaftaran_asisten 
                     WHERE nama_dosen = ?";
$stmt_pengalaman = $connection->prepare($query_pengalaman);
$stmt_pengalaman->bind_param("s", $nama_dosen);
$stmt_pengalaman->execute();
$result_pengalaman = $stmt_pengalaman->get_result();

// Ambil data wawancara berdasarkan nim yang sudah difilter dari pendaftaran_asisten
$query_wawancara = "SELECT tw.nim, tw.nama_mahasiswa, tw.semester, tw.penguasaan_materi, tw.komunikasi, tw.kepercayaan_diri, tw.attitude 
                    FROM tes_wawancara tw
                    INNER JOIN pendaftaran_asisten pa ON tw.nim = pa.nim
                    WHERE pa.nama_dosen = ?";
$stmt_wawancara = $connection->prepare($query_wawancara);
$stmt_wawancara->bind_param("s", $nama_dosen);
$stmt_wawancara->execute();
$result_wawancara = $stmt_wawancara->get_result();

// Ambil data kompetensi berdasarkan nim yang sudah difilter dari pendaftaran_asisten
$query_kompetensi = "SELECT tk.nim, tk.nama_mahasiswa, tk.semester, tk.pemahaman_materi, tk.kemampuan_mengajar, tk.kemampuan_analisis, tk.kreativitas
                     FROM tes_kompetensi tk
                     INNER JOIN pendaftaran_asisten pa ON tk.nim = pa.nim
                     WHERE pa.nama_dosen = ?";
$stmt_kompetensi = $connection->prepare($query_kompetensi);
$stmt_kompetensi->bind_param("s", $nama_dosen);
$stmt_kompetensi->execute();
$result_kompetensi = $stmt_kompetensi->get_result();




// Ambil data profil ideal jika sudah ada
$query = "SELECT * FROM pm_ideal LIMIT 1";
$result = $connection->query($query);
$profil_ideal = $result->fetch_assoc();

// Jika form disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_profil_ideal'])) {
    // Ambil nilai dari form
    $pengalaman = $_POST['pengalaman'];
    $penguasaan_materi = $_POST['penguasaan_materi'];
    $komunikasi = $_POST['komunikasi'];
    $kepercayaan_diri = $_POST['kepercayaan_diri'];
    $attitude = $_POST['attitude'];
    $pemahaman_materi = $_POST['pemahaman_materi'];
    $kemampuan_mengajar = $_POST['kemampuan_mengajar'];
    $kemampuan_analisis = $_POST['kemampuan_analisis'];
    $kreativitas = $_POST['kreativitas'];

    // Cek apakah data sudah ada
    if ($profil_ideal) {
        // Jika ada, update data
        $sql = "UPDATE pm_ideal SET 
                pengalaman = '$pengalaman', 
                penguasaan_materi = '$penguasaan_materi', 
                komunikasi = '$komunikasi', 
                kepercayaan_diri = '$kepercayaan_diri', 
                attitude = '$attitude', 
                pemahaman_materi = '$pemahaman_materi', 
                kemampuan_mengajar = '$kemampuan_mengajar', 
                kemampuan_analisis = '$kemampuan_analisis', 
                kreativitas = '$kreativitas'";
    } else {
        // Jika belum ada, insert data baru
        $sql = "INSERT INTO pm_ideal (pengalaman, penguasaan_materi, komunikasi, kepercayaan_diri, attitude, pemahaman_materi, kemampuan_mengajar, kemampuan_analisis, kreativitas) 
                VALUES ('$pengalaman', '$penguasaan_materi', '$komunikasi', '$kepercayaan_diri', '$attitude', '$pemahaman_materi', '$kemampuan_mengajar', '$kemampuan_analisis', '$kreativitas')";
    }

    if ($connection->query($sql) === TRUE) {
        $_SESSION['message'] = "Profil Ideal berhasil disimpan!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menyimpan Profil Ideal!";
        $_SESSION['message_type'] = "error";
    }
    header("Location: perhitungan_pm.php");
    exit;
}


// Jika tombol simpan diklik
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_skala_gap'])) {
    // Ambil nilai dari form
    $gap = $_POST['gap'];
    $bobot = $_POST['bobot'];
    $keterangan = $_POST['keterangan'];

    // Cek apakah data dengan GAP yang sama sudah ada
    $check_query = "SELECT * FROM pm_skala_gap WHERE gap = '$gap'";
    $result = $connection->query($check_query);

    if ($result->num_rows > 0) {
        // Jika data sudah ada, update
        $sql = "UPDATE pm_skala_gap SET bobot = '$bobot', keterangan = '$keterangan' WHERE gap = '$gap'";
    } else {
        // Jika belum ada, insert data baru
        $sql = "INSERT INTO pm_skala_gap (gap, bobot, keterangan) VALUES ('$gap', '$bobot', '$keterangan')";
    }

    // Eksekusi query
    if ($connection->query($sql) === TRUE) {
        $_SESSION['message'] = "Skala GAP berhasil disimpan!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Gagal menyimpan Skala GAP: " . $connection->error;
        $_SESSION['message_type'] = "error";
    }
    
    // Redirect setelah menyimpan agar tidak terjadi form resubmission
    header("Location: perhitungan_pm.php"); // Ganti dengan halaman yang sesuai
    exit;
    
}

// Ambil bobot yang sudah ada (jika ada)
$cf = "";
$sf = "";
$result = $connection->query("SELECT * FROM profile_matching LIMIT 1");

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $cf = $row['cf'];
    $sf = $row['sf'];
}

// Jika tombol diklik
if (isset($_POST['bobotcfsf'])) {
    $cf_input = $_POST['cf'];
    $sf_input = $_POST['sf'];

    // Cek apakah sudah ada data di tabel
    $check = $connection->query("SELECT * FROM profile_matching LIMIT 1");

    if ($check->num_rows > 0) {
        // Jika sudah ada, lakukan UPDATE
        $update_sql = "UPDATE profile_matching SET cf='$cf_input', sf='$sf_input' WHERE id=1";
        if ($connection->query($update_sql) === TRUE) {
            $_SESSION['message'] = "Bobot berhasil diperbarui!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal memperbarui bobot: " . $connection->error;
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect setelah menyimpan agar tidak terjadi form resubmission
        header("Location: perhitungan_pm.php");
        exit;
        
    } else {
        // Jika belum ada, lakukan INSERT
        $insert_sql = "INSERT INTO profile_matching (cf, sf) VALUES ('$cf_input', '$sf_input')";
        if ($connection->query($insert_sql) === TRUE) {
            $_SESSION['message'] = "Bobot berhasil disimpan!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menyimpan bobot: " . $connection->error;
            $_SESSION['message_type'] = "error";
        }
        
        // Redirect untuk mencegah form resubmission
        header("Location: perhitungan_pm.php");
        exit;
        
    }
}


$bobot_query = $connection->query("SELECT cf, sf FROM profile_matching LIMIT 1");

if ($bobot_query->num_rows > 0) {
    $bobot = $bobot_query->fetch_assoc();
    $bobot_cf = $bobot['cf'] / 100;
    $bobot_sf = $bobot['sf'] / 100;
} else {
    // Jika tidak ada data, set nilai default agar tidak error
    $bobot_cf = 0;  
    $bobot_sf = 0;
    echo "Data profile_matching tidak ditemukan!";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_kandidat_terbaik'])) {

    $data_rata = $_POST['data_rata'] ?? [];

    if (!empty($data_rata)) {
        foreach ($data_rata as $json_data) {
            $rata = json_decode($json_data, true);

            $nama_mahasiswa = $rata['nama'];
            $total_score = $rata['total_score'];
            $peringkat = $rata['peringkat'];

            // Ambil data dari pendaftaran_asisten berdasarkan nama_mahasiswa
            $query = "SELECT nim, semester, nama_matkul, nama_dosen FROM pendaftaran_asisten WHERE nama_mahasiswa = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param("s", $nama_mahasiswa);
            $stmt->execute();
            $result = $stmt->get_result();
            $data_pendaftaran = $result->fetch_assoc();

            if ($data_pendaftaran) {
                $nim = $data_pendaftaran['nim'];
                $semester = $data_pendaftaran['semester'];
                $nama_matkul = $data_pendaftaran['nama_matkul'];
                $nama_dosen = $data_pendaftaran['nama_dosen'];
                $created_at = date("Y-m-d H:i:s");

                // Cek apakah data dengan nim sudah ada di hasil_pm
                $check_query = "SELECT * FROM hasil_pm WHERE nim = ? AND semester = ?";
                $stmt_check = $connection->prepare($check_query);
                $stmt_check->bind_param("ss", $nim, $semester);
                $stmt_check->execute();
                $result_check = $stmt_check->get_result();

                if ($result_check->num_rows > 0) {
                // Jika sudah ada, update total_score dan peringkat saja
                $update_query = "UPDATE hasil_pm SET total_score = ?, peringkat = ?, created_at = ? WHERE nim = ? AND semester = ?";
                $stmt_update = $connection->prepare($update_query);
                $stmt_update->bind_param("sdsss", $total_score, $peringkat, $created_at, $nim, $semester);
                if ($stmt_update->execute()) {
                    $_SESSION['message'] = "Data berhasil diperbarui di hasil_pm!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Gagal memperbarui data: " . $stmt_update->error;
                    $_SESSION['message_type'] = "error";
                }
            } else {
                // Jika belum ada, insert data baru
                $insert_query = "INSERT INTO hasil_pm (nim, semester, nama_mahasiswa, nama_dosen, nama_matkul, total_score, peringkat, created_at) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_insert = $connection->prepare($insert_query);
                $stmt_insert->bind_param("sssssdss", $nim, $semester, $nama_mahasiswa, $nama_dosen, $nama_matkul, $total_score, $peringkat, $created_at);
                
                if ($stmt_insert->execute()) {
                    $_SESSION['message'] = "Data berhasil disimpan ke hasil_pm!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Gagal menyimpan data: " . $stmt_insert->error;
                    $_SESSION['message_type'] = "error";
                }
            }
        }
    }
} else {
    $_SESSION['message'] = "Tidak ada data yang disimpan!";
    $_SESSION['message_type'] = "error";
}

// Redirect untuk mencegah resubmission form
header("Location: perhitungan_pm.php");
exit;
}


require_once '../layout/_top.php';
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
        <h1 class="h3 mb-0">Perhitungan Profile Matching</h1>
    </div>

    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card-body">

                    <style>
                        @import url('https://fonts.googleapis.com/css?family=Source+Sans+Pro');

                        body {
                            font-family: 'Source Sans Pro', sans-serif;
                            background: #ffffff;
                            color: #414141;
                        }

                        .select-box {
                            cursor: pointer;
                            position: relative;
                            max-width: 100%;
                            width: 100%;
                            margin: 10px 0;
                        }

                        .select,
                        .label {
                            color: #414141;
                            display: block;
                            font-size: 16px;
                        }

                        .select {
                            width: 100%;
                            position: absolute;
                            top: 0;
                            padding: 8px 10px;
                            height: 40px;
                            opacity: 0;
                            background: none transparent;
                            border: 0 none;
                            cursor: pointer;
                        }

                        .select-box1 {
                            background: #f8f9fa;
                            padding: 10px;
                            border-radius: 8px;
                            box-shadow: 0px 3px 10px rgba(0, 0, 0, 0.1);
                            position: relative;
                        }

                        .label {
                            position: relative;
                            padding: 10px 15px;
                            cursor: pointer;
                        }

                        .open .label::after {
                            content: "▲";
                        }

                        .label::after {
                            content: "▼";
                            font-size: 12px;
                            position: absolute;
                            right: 15px;
                            top: 50%;
                            transform: translateY(-50%);
                        }

                        .select-box:hover {
                            background: #e2e6ea;
                            transition: 0.3s;
                        }
                    </style>
                    <!-- Pilih Dosen & Matakuliah -->
                    <form method="POST" class="mb-4">
                        <div class="row g-3">
                            <!-- Dropdown Dosen -->
                            <div class="col-md-6">
                                <div class="select-box">
                                    <label for="nama_dosen" class="label select-box1">
                                        <span class="label-desc">Pilih Dosen</span>
                                    </label>
                                    <select name="nama_dosen" id="nama_dosen" class="select" required>
                                        <option value="" disabled selected>-- Pilih Dosen --</option>
                                        <?php foreach ($dosen_list as $dosen): ?>
                                            <option value="<?= htmlspecialchars_decode($dosen, ENT_QUOTES) ?>"><?= htmlspecialchars_decode($dosen, ENT_QUOTES) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- Dropdown Matakuliah -->
                            <div class="col-md-6">
                                <div class="select-box">
                                    <label for="nama_matkul" class="label select-box1">
                                        <span class="label-desc">Pilih Matakuliah</span>
                                    </label>
                                    <select name="nama_matkul" id="nama_matkul" class="select" required>
                                        <option value="" disabled selected>-- Pilih Matakuliah --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Tampilkan -->
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Tampilkan Mahasiswa
                            </button>
                        </div>
                    </form>

                    <!-- Tabel Mahasiswa -->
                    <div id="mahasiswaContainer" class="mt-4">
                        <?php if (!empty($mahasiswa_list)): ?>
                            <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
                                Daftar Mahasiswa Yang Mendaftar Pada Dosen <?= html_entity_decode(htmlspecialchars($nama_dosen)) ?>
                            </h5>
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mahasiswa_list as $index =>  $mahasiswa): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($mahasiswa['nama_mahasiswa']) ?></td>
                                            <td><?= htmlspecialchars($mahasiswa['semester']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <!-- Tombol Tampilkan Kriteria -->
                            <div class="mt-4">
                                <button id="showKriteria" class="btn btn-secondary w-100">Tampilkan Kriteria dan Sub-Kriteria</button>
                            </div>
                        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <div class="alert alert-warning">Tidak ada mahasiswa ditemukan.</div>
                        <?php endif; ?>
                    </div>


                    <!-- Menentukan Kriteria dan Sub-Kriteria -->
                    <div id="kriteriaContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Menentukan Kriteria dan Sub-Kriteria</h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Kriteria</th>
                                    <th>Sub-Kriteria</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td rowspan="1">Core Factor</td>
                                    <td>Pengalaman</td>
                                </tr>
                                <tr>
                                    <td rowspan="3">Secondary Factor</td>
                                    <td>Kompetensi: Pemahaman Materi, Kemampuan mengajar, Kemampuan Analisis, Kreativitas</td>
                                </tr>
                                <tr>
                                    <td>Wawancara: Penguasaan Materi, Komunikasi, Attitude</td>
                                </tr>
                            </tbody>
                        </table>
                        <!-- Tombol Tampilkan Bobot Kriteria -->
                        <div class="mt-4">
                            <button id="showcfsf" class="btn btn-secondary w-100">Data CF & SF</button>
                        </div>
                    </div>

                    <!-- Menentukan Kriteria dan Faktor Penilaian (Core Factor & Secondary Factor) -->
                    <div id="cfsfContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center p-3 bg-light text-primary" style="border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
                            Menentukan Kriteria dan Faktor Penilaian (Core Factor & Secondary Factor) & Data Kandidat
                        </h5>

                        <!-- Table Keterangan Faktor Penilaian -->
                        <div class="mt-4">
                            <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
                                Skala Penilaian
                            </h5>
                            <table class="table table-bordered table-striped text-center">
                                <thead class="table-primary">
                                    <tr>
                                        <th rowspan="2">Kriteria</th>
                                        <th colspan="2">Kategori</th>
                                    </tr>
                                    <tr>
                                        <th>Keterangan</th>
                                        <th>Skor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Pengalaman -->
                                    <tr>
                                        <td rowspan="4">Pengalaman (CF)</td>
                                        <td>Sangat Berpengalaman</td>
                                        <td>4</td>
                                    </tr>
                                    <tr>
                                        <td>Berpengalaman</td>
                                        <td>3</td>
                                    </tr>
                                    <tr>
                                        <td>Kurang Berpengalaman</td>
                                        <td>2</td>
                                    </tr>
                                    <tr>
                                        <td>Tidak Berpengalaman</td>
                                        <td>1</td>
                                    </tr>

                                    <!-- Wawancara & Kompetensi -->
                                    <tr>
                                        <td rowspan="4">Wawancara & Kompetensi (SF)</td>
                                        <td>Sangat Baik</td>
                                        <td>4</td>
                                    </tr>
                                    <tr>
                                        <td>Baik</td>
                                        <td>3</td>
                                    </tr>
                                    <tr>
                                        <td>Cukup Baik</td>
                                        <td>2</td>
                                    </tr>
                                    <tr>
                                        <td>Kurang</td>
                                        <td>1</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <!-- Table Menentukan Kriteria dan Faktor Penilaian (Core Factor & Secondary Factor)  -->
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th rowspan="2">Nama Mahasiswa</th>
                                    <th rowspan="2">Pengalaman (CF)</th>
                                    <th colspan="4" class="text-center">Kompetensi (SF)</th>
                                    <th colspan="4" class="text-center">Wawancara (SF)</th>
                                </tr>
                                <tr>
                                    <th>Pemahaman Materi</th>
                                    <th>Kemampuan Mengajar</th>
                                    <th>Kemampuan Analisis</th>
                                    <th>Kreativitas</th>
                                    <th>Penguasaan Materi</th>
                                    <th>Komunikasi</th>
                                    <th>Kepercayaan Diri</th>
                                    <th>Attitude</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                while ($row = $result_pengalaman->fetch_assoc()) {
                                    $nim = $row['nim'];

                                    // Ambil data kompetensi
                                    $query_kompetensi_mahasiswa = "SELECT * FROM tes_kompetensi WHERE nim='$nim'";
                                    $result_km = $connection->query($query_kompetensi_mahasiswa);
                                    $kompetensi = $result_km->fetch_assoc();

                                    // Ambil data wawancara
                                    $query_wawancara_mahasiswa = "SELECT * FROM tes_wawancara WHERE nim='$nim'";
                                    $result_wm = $connection->query($query_wawancara_mahasiswa);
                                    $wawancara = $result_wm->fetch_assoc();
                                ?>
                                    <tr>
                                        <td><?php echo $row['nama_mahasiswa']; ?></td>
                                        <td><?php echo $row['pengalaman']; ?></td>
                                        <td><?php echo $kompetensi['pemahaman_materi'] ?? '-'; ?></td>
                                        <td><?php echo $kompetensi['kemampuan_mengajar'] ?? '-'; ?></td>
                                        <td><?php echo $kompetensi['kemampuan_analisis'] ?? '-'; ?></td>
                                        <td><?php echo $kompetensi['kreativitas'] ?? '-'; ?></td>
                                        <td><?php echo $wawancara['penguasaan_materi'] ?? '-'; ?></td>
                                        <td><?php echo $wawancara['komunikasi'] ?? '-'; ?></td>
                                        <td><?php echo $wawancara['kepercayaan_diri'] ?? '-'; ?></td>
                                        <td><?php echo $wawancara['attitude'] ?? '-'; ?></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>

                        <!-- Table Data Kandidat -->
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Nama</th>
                                    <th rowspan="2" style="text-align: center; vertical-align: middle;">Pengalaman <br> (Core Factor)</th>
                                    <th colspan="4" style="text-align: center;">Wawancara (Secondary Factor)</th>
                                    <th colspan="4" style="text-align: center;">Kompetensi (Secondary Factor)</th>
                                </tr>
                                <tr>
                                    <th style="text-align: center;">Penguasaan Materi</th>
                                    <th style="text-align: center;">Komunikasi</th>
                                    <th style="text-align: center;">Kepercayaan Diri</th>
                                    <th style="text-align: center;">Attitude</th>
                                    <th style="text-align: center;">Pemahaman Materi</th>
                                    <th style="text-align: center;">Kemampuan Mengajar</th>
                                    <th style="text-align: center;">Kemampuan Analisis</th>
                                    <th style="text-align: center;">Kreativitas</th>
                                </tr>
                            </thead>
                            <?php
                            // Ambil nama dosen dari session atau input form
                            $nama_dosen = $_POST['nama_dosen'] ?? ''; // Sesuai dengan pilihan form

                            // Query untuk mengambil data kandidat berdasarkan nama_dosen
                            $sql = "SELECT 
                                    pa.nim, 
                                    pa.nama_mahasiswa, 
                                    pa.pengalaman, 
                                    tw.penguasaan_materi, tw.komunikasi, tw.kepercayaan_diri, tw.attitude, 
                                    tk.pemahaman_materi, tk.kemampuan_mengajar, tk.kemampuan_analisis, tk.kreativitas
                                FROM pendaftaran_asisten pa
                                LEFT JOIN tes_wawancara tw ON pa.nim = tw.nim
                                LEFT JOIN tes_kompetensi tk ON pa.nim = tk.nim
                                WHERE pa.nama_dosen = ?";

                            $stmt = $connection->prepare($sql);
                            $stmt->bind_param("s", $nama_dosen);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            ?>

                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<tr>";
                                        echo "<td>" . $row['nama_mahasiswa'] . "</td>";

                                        // Konversi Pengalaman ke Skala (Core Factor)
                                        $pengalaman_map = [
                                            'Sangat Berpengalaman' => 4,
                                            'Berpengalaman' => 3,
                                            'Kurang Berpengalaman' => 2,
                                            'Tidak Berpengalaman' => 1
                                        ];
                                        $pengalaman = $pengalaman_map[$row['pengalaman']] ?? 0;
                                        echo "<td style='text-align: center;'>$pengalaman</td>";

                                        // Konversi Wawancara dan Kompetensi ke Skala (Secondary Factor)
                                        $skala_map = [
                                            'Sangat Baik' => 4,
                                            'Baik' => 3,
                                            'Cukup Baik' => 2,
                                            'Kurang' => 1
                                        ];

                                        // Wawancara
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['penguasaan_materi']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['komunikasi']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['kepercayaan_diri']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['attitude']] ?? 0) . "</td>";

                                        // Kompetensi
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['pemahaman_materi']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['kemampuan_mengajar']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['kemampuan_analisis']] ?? 0) . "</td>";
                                        echo "<td style='text-align: center;'>" . ($skala_map[$row['kreativitas']] ?? 0) . "</td>";

                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='10' style='text-align: center;'>Belum ada data kandidat</td></tr>";
                                }
                                ?>
                            </tbody>

                        </table>

                        <div class="mt-4">
                            <button id="showpisp" class="btn btn-secondary w-100">Data CF & SF</button>
                        </div>

                    </div>

                    <!-- Menentukan Profil Ideal dan Skala Penilaian -->
                    <div id="pispContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
                            Menentukan Profil Ideal dan Skala Penilaian
                        </h5>

                        <form method="POST">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Kriteria</th>
                                        <th>Sub-Kriteria</th>
                                        <th>Profil Ideal (1-4)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td rowspan="1">Pengalaman</td>
                                        <td>Sangat Berpengalaman | Berpengalaman | Kurang Berpengalaman | Tidak Berpengalaman</td>
                                        <td><input type="number" name="pengalaman" min="1" max="4" class="form-control" value="<?= $profil_ideal['pengalaman'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td rowspan="4">Wawancara</td>
                                        <td>Penguasaan Materi</td>
                                        <td><input type="number" name="penguasaan_materi" min="1" max="4" class="form-control" value="<?= $profil_ideal['penguasaan_materi'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Komunikasi</td>
                                        <td><input type="number" name="komunikasi" min="1" max="4" class="form-control" value="<?= $profil_ideal['komunikasi'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Kepercayaan Diri</td>
                                        <td><input type="number" name="kepercayaan_diri" min="1" max="4" class="form-control" value="<?= $profil_ideal['kepercayaan_diri'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Attitude</td>
                                        <td><input type="number" name="attitude" min="1" max="4" class="form-control" value="<?= $profil_ideal['attitude'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td rowspan="4">Kompetensi</td>
                                        <td>Pemahaman Materi</td>
                                        <td><input type="number" name="pemahaman_materi" min="1" max="4" class="form-control" value="<?= $profil_ideal['pemahaman_materi'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Kemampuan Mengajar</td>
                                        <td><input type="number" name="kemampuan_mengajar" min="1" max="4" class="form-control" value="<?= $profil_ideal['kemampuan_mengajar'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Kemampuan Analisis</td>
                                        <td><input type="number" name="kemampuan_analisis" min="1" max="4" class="form-control" value="<?= $profil_ideal['kemampuan_analisis'] ?? '' ?>" required></td>
                                    </tr>
                                    <tr>
                                        <td>Kreativitas</td>
                                        <td><input type="number" name="kreativitas" min="1" max="4" class="form-control" value="<?= $profil_ideal['kreativitas'] ?? '' ?>" required></td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="text-center">
                                <button type="submit" name="simpan_profil_ideal" class="btn btn-primary">Simpan Profil Ideal</button>
                            </div>
                        </form>
                        <!-- Menentukan Skala Penilaian GAP -->
                        <div class="mt-4">
                            <button id="showdataselisih" class="btn btn-secondary w-100">Data Hitung Selisi</button>
                        </div>
                    </div>

                    <!-- Menghitung Nilai GAP (Selisih antara nilai kandidat dan profil ideal) -->
                    <div id="hitungselisiContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Menghitung Nilai GAP (Selisih antara nilai kandidat dan profil ideal)
                        </h5>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary text-center align-middle">
                                    <tr>
                                        <th rowspan="5">Nama</th>
                                        <th colspan="3">Pengalaman (Core Factor)</th>
                                        <th colspan="25">Wawancara & Kompetensi (Secondary Factor)</th>
                                    </tr>
                                    <tr>
                                        <th>Nilai Pengalaman</th>
                                        <th>Profil Ideal</th>
                                        <th>GAP</th>

                                        <?php
                                        $subkriteria = [
                                            'Penguasaan Materi',
                                            'Komunikasi',
                                            'Kepercayaan Diri',
                                            'Attitude',
                                            'Pemahaman Materi',
                                            'Kemampuan Mengajar',
                                            'Kemampuan Analisis',
                                            'Kreativitas'
                                        ];
                                        foreach ($subkriteria as $kriteria) {
                                            echo "<th>$kriteria</th><th>Profil Ideal</th><th>GAP</th>";
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody class="text-center align-middle">
                                    <?php
                                    $nama_dosen = $_POST['nama_dosen'] ?? ''; // Sesuai dengan pilihan form

                                    // Query untuk mengambil data kandidat berdasarkan nama_dosen
                                    $sql = "SELECT 
                                            pa.nim, 
                                            pa.nama_mahasiswa, 
                                            pa.pengalaman, 
                                            tw.penguasaan_materi, tw.komunikasi, tw.kepercayaan_diri, tw.attitude, 
                                            tk.pemahaman_materi, tk.kemampuan_mengajar, tk.kemampuan_analisis, tk.kreativitas
                                        FROM pendaftaran_asisten pa
                                        LEFT JOIN tes_wawancara tw ON pa.nim = tw.nim
                                        LEFT JOIN tes_kompetensi tk ON pa.nim = tk.nim
                                        WHERE pa.nama_dosen = ?";

                                    $stmt = $connection->prepare($sql);
                                    $stmt->bind_param("s", $nama_dosen);
                                    $stmt->execute();
                                    $result = $stmt->get_result(); // 

                                    // Ambil data profile matching ideal
                                    $result_pm = $connection->query("SELECT * FROM pm_ideal LIMIT 1");
                                    $pm_ideal = $result_pm->fetch_assoc();

                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold'>" . $row['nama_mahasiswa'] . "</td>";

                                            // Core Factor: Pengalaman
                                            $pengalaman_map = ['Sangat Berpengalaman' => 4, 'Berpengalaman' => 3, 'Kurang Berpengalaman' => 2, 'Tidak Berpengalaman' => 1];
                                            $pengalaman = $pengalaman_map[$row['pengalaman']] ?? 0;
                                            $gap_pengalaman = $pengalaman - $pm_ideal['pengalaman'];

                                            echo "<td>$pengalaman</td><td>" . $pm_ideal['pengalaman'] . "</td><td class='fw-bold text-danger'>$gap_pengalaman</td>";

                                            // Secondary Factor: Wawancara & Kompetensi
                                            $skala_map = ['Sangat Baik' => 4, 'Baik' => 3, 'Cukup Baik' => 2, 'Kurang' => 1];
                                            $subkriteria_keys = ['penguasaan_materi', 'komunikasi', 'kepercayaan_diri', 'attitude', 'pemahaman_materi', 'kemampuan_mengajar', 'kemampuan_analisis', 'kreativitas'];

                                            foreach ($subkriteria_keys as $kriteria) {
                                                $nilai_kandidat = $skala_map[$row[$kriteria]] ?? 0;
                                                $nilai_pm = $pm_ideal[$kriteria] ?? 0;
                                                $gap = $nilai_kandidat - $nilai_pm;

                                                echo "<td>$nilai_kandidat</td><td>$nilai_pm</td><td class='fw-bold text-danger'>$gap</td>";
                                            }

                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='28' class='text-center'>Belum ada data kandidat</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <button id="showdatatabelkonversi" class="btn btn-secondary w-100">GAP ke Bobot Nilai</button>
                        </div>
                    </div>

                    <!-- Mengubah GAP ke Bobot Nilai -->
                    <div id="konversiGapContainer" class="mt-4" style="display: none;">
                        <div class="mt-4">
                            <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                                Tabel Konversi GAP ke Bobot
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped text-center">
                                    <thead class="table-primary">
                                        <tr>
                                            <th>GAP</th>
                                            <th>Bobot</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>0</td>
                                            <td>5.00</td>
                                        </tr>
                                        <tr>
                                            <td>1</td>
                                            <td>4.50</td>
                                        </tr>
                                        <tr>
                                            <td>-1</td>
                                            <td>4.00</td>
                                        </tr>
                                        <tr>
                                            <td>2</td>
                                            <td>3.50</td>
                                        </tr>
                                        <tr>
                                            <td>-2</td>
                                            <td>3.00</td>
                                        </tr>
                                        <tr>
                                            <td>3</td>
                                            <td>2.50</td>
                                        </tr>
                                        <tr>
                                            <td>-3</td>
                                            <td>2.00</td>
                                        </tr>
                                        <tr>
                                            <td>4</td>
                                            <td>1.50</td>
                                        </tr>
                                        <tr>
                                            <td>-4</td>
                                            <td>1.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!--Mengubah GAP ke Bobot Nilai-->
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Mengubah GAP ke Bobot Nilai
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary text-center align-middle">
                                    <tr>
                                        <th rowspan="5">Nama</th>
                                        <th colspan="2">Pengalaman (Core Factor)</th>
                                        <th colspan="25">Wawancara & Kompetensi (Secondary Factor)</th>
                                    </tr>
                                    <tr>
                                        <th>GAP</th>
                                        <th>Bobot</th>

                                        <?php
                                        $subkriteria = [
                                            'Penguasaan Materi',
                                            'Komunikasi',
                                            'Kepercayaan Diri',
                                            'Attitude',
                                            'Pemahaman Materi',
                                            'Kemampuan Mengajar',
                                            'Kemampuan Analisis',
                                            'Kreativitas'
                                        ];
                                        foreach ($subkriteria as $kriteria) {
                                            echo "<th>GAP $kriteria</th><th>Bobot $kriteria</th>";
                                        }
                                        ?>
                                    </tr>
                                </thead>
                                <tbody class="text-center align-middle">
                                    <?php
                                    $data_rata = []; // Menyimpan hasil perhitungan rata-rata

                                    function konversiGap($gap)
                                    {
                                        $konversi = [
                                            0 => 5.00,
                                            1 => 4.50,
                                            -1 => 4.00,
                                            2 => 3.50,
                                            -2 => 3.00,
                                            3 => 2.50,
                                            -3 => 2.00,
                                            4 => 1.50,
                                            -4 => 1.00
                                        ];
                                        return $konversi[$gap] ?? 1.00; // Default jika GAP tidak ditemukan
                                    }

                                    // Persiapkan query menggunakan prepared statement
                                    $stmt = $connection->prepare($sql);
                                    $stmt->bind_param("s", $nama_dosen);
                                    $stmt->execute();
                                    $result = $stmt->get_result();

                                    // Cek apakah query berhasil dieksekusi
                                    if (!$result) {
                                        die("Query error: " . $stmt->error); // Menampilkan error SQL jika terjadi kesalahan
                                    }

                                    // Cek apakah ada data yang ditemukan
                                    if ($result->num_rows > 0) {
                                        while ($row = $result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td class='fw-bold'>" . htmlspecialchars($row['nama_mahasiswa']) . "</td>";

                                            // Mapping teks pengalaman ke angka
                                            $skala_pengalaman = [
                                                "Sangat Berpengalaman" => 4,
                                                "Berpengalaman" => 3,
                                                "Kurang Berpengalaman" => 2,
                                                "Tidak Berpengalaman" => 1
                                            ];

                                            // Konversi nilai pengalaman mahasiswa ke angka
                                            $pengalaman_mahasiswa = isset($skala_pengalaman[$row['pengalaman']]) ? $skala_pengalaman[$row['pengalaman']] : 0;
                                            $pengalaman_ideal = (int)$pm_ideal['pengalaman']; // Pastikan profil ideal sudah angka

                                            // Hitung GAP
                                            $gap_pengalaman = $pengalaman_mahasiswa - $pengalaman_ideal;

                                            // Konversi GAP ke bobot nilai
                                            $bobot_pengalaman = konversiGap($gap_pengalaman);

                                            echo "<td>$gap_pengalaman</td><td class='fw-bold text-danger'>" . number_format($bobot_pengalaman, 2) . "</td>";



                                            // Secondary Factor: Wawancara & Kompetensi
                                            $total_bobot_sf = 0;
                                            $detail_sf = [];
                                            $jumlah_sf = count($subkriteria);

                                            foreach ($subkriteria_keys as $kriteria) {
                                                $gap = $skala_map[$row[$kriteria]] - $pm_ideal[$kriteria];
                                                $bobot = konversiGap($gap);
                                                $total_bobot_sf += $bobot; // Menjumlahkan bobot SF
                                                $detail_sf[] = "$bobot (GAP : $gap)"; // Simpan detail perhitungan
                                                echo "<td>$gap</td><td class='fw-bold text-danger'>" . number_format($bobot, 2) . "</td>";
                                            }

                                            // Menghitung rata-rata CF dan SF
                                            $rata_cf = $bobot_pengalaman; // CF hanya pengalaman
                                            $detail_cf = "$bobot_pengalaman (GAP : $gap_pengalaman)"; // Detail CF
                                            $rata_sf = $jumlah_sf > 0 ? $total_bobot_sf / $jumlah_sf : 0; // Rata-rata SF
                                            $detail_sf_str = implode(' &nbsp; | &nbsp; ', $detail_sf); // Gabungkan detail SF

                                            // Simpan ke array untuk tabel kedua
                                            $data_rata[] = [
                                                'nama' => $row['nama_mahasiswa'],
                                                'rata_cf' => number_format($rata_cf, 2),
                                                'detail_cf' => $detail_cf,
                                                'rata_sf' => number_format($rata_sf, 2),
                                                'detail_sf' => $detail_sf_str,
                                            ];

                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='28' class='text-center'>Belum ada data kandidat</td></tr>";
                                    }

                                    ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            <button id="showdatahitungcfsf" class="btn btn-secondary w-100">Hitung Rata - Rata CF & SF</button>
                        </div>
                    </div>

                    <!-- Menghitung Rata Rata CF dan SF  -->
                    <div id="hitungcfsfContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Menghitung Rata-rata Core Factor (CF) dan Secondary Factor (SF)
                        </h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary text-center">
                                <tr>
                                    <th rowspan="2">Nama</th>
                                    <th colspan="2">Rata-rata CF</th>
                                    <th colspan="2">Rata-rata SF</th>
                                </tr>
                                <tr>
                                    <th>Nilai</th>
                                    <th>Detail Perhitungan</th>
                                    <th>Nilai</th>
                                    <th>Detail Perhitungan</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php
                                if (!empty($data_rata)) {
                                    foreach ($data_rata as $rata) {
                                        echo "<tr>";
                                        echo "<td class='fw-bold'>" . $rata['nama'] . "</td>";

                                        // Menampilkan nilai rata-rata CF dan detail perhitungannya
                                        echo "<td class='fw-bold'>" . $rata['rata_cf'] . "</td>";
                                        echo "<td class='text-start'>" . $rata['detail_cf'] . "</td>";

                                        // Menampilkan nilai rata-rata SF dan detail perhitungannya
                                        echo "<td class='fw-bold'>" . $rata['rata_sf'] . "</td>";
                                        echo "<td class='text-start'>" . $rata['detail_sf'] . "</td>";

                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada data kandidat</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="mt-4">
                            <button id="showmenyimpanbobotcfsf" class="btn btn-secondary w-100">Menyimpan Bobot CF dan SF </button>
                        </div>

                    </div>


                    <!-- Menyimpan Bobot CF dan SF -->
                    <div id="menyimpanbobotcfsf" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Menyimpan Bobot CF & SF
                        </h5>
                        <!-- Form Input Bobot CF & SF -->
                        <form method="POST" action="">
                            <table class="table table-bordered mt-3 text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Faktor</th>
                                        <th>Kriteria yang Termasuk</th>
                                        <th>Bobot (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Core Factor (CF)</strong></td>
                                        <td>Pengalaman</td>
                                        <td>
                                            <input type="number" step="0.01" name="cf" class="form-control text-center" required value="<?= $cf ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>Secondary Factor (SF)</strong></td>
                                        <td>Kompetensi & Wawancara</td>
                                        <td>
                                            <input type="number" step="0.01" name="sf" class="form-control text-center" required value="<?= $sf ?>">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="submit" name="bobotcfsf" class="btn btn-primary">Simpan / Update Bobot</button> <br>
                        </form>
                        <div class="mt-4">
                            <button id="showtotalscore" class="btn btn-secondary w-100">Total Score</button>
                        </div>
                    </div>


                    <!-- Menampilkan Total Score -->
                    <div id="totalscoreContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Menghitung Nilai Akhir (Total Score)
                        </h5>
                        <table class="table table-bordered mt-3">
                            <thead class="table-primary">
                                <tr>
                                    <th>Nama Mahasiswa</th>
                                    <th>Rata-rata CF</th>
                                    <th>Rata-rata SF</th>
                                    <th>Perhitungan</th>
                                    <th>Total Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($data_rata)) {
                                    foreach ($data_rata as $rata) {
                                        // Perhitungan hasil CF dan SF setelah dikalikan bobot
                                        $hasil_cf = $rata['rata_cf'] * $bobot_cf;
                                        $hasil_sf = $rata['rata_sf'] * $bobot_sf;

                                        // Simpan total_score ke dalam array
                                        $rata['total_score'] = $hasil_cf + $hasil_sf;

                                        // Perhitungan total score
                                        $total_score = $hasil_cf + $hasil_sf;

                                        echo "<tr>";
                                        echo "<td class='fw-bold'>" . $rata['nama'] . "</td>";

                                        // Menampilkan format "Rata-rata CF × Bobot"
                                        echo "<td>" . number_format($rata['rata_cf'], 2) . " × " . number_format($bobot_cf, 2) . " = <span class='fw-bold text-success'>" . number_format($hasil_cf, 2) . "</span></td>";

                                        // Menampilkan format "Rata-rata SF × Bobot"
                                        echo "<td>" . number_format($rata['rata_sf'], 2) . " × " . number_format($bobot_sf, 2) . " = <span class='fw-bold text-success'>" . number_format($hasil_sf, 2) . "</span></td>";

                                        // Menampilkan hasil penjumlahan CF dan SF
                                        echo "<td class='fw-bold'>" . number_format($hasil_cf, 2) . " + " . number_format($hasil_sf, 2) . "</td>";

                                        // Menampilkan total score
                                        echo "<td class='fw-bold text-primary'>" . number_format($total_score, 2) . "</td>";

                                        echo "</tr>";
                                    }
                                    unset($rata);
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>Belum ada data kandidat</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                        <div class="mt-4">
                            <button id="showkandidatterbaik" class="btn btn-secondary w-100">Kandidat Terbaik</button>
                        </div>
                    </div>

                    <!-- Menampilkan Kandidat Terbaik -->
                    <div id="kandidatterbaikContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Mengurutkan dan Menentukan Kandidat Terbaik
                        </h5>
                        <form method="post" action="perhitungan_pm.php">
                            <table class="table table-bordered mt-3">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Peringkat</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Total Score</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($data_rata)) {
                                        foreach ($data_rata as &$rata) {
                                            $hasil_cf = $rata['rata_cf'] * $bobot_cf;
                                            $hasil_sf = $rata['rata_sf'] * $bobot_sf;
                                            $rata['total_score'] = $hasil_cf + $hasil_sf;
                                        }
                                        unset($rata);

                                        usort($data_rata, function ($a, $b) {
                                            return $b['total_score'] <=> $a['total_score'];
                                        });

                                        $peringkat = 1;
                                        foreach ($data_rata as $rata) {
                                            echo "<tr" . ($peringkat == 1 ? " class='table-success'" : "") . ">";
                                            echo "<td class='fw-bold text-center'>" . $peringkat . "</td>";
                                            echo "<td class='fw-bold'>" . $rata['nama'] . "</td>";
                                            echo "<td class='fw-bold text-primary'>" . number_format($rata['total_score'], 2) . "</td>";
                                            echo "</tr>";

                                            // Simpan data dalam input hidden untuk dikirim ke server
                                            echo "<input type='hidden' name='data_rata[]' value='" . json_encode([
                                                'nama' => $rata['nama'],
                                                'total_score' => $rata['total_score'],
                                                'peringkat' => $peringkat
                                            ]) . "'>";
                                            $peringkat++;
                                        }
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center text-danger'>Belum ada data kandidat</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <button type="submit" name="simpan_kandidat_terbaik" class="btn btn-primary">Simpan Hasil</button>
                        </form>

                    </div>





                </div>
            </div>
        </div>
    </div>
</section>


<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Ketika dropdown nama_dosen berubah
        $("#nama_dosen").on("change", function() {
            var namaDosen = $(this).val();
            var matkulSelect = $("#nama_matkul");

            matkulSelect.html('<option value="">-- Memuat data... --</option>');

            $.ajax({
                url: window.location.href,
                type: "POST",
                data: {
                    get_matakuliah: 1,
                    nama_dosen: namaDosen
                },
                dataType: "json",
                success: function(data) {
                    matkulSelect.html('<option value="">-- Pilih Matakuliah --</option>');
                    data.forEach(matkul => {
                        matkulSelect.append(`<option value="${matkul}">${matkul}</option>`);
                    });
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                }
            });
        });

        // Tampilkan pilihan terpilih di label
        $("select").on("change", function() {
            var selection = $(this).find("option:selected").text();
            var labelFor = $(this).attr("id");
            var label = $("[for='" + labelFor + "']");
            label.find(".label-desc").html(selection);
        });

        // Toggle animasi dropdown saat diklik
        $("select").on("click", function() {
            $(this).parent(".select-box").toggleClass("open");
        });

        // Tutup dropdown jika klik di luar area
        $(document).mouseup(function(e) {
            var container = $(".select-box");
            if (!container.is(e.target) && container.has(e.target).length === 0) {
                container.removeClass("open");
            }
        });

        $("#showKriteria").on("click", function() {
            $("#kriteriaContainer").toggle();
        });

        // Menentukan Kriteria dan Faktor Penilaian (Core Factor & Secondary Factor)
        $("#showcfsf").on("click", function() {
            $("#cfsfContainer").toggle();
        });

        // Menentukan Profil Ideal dan Skala Penilaian
        $("#showpisp").on("click", function() {
            $("#pispContainer").toggle();
        });

        // Menentukan Data Kandidat
        $("#showdatakandidat").on("click", function() {
            $("#datakandidatContainer").toggle();
        });

        $("#showdatakandidat").on("click", function() {
            $("#datakandidatContainer").toggle();
        });

        $("#showdataselisih").on("click", function() {
            $("#hitungselisiContainer").toggle();
        });

        $("#showdatatabelkonversi").on("click", function() {
            $("#konversiGapContainer").toggle();
        });

        $("#showdatahitungcfsf").on("click", function() {
            $("#hitungcfsfContainer").toggle();
        });

        $("#showmenyimpanbobotcfsf").on("click", function() {
            $("#menyimpanbobotcfsf").toggle();
        });

        $("#showtotalscore").on("click", function() {
            $("#totalscoreContainer").toggle();
        });

        $("#showkandidatterbaik").on("click", function() {
            $("#kandidatterbaikContainer").toggle();
        });

    });
</script>
<?php require_once '../layout/_bottom.php'; ?>