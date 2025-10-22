<?php
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

    // Redirect agar pesan bisa ditampilkan di halaman tujuan
    header("Location: perhitungan_topsis.php"); // Ganti dengan halaman yang sesuai
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

            // Redirect agar pesan bisa ditampilkan di halaman tujuan
            header("Location: perhitungan_topsis.php"); // Ganti dengan halaman yang sesuai
            exit;
        }
    } else {
        $_SESSION['message'] = "Nama dosen dan matakuliah tidak boleh kosong!";
        $_SESSION['message_type'] = "error";

        // Redirect agar pesan bisa ditampilkan di halaman tujuan
        header("Location: perhitungan_topsis.php"); // Ganti dengan halaman yang sesuai
        exit;
    }
}

// Ambil daftar mahasiswa berdasarkan dosen dan mata kuliah yang dipilih
$alternatif_list = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_dosen']) && isset($_POST['nama_matkul'])) {
    $nama_dosen = $_POST['nama_dosen'] ?? '';
    $nama_matkul = $_POST['nama_matkul'] ?? '';


    // Query untuk mengambil mahasiswa berdasarkan dosen & mata kuliah yang dipilih
    $query_alternatif = "SELECT DISTINCT nama_mahasiswa FROM pendaftaran_asisten WHERE nama_dosen = ? AND nama_matkul = ?";
    $stmt = $connection->prepare($query_alternatif);

    if ($stmt) {
        $stmt->bind_param('ss', $nama_dosen, $nama_matkul);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $alternatif_list[] = htmlspecialchars($row['nama_mahasiswa']);
        }
    } else {
        $_SESSION['message'] = "Gagal menyiapkan query daftar mahasiswa!";
        $_SESSION['message_type'] = "error";

        // Redirect agar pesan bisa ditampilkan di halaman tujuan
        header("Location: perhitungan_topsis.php"); // Ganti dengan halaman yang sesuai
        exit;
    }
}


// Simpan bobot ke dalam tabel topsis 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_bobot'])) {
    $bobot_ipk = $_POST['bobot_ipk'];
    $bobot_nilai_standarisasi = $_POST['bobot_nilai_standarisasi'];

    $query = "INSERT INTO topsis (kriteria, bobot) VALUES 
          ('IPK', ?), 
          ('Nilai Standarisasi', ?)
          ON DUPLICATE KEY UPDATE kriteria=VALUES(kriteria), bobot=VALUES(bobot)";

    $stmt = $connection->prepare($query);
    if ($stmt) {
        $stmt->bind_param('dd', $bobot_ipk, $bobot_nilai_standarisasi);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Bobot berhasil disimpan!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Gagal menyimpan bobot!";
            $_SESSION['message_type'] = "error";
        }
    } else {
        $_SESSION['message'] = "Gagal menyiapkan statement!";
        $_SESSION['message_type'] = "error";
    }

    // Redirect agar pesan bisa ditampilkan di halaman tujuan
    header("Location: perhitungan_topsis.php");
    exit;
}

// Ambil bobot dari tabel topsis
$bobot_ipk = 0;
$bobot_nilai_standarisasi = 0;
$query_bobot = "SELECT kriteria, bobot FROM topsis WHERE kriteria IN ('IPK', 'Nilai Standarisasi')";
$result_bobot = $connection->query($query_bobot);
if ($result_bobot) {
    while ($row = $result_bobot->fetch_assoc()) {
        if ($row['kriteria'] == 'IPK') {
            $bobot_ipk = $row['bobot'];
        } elseif ($row['kriteria'] == 'Nilai Standarisasi') {
            $bobot_nilai_standarisasi = $row['bobot'];
        }
    }
} else {
    $_SESSION['message'] = "Gagal mengambil bobot!";
    $_SESSION['message_type'] = "error";
    header("Location: perhitungan_topsis.php");
    exit;
}



// Table Alternatif 
// Inisialisasi array untuk menyimpan total kuadrat per kriteria
$total_kuadrat = ['ipk' => 0, 'standarisasi' => 0];

