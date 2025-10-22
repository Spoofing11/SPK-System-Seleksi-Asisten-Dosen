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
    $nama_dosen = $_POST['nama_dosen'];
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
while ($row = $result_dosen->fetch_assoc()) {
    $dosen_list[] = $row['nama_dosen'];
}

// Inisialisasi variabel untuk daftar mahasiswa
$mahasiswa_list = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_dosen']) && isset($_POST['nama_matkul'])) {
    $nama_dosen = $_POST['nama_dosen'];
    $nama_matkul = $_POST['nama_matkul'];

    $query = "SELECT nama_mahasiswa, semester FROM pendaftaran_asisten WHERE nama_dosen = ? AND nama_matkul = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ss', $nama_dosen, $nama_matkul);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $mahasiswa_list[] = $row;
    }
}

// Ambil data dari hasil_topsis
$query_topsis = "SELECT nama_mahasiswa, nilai_preferensi FROM hasil_topsis";
$result_topsis = $connection->query($query_topsis);
$data_topsis = [];
while ($row = $result_topsis->fetch_assoc()) {
    $data_topsis[$row['nama_mahasiswa']] = $row['nilai_preferensi'];
}

// Ambil data dari hasil_pm
$query_pm = "SELECT nama_mahasiswa, total_score FROM hasil_pm";
$result_pm = $connection->query($query_pm);
$data_pm = [];
$max_total_score = 0; // Untuk normalisasi

while ($row = $result_pm->fetch_assoc()) {
    $data_pm[$row['nama_mahasiswa']] = $row['total_score'];
    if ($row['total_score'] > $max_total_score) {
        $max_total_score = $row['total_score']; // Cari nilai tertinggi untuk normalisasi
    }
}

// Ambil data tambahan dari pendaftaran_asisten
$query_pendaftaran = "SELECT nim, nama_mahasiswa, nama_dosen, nama_matkul FROM pendaftaran_asisten";
$result_pendaftaran = $connection->query($query_pendaftaran);
$data_pendaftaran = [];

while ($row = $result_pendaftaran->fetch_assoc()) {
    $data_pendaftaran[$row['nama_mahasiswa']] = [
        'nim' => $row['nim'],
        'nama_dosen' => $row['nama_dosen'],
        'nama_matkul' => $row['nama_matkul']
    ];
}

// Gabungkan hasil dari kedua metode dengan data tambahan
$data_kombinasi = [];

foreach ($data_topsis as $nama => $nilai_topsis) {
    if (isset($data_pm[$nama])) {
        $total_score = $data_pm[$nama];
        $total_score_normalisasi = ($max_total_score > 0) ? $total_score / $max_total_score : 0;
        $total_gabungan = $nilai_topsis + $total_score_normalisasi;

        // Ambil data tambahan dari pendaftaran_asisten
        $nim = $data_pendaftaran[$nama]['nim'] ?? '-';
        $nama_dosen = $data_pendaftaran[$nama]['nama_dosen'] ?? '-';
        $nama_matkul = $data_pendaftaran[$nama]['nama_matkul'] ?? '-';

        $data_kombinasi[] = [
            'nim' => $nim,
            'nama_mahasiswa' => $nama,
            'nama_dosen' => $nama_dosen,
            'nama_matkul' => $nama_matkul,
            'nilai_preferensi' => $nilai_topsis,
            'total_score' => $total_score,
            'total_score_normalisasi' => $total_score_normalisasi,
            'total_gabungan' => $total_gabungan
        ];
    }
}

// Urutkan berdasarkan total_gabungan (tertinggi ke terendah)
usort($data_kombinasi, function ($a, $b) {
    return $b['total_gabungan'] <=> $a['total_gabungan'];
});