// Hitung total kuadrat untuk normalisasi
foreach ($alternatif_list as $alternatif) {
    // Ambil nilai IPK
    $query_ipk = "SELECT ipk FROM pendaftaran_asisten WHERE nama_mahasiswa = ? AND nama_dosen = ? AND nama_matkul = ?";
    $stmt_ipk = $connection->prepare($query_ipk);
    $stmt_ipk->bind_param('sss', $alternatif, $nama_dosen, $nama_matkul);
    $stmt_ipk->execute();
    $result_ipk = $stmt_ipk->get_result();
    $ipk = $result_ipk->fetch_assoc()['ipk'] ?? 0;

    // Ambil kode_matkul
    $query_kode_matkul = "SELECT kode_matkul FROM pendaftaran_asisten WHERE nama_mahasiswa = ? AND nama_matkul = ?";
    $stmt_kode_matkul = $connection->prepare($query_kode_matkul);
    $stmt_kode_matkul->bind_param('ss', $alternatif, $nama_matkul);
    $stmt_kode_matkul->execute();
    $result_kode_matkul = $stmt_kode_matkul->get_result();
    $kode_matkul = $result_kode_matkul->fetch_assoc()['kode_matkul'] ?? '-';

    // Ambil Standarisasi Matakuliah
    $query_pengajaran = "SELECT bobot_standarisasi FROM pengajaran WHERE kode_matkul = ?";
    $stmt_pengajaran = $connection->prepare($query_pengajaran);
    $stmt_pengajaran->bind_param('s', $kode_matkul);
    $stmt_pengajaran->execute();
    $result_pengajaran = $stmt_pengajaran->get_result();
    $bobot_standarisasi = $result_pengajaran->fetch_assoc()['bobot_standarisasi'] ?? 0;

    // Hitung total kuadrat
    $total_kuadrat['ipk'] += pow($ipk, 2);
    $total_kuadrat['standarisasi'] += pow($bobot_standarisasi, 2);
}


// Table Normalisai
// Hitung akar total kuadrat
$akar_total_kuadrat = [
    'ipk' => sqrt($total_kuadrat['ipk']),
    'standarisasi' => sqrt($total_kuadrat['standarisasi'])
];

// Simpan nilai normalisasi
$normalisasi_list = [];
foreach ($alternatif_list as $alternatif) {
    // Ambil kembali nilai IPK dan Standarisasi
    $stmt_ipk->execute();
    $result_ipk = $stmt_ipk->get_result();
    $ipk = $result_ipk->fetch_assoc()['ipk'] ?? 0;

    $stmt_pengajaran->execute();
    $result_pengajaran = $stmt_pengajaran->get_result();
    $bobot_standarisasi = $result_pengajaran->fetch_assoc()['bobot_standarisasi'] ?? 0;

    // Hitung normalisasi
    $normalisasi_ipk = $akar_total_kuadrat['ipk'] ? $ipk / $akar_total_kuadrat['ipk'] : 0;
    $normalisasi_standarisasi = $akar_total_kuadrat['standarisasi'] ? $bobot_standarisasi / $akar_total_kuadrat['standarisasi'] : 0;

    $normalisasi_list[] = [
        'nama' => $alternatif,
        'ipk' => $normalisasi_ipk,
        'standarisasi' => $normalisasi_standarisasi
    ];
}


// Table Normalisasi Terbobot 
// Query untuk mengambil bobot dari tabel topsis
$query_bobot = "SELECT kriteria, bobot FROM topsis";
$result_bobot = $connection->query($query_bobot);

$bobot_kriteria = [];

// Ambil bobot dari database
while ($row = $result_bobot->fetch_assoc()) {
    $bobot_kriteria[strtolower($row['kriteria'])] = $row['bobot'];
}

// Tetapkan nilai default jika bobot belum ada di database
$bobot_default = [
    'ipk' => 50, // Default 50% untuk IPK
    'standarisasi' => 50 // Default 50% untuk Standarisasi
];

// Gunakan bobot dari database jika tersedia, jika tidak gunakan default
$bobot_kriteria = [
    'ipk' => $bobot_kriteria['ipk'] ?? $bobot_default['ipk'],
    'standarisasi' => $bobot_kriteria['standarisasi'] ?? $bobot_default['standarisasi']
];

// List normalisasi terbobot
$normalisasi_terbobot_list = [];

foreach ($normalisasi_list as $data) {
    $normalisasi_terbobot_list[] = [
        'nama' => $data['nama'],
        'ipk' => $data['ipk'] * ($bobot_kriteria['ipk'] / 100),
        'nilai_standarisasi' => $data['standarisasi'] * ($bobot_kriteria['standarisasi'] / 100)
    ];
}



// Table Matriks Ideal 
// Inisialisasi array untuk menyimpan nilai maksimal dan minimal
$solusi_ideal = [
    'positif' => ['ipk' => 0, 'nilai_standarisasi' => 0],
    'negatif' => ['ipk' => PHP_INT_MAX, 'nilai_standarisasi' => PHP_INT_MAX]
];

// Cari nilai maksimum (A⁺) dan minimum (A⁻) untuk setiap kriteria
foreach ($normalisasi_terbobot_list as $data) {
    $solusi_ideal['positif']['ipk'] = max($solusi_ideal['positif']['ipk'], $data['ipk']);
    $solusi_ideal['positif']['nilai_standarisasi'] = max($solusi_ideal['positif']['nilai_standarisasi'], $data['nilai_standarisasi']);

    $solusi_ideal['negatif']['ipk'] = min($solusi_ideal['negatif']['ipk'], $data['ipk']);
    $solusi_ideal['negatif']['nilai_standarisasi'] = min($solusi_ideal['negatif']['nilai_standarisasi'], $data['nilai_standarisasi']);
}

$jarak_solusi = [];
if (!empty($normalisasi_terbobot_list)) {
    foreach ($normalisasi_terbobot_list as $data) {
        // Hitung D+ (Jarak ke solusi ideal positif)
        $d_plus = sqrt(
            pow(($solusi_ideal['positif']['ipk'] - $data['ipk']), 2) +
                pow(($solusi_ideal['positif']['nilai_standarisasi'] - $data['nilai_standarisasi']), 2)
        );

        // Hitung D- (Jarak ke solusi ideal negatif)
        $d_minus = sqrt(
            pow(($solusi_ideal['negatif']['ipk'] - $data['ipk']), 2) +
                pow(($solusi_ideal['negatif']['nilai_standarisasi'] - $data['nilai_standarisasi']), 2)
        );

        // Simpan hasil perhitungan
        $jarak_solusi[] = [
            'nama' => $data['nama'],
            'd_plus' => $d_plus,
            'd_minus' => $d_minus
        ];
    }
}

// Table Nilai Preferensi & CI 
$nilai_preferensi = [];

foreach ($jarak_solusi as $data) {
    $d_plus = $data['d_plus'];
    $d_minus = $data['d_minus'];
    $sum_d = $d_plus + $d_minus;

    // Pastikan sum_d tidak nol
    if ($sum_d > 0) {
        $ci = $d_minus / $sum_d;
    } else {
        $ci = 0; // Jika sum_d = 0, default Ci menjadi 0
    }

    // Simpan hasil perhitungan Ci
    $nilai_preferensi[] = [
        'nama' => $data['nama'],
        'ci' => $ci
    ];
}

// Urutkan berdasarkan nilai Ci dari yang tertinggi ke terendah
usort($nilai_preferensi, function ($a, $b) {
    return $b['ci'] <=> $a['ci'];
});