if (isset($_POST['simpan_kombinasi']) && !empty($data_kombinasi)) {
    foreach ($data_kombinasi as $data) {
        $nim = $data['nim'];
        $nama_mahasiswa = $data['nama_mahasiswa'];
        $nama_dosen = $data['nama_dosen'];
        $nama_matkul = $data['nama_matkul'];
        $total_score_normalisasi = $data['total_score_normalisasi'];
        $skor_kombinasi_gabungan = $data['total_gabungan'];

        // Ambil semester dari pendaftaran_asisten berdasarkan NIM
        $query_semester = "SELECT semester FROM pendaftaran_asisten WHERE nim = ?";
        $stmt_semester = $connection->prepare($query_semester);
        $stmt_semester->bind_param("s", $nim);
        $stmt_semester->execute();
        $result_semester = $stmt_semester->get_result();
        $semester = ($row = $result_semester->fetch_assoc()) ? $row['semester'] : null;

        // Cek apakah data sudah ada di hasil_kombinasi
        $query_check = "SELECT * FROM hasil_kombinasi WHERE nim = ?";
        $stmt_check = $connection->prepare($query_check);
        $stmt_check->bind_param("s", $nim);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Jika sudah ada, update data
            $query_update = "UPDATE hasil_kombinasi SET 
                nama_mahasiswa=?, 
                nama_dosen=?, 
                nama_matkul=?, 
                total_score_normalisasi=?, 
                skor_kombinasi_gabungan=?, 
                semester=?, 
                created_at=CURRENT_TIMESTAMP 
                WHERE nim=?";
            $stmt_update = $connection->prepare($query_update);
            $stmt_update->bind_param("sssssis", $nama_mahasiswa, $nama_dosen, $nama_matkul, $total_score_normalisasi, $skor_kombinasi_gabungan, $semester, $nim);
            if ($stmt_update->execute()) {
                $_SESSION['message'] = "Data berhasil diperbarui!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal memperbarui data!";
                $_SESSION['message_type'] = "error";
            }
        } else {
            // Jika belum ada, insert data baru
            $query_insert = "INSERT INTO hasil_kombinasi 
                (nim, nama_mahasiswa, nama_dosen, nama_matkul, total_score_normalisasi, skor_kombinasi_gabungan, semester) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $connection->prepare($query_insert);
            $stmt_insert->bind_param("sssssis", $nim, $nama_mahasiswa, $nama_dosen, $nama_matkul, $total_score_normalisasi, $skor_kombinasi_gabungan, $semester);
            if ($stmt_insert->execute()) {
                $_SESSION['message'] = "Data berhasil disimpan!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Gagal menyimpan data!" . $stmt_insert->error;
                $_SESSION['message_type'] = "error";
            }
        }
    }

    // Redirect untuk memastikan pesan sesi muncul di halaman baru
    header("Location: hasil_perhitungan.php"); // Ganti dengan halaman yang sesuai
    exit();
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
        <h1 class="h3 mb-0">Hasil Kombinasi Perhitungan Metode Topsis & Profile Matching</h1>
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
                                            <option value="<?= htmlspecialchars($dosen) ?>"><?= htmlspecialchars($dosen) ?></option>
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
                                <h5 style="font-weight: bold; color: #0d6efd; text-align: center; padding: 10px; background-color: #f8f9fa; border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Hasil Perhitungan Untuk Dosen <?= htmlspecialchars($nama_dosen) ?></h5>
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
                            <div class="mt-4">
                                <button id="showdatakombinasi" class="btn btn-secondary w-100">Kombinasi Perhitungan</button>
                            </div>
                        <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                            <div class="alert alert-warning">Tidak ada mahasiswa ditemukan.</div>
                        <?php endif; ?>
                    </div>

                    <div id="kombinasiContainer" class="mt-4" style="display: none;">
                        <h5 class="text-center fw-bold text-primary p-3 bg-light rounded shadow-sm">
                            Kombinasi Perhitungan Profile Matching & Topsis
                        </h5>
                        <table class="table table-bordered mt-3">
                            <thead class="table-primary">
                                <tr>
                                    <th>Peringkat</th>
                                    <th>Nama Mahasiswa</th>
                                    <th>Nilai Preferensi (TOPSIS)</th>
                                    <th>Total Score (Profile Matching)</th>
                                    <th>Total Score (Normalisasi)</th>
                                    <th>Total Gabungan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if (!empty($data_kombinasi)) {
                                    $peringkat = 1;
                                    // Ambil 5 kandidat terbaik saja
                                    foreach (array_slice($data_kombinasi, 0, 5) as $row) {
                                        echo "<tr" . ($peringkat == 1 ? " class='table-success'" : "") . ">";
                                        echo "<td class='fw-bold text-center'>" . $peringkat . "</td>";
                                        echo "<td class='fw-bold'>" . $row['nama_mahasiswa'] . "</td>";
                                        echo "<td class='fw-bold text-primary'>" . number_format($row['nilai_preferensi'], 2) . "</td>";
                                        echo "<td class='fw-bold text-primary'>" . number_format($row['total_score'], 2) . "</td>";
                                        echo "<td class='fw-bold text-primary'>" . number_format($row['total_score_normalisasi'], 4) . "</td>";
                                        echo "<td class='fw-bold text-danger'>" . number_format($row['total_gabungan'], 4) . "</td>";
                                        echo "</tr>";
                                        $peringkat++;
                                    }
                                } else {
                                    echo "<tr><td colspan='6' class='text-center text-danger'>Belum ada data kombinasi</td></tr>";
                                }
                                ?>
                            </tbody>

                            <form method="post">
                                <button type="submit" name="simpan_kombinasi" class="btn btn-success">Simpan Hasil Kombinasi</button>
                            </form>
                        </table>
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

        // Kombinasi Perhitungan
        $("#showdatakombinasi").on("click", function() {
            $("#kombinasiContainer").toggle();
        });


    });
</script>

<?php require_once '../layout/_bottom.php'; ?>