// Simpan Data ke Database Saat Tombol Diklik
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['simpan_hasil'])) {
    $nama_dosen = $_POST['nama_dosen']; // Pastikan ini terisi
    $nama_matkul = $_POST['nama_matkul'];

    foreach ($nilai_preferensi as $data) {
        $nama_mahasiswa = $data['nama'];
        $nilai_preferensi_value = $data['ci'];
        $tanggal_simpan = date("Y-m-d H:i:s");

        // Debugging: Periksa apakah nilai dosen terisi dengan benar
        echo "Nama Dosen: " . $nama_dosen . "<br>";

        // Cek apakah data sudah ada
        $cek_query = "SELECT COUNT(*) AS total FROM hasil_topsis 
                    WHERE nama_mahasiswa = ? AND nama_dosen = ? AND nama_matkul = ?";
        $stmt_cek = $connection->prepare($cek_query);
        if (!$stmt_cek) {
            die("Gagal prepare cek_query: " . $connection->error);
        }
        $stmt_cek->bind_param("sss", $nama_mahasiswa, $nama_dosen, $nama_matkul);
        $stmt_cek->execute();
        $result_cek = $stmt_cek->get_result();
        $row = $result_cek->fetch_assoc();

        if ($row['total'] == 0) {
            // Simpan ke database
            $query = "INSERT INTO hasil_topsis 
                    (nama_mahasiswa, nilai_preferensi, nama_dosen, nama_matkul, tanggal_simpan) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $connection->prepare($query);
            if (!$stmt) {
                die("Query Prepare Gagal: " . $connection->error);
            }
            $stmt->bind_param("ssdss", $nama_mahasiswa, $nilai_preferensi_value, $nama_dosen, $nama_matkul, $tanggal_simpan);
            $stmt->execute();
        }
    }

    $_SESSION['message'] = "Data berhasil disimpan ke dalam hasil_topsis!";
    $_SESSION['message_type'] = "success";
    header("Location: perhitungan_topsis.php");
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
    <div class="section-header d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Perhitungan TOPSIS</h1>
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
                            <table class="table table-bordered table-striped">
                                <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">
                                    Daftar Mahasiswa Yang Mendaftar Pada Dosen <?= html_entity_decode(htmlspecialchars($nama_dosen)) ?>
                                </h5>
                                <thead class="table-primary">
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Mahasiswa</th>
                                        <th>Semester</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mahasiswa_list as $index => $mahasiswa): ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($mahasiswa['nama_mahasiswa']) ?></td>
                                            <td><?= htmlspecialchars($mahasiswa['semester']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button id="showAlternatif" class="btn btn-secondary mt-3"> Data Alternatif</button>
                        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <div class="alert alert-warning">Tidak ada mahasiswa ditemukan.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Tabel Data Alternatif -->
                    <div id="alternatifContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Data Alternatif</h5>
                        <?php if (!empty($alternatif_list)): ?>
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Mahasiswa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alternatif_list as $index => $alternatif): ?>
                                        <tr>
                                            <td><?= 'A' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($alternatif) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button id="showKriteria" class="btn btn-secondary mt-3">Data Kriteria</button>
                        <?php else: ?>
                            <div class="alert alert-warning">Tidak ada data alternatif ditemukan.</div>
                        <?php endif; ?>
                    </div>

                    <!-- Tabel Data Kriteria -->
                    <div id="kriteriaContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Data Kriteria</h5>
                        <form method="POST" action="">
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Kode</th>
                                        <th>Nama Kriteria</th>
                                        <th>Atribut</th>
                                        <th>Bobot</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>K1</td>
                                        <td>IPK</td>
                                        <td>Benefit</td>
                                        <td><input type="number" name="bobot_ipk" step="0.01" value="<?= $bobot_ipk ?>" required class="form-control rounded-pill shadow-sm text-center"></td>
                                    </tr>
                                    <tr>
                                        <td>K2</td>
                                        <td>Nilai Standarisasi</td>
                                        <td>Benefit</td>
                                        <td><input type="number" name="bobot_nilai_standarisasi" step="0.01" value="<?= $bobot_nilai_standarisasi ?>" required class="form-control rounded-pill shadow-sm text-center"></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="submit" name="simpan_bobot" class="btn btn-primary mt-3">Simpan Bobot</button>
                        </form>
                        <button id="showNilaiAlternatif" class="btn btn-secondary mt-3">Nilai Alternatif</button>
                    </div>

                    <!-- Tabel Nilai Alternatif -->
                    <div id="nilaiAlternatifContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Nilai Alternatif</h5>

                        <?php if (!empty($alternatif_list)): ?>
                            <table class="table table-bordered table-striped">
                                <thead class="table-primary">
                                    <tr>
                                        <th>Kode</th>
                                        <th>K1 (IPK)</th>
                                        <th>K2 (Standarisasi Matakuliah)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alternatif_list as $index => $alternatif): ?>
                                        <?php
                                        // Ambil IPK dari tabel pendaftaran_asisten
                                        $query_ipk = "SELECT ipk FROM pendaftaran_asisten WHERE nama_mahasiswa = ? AND nama_dosen = ? AND nama_matkul = ?";
                                        $stmt_ipk = $connection->prepare($query_ipk);
                                        $stmt_ipk->bind_param('sss', $alternatif, $nama_dosen, $nama_matkul);
                                        $stmt_ipk->execute();
                                        $result_ipk = $stmt_ipk->get_result();
                                        $ipk = $result_ipk->fetch_assoc()['ipk'] ?? '-';

                                        // Ambil kode_matkul dari tabel pendaftaran_asisten
                                        $query_kode_matkul = "SELECT kode_matkul FROM pendaftaran_asisten WHERE nama_mahasiswa = ? AND nama_matkul = ?";
                                        $stmt_kode_matkul = $connection->prepare($query_kode_matkul);
                                        $stmt_kode_matkul->bind_param('ss', $alternatif, $nama_matkul);
                                        $stmt_kode_matkul->execute();
                                        $result_kode_matkul = $stmt_kode_matkul->get_result();
                                        $kode_matkul = $result_kode_matkul->fetch_assoc()['kode_matkul'] ?? '-';

                                        // Ambil Standarisasi Matakuliah dari tabel pengajaran
                                        $query_pengajaran = "SELECT standarisasi_nilai, bobot_standarisasi FROM pengajaran WHERE kode_matkul = ?";
                                        $stmt_pengajaran = $connection->prepare($query_pengajaran);
                                        $stmt_pengajaran->bind_param('s', $kode_matkul);
                                        $stmt_pengajaran->execute();
                                        $result_pengajaran = $stmt_pengajaran->get_result();
                                        $standarisasi_matkul = $result_pengajaran->fetch_assoc();
                                        $bobot_standarisasi = $standarisasi_matkul['bobot_standarisasi'] ?? '-';
                                        $standarisasi_nilai = $standarisasi_matkul['standarisasi_nilai'] ?? '-';
                                        ?>
                                        <tr>
                                            <td><?= 'A' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                            <td><?= htmlspecialchars($ipk) ?></td>
                                            <td><?= htmlspecialchars($bobot_standarisasi) . ' (' . htmlspecialchars($standarisasi_nilai) . ')' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <button id="showNormalisasi" class="btn btn-secondary mt-3">Normalisasi</button>
                        <?php else: ?>
                            <div class="alert alert-warning">Tidak ada data alternatif ditemukan.</div>
                        <?php endif; ?>
                    </div>


                    <!-- Tabel Normalisasi -->
                    <div id="normalisasiContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Normalisasi</h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Kode</th>
                                    <th>K1 (IPK)</th>
                                    <th>K2 (Standarisasi Matakuliah)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($normalisasi_list as $index => $data): ?>
                                    <tr>
                                        <td><?= 'A' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= number_format($data['ipk'], 2) ?></td>
                                        <td><?= number_format($data['standarisasi'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>

                        </table>
                        <button id="showNormalisasiTerbobot" class="btn btn-secondary mt-3">Normalisasi Terbobot</button>
                    </div>

                    <!-- Tabel Normalisasi Terbobot -->
                    <div id="normalisasiTerbobotContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Normalisasi Terbobot</h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-success">
                                <tr>
                                    <th>Kode</th>
                                    <th>K1 (IPK)</th>
                                    <th>K2 (Standarisasi Matakuliah)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($normalisasi_terbobot_list as $index => $data): ?>
                                    <tr>
                                        <td><?= 'A' . str_pad($index + 1, 2, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= number_format($data['ipk'], 2) ?></td>
                                        <td><?= number_format($data['nilai_standarisasi'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button id="showMatriksSolusiIdeal" class="btn btn-secondary mt-3">Matriks Solusi</button>
                    </div>

                    <!-- Tabel Matriks Solusi Ideal -->
                    <div id="solusiIdealContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Matriks Solusi Ideal</h5>
                        <table class="table table-bordered table-striped">
                            <thead class="table-primary">
                                <tr>
                                    <th>Solusi</th>
                                    <th>K1 (IPK)</th>
                                    <th>K2 (Standarisasi Matakuliah)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>A⁺ (Positif)</td>
                                    <td><?= number_format($solusi_ideal['positif']['ipk'], 2) ?></td>
                                    <td><?= number_format($solusi_ideal['positif']['nilai_standarisasi'], 2) ?></td>
                                </tr>
                                <tr>
                                    <td>A⁻ (Negatif)</td>
                                    <td><?= number_format($solusi_ideal['negatif']['ipk'], 2) ?></td>
                                    <td><?= number_format($solusi_ideal['negatif']['nilai_standarisasi'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                        <button id="showNilaiPreferensi" class="btn btn-secondary mt-3">Preferensi Dan Peringkat</button>
                    </div>

                    <!-- Tabel Nilai Preferensi (Ci) & Peringkat Akhir -->
                    <div id="nilaiPreferensiContainer" class="mt-4" style="display: none;">
                        <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Nilai Preferensi (Ci) & Peringkat Akhir</h5>

                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Nilai Preferensi (Ci)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (is_array($nilai_preferensi)) : ?>
                                    <?php $rank = 1; ?>
                                    <?php foreach ($nilai_preferensi as $data) : ?>
                                        <tr>
                                            <td><?= $rank++; ?></td>
                                            <td><?= htmlspecialchars($data['nama']); ?></td>
                                            <td><?= number_format($data['ci'], 4); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">Data tidak ditemukan atau terjadi kesalahan</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        <!-- Tombol Simpan -->
                        <div class="mt-3 text-center">
                            <form method="POST">
                                <input type="hidden" name="nama_dosen" value="<?= isset($_POST['nama_dosen']) ? $_POST['nama_dosen'] : '' ?>">
                                <input type="hidden" name="nama_matkul" value="<?= isset($_POST['nama_matkul']) ? $_POST['nama_matkul'] : '' ?>">
                                <button type="submit" name="simpan_hasil" class="btn btn-success">
                                    <i class="bi bi-save"></i> Simpan Hasil
                                </button>
                            </form>
                        </div>
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

        // Tampilkan Data Alternatif saat tombol diklik
        $("#showAlternatif").on("click", function() {
            $("#alternatifContainer").toggle();
        });

        // Tampilkan Data Kriteria saat tombol diklik
        $("#showKriteria").on("click", function() {
            $("#kriteriaContainer").toggle();
        });

        // Tampilkan Nilai Alternatif saat tombol diklik
        $("#showNilaiAlternatif").on("click", function() {
            $("#nilaiAlternatifContainer").toggle();
        });

        // Tampilkan Normalisasi saat tombol diklik
        $("#showNormalisasi").on("click", function() {
            $("#normalisasiContainer").toggle();
        });

        //  Tampilkan Normalisasi Terbobot saat tombol diklik
        $("#showNormalisasiTerbobot").on("click", function() {
            $("#normalisasiTerbobotContainer").toggle();
        });

        // Tampilkan Matriks Solusi Ideal saat tombol diklik
        $("#showMatriksSolusiIdeal").on("click", function() {
            $("#solusiIdealContainer").toggle();
        });

        // Tampilkan Nilai Preferensi saat tombol diklik
        $("#showNilaiPreferensi").on("click", function() {
            $("#nilaiPreferensiContainer").toggle();
        });
    });
</script>
<?php require_once '../layout/_bottom.php'; ?>