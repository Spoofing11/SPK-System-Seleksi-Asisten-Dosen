<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek akses hanya untuk dosen
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'dosen') {
    $_SESSION['message'] = "Anda tidak memiliki akses ke halaman ini!";
    $_SESSION['message_type'] = "error";
    header("Location: ../login.php");
    exit;
}


$nidn_login = $_SESSION['login']['nidn'];

// Sanitasi input NIDN sebelum digunakan dalam query
$nidn_login_safe = mysqli_real_escape_string($connection, $nidn_login);

// Ambil nama dosen berdasarkan NIDN yang login
$query_dosen = "SELECT nama_dosen FROM dosen WHERE nidn = '$nidn_login_safe'";
$result_dosen = mysqli_query($connection, $query_dosen);
$dosen = mysqli_fetch_assoc($result_dosen);
$nama_dosen = $dosen['nama_dosen'];

// Ambil daftar mata kuliah yang diajarkan dosen
$query_matkul = "SELECT kode_matkul, nama_matkul, semester FROM matakuliah";
$result_matkul = mysqli_query($connection, $query_matkul);
$matakuliah_data = [];

while ($row = mysqli_fetch_assoc($result_matkul)) {
    $matakuliah_data[$row['kode_matkul']] = [
        'nama' => $row['nama_matkul'],
        'semester' => $row['semester']
    ];
}

// Proses penyimpanan ke database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_matkul = mysqli_real_escape_string($connection, $_POST['kode_matkul']);
    $nama_matkul = mysqli_real_escape_string($connection, $matakuliah_data[$kode_matkul]['nama']);
    $semester = mysqli_real_escape_string($connection, $_POST['semester']);
    $nilai_matakuliah = mysqli_real_escape_string($connection, $_POST['nilai_matakuliah']);
    $created_at = date('Y-m-d H:i:s');

    // Hitung standarisasi nilai
    if ($nilai_matakuliah >= 90) {
        $standarisasi_nilai = 'A';
        $bobot_standarisasi = 4.00;
    } elseif ($nilai_matakuliah >= 80) {
        $standarisasi_nilai = 'B';
        $bobot_standarisasi = 3.00;
    } elseif ($nilai_matakuliah >= 70) {
        $standarisasi_nilai = 'C';
        $bobot_standarisasi = 2.00;
    } else {
        $standarisasi_nilai = 'D';
        $bobot_standarisasi = 1.00;
    }

    // Cek apakah mata kuliah sudah diajarkan oleh dosen lain
    $query_check = "SELECT nama_dosen FROM pengajaran WHERE kode_matkul = '$kode_matkul' AND nidn != '$nidn_login_safe' LIMIT 1";
    $result_check = mysqli_query($connection, $query_check);

    if ($result_check && mysqli_num_rows($result_check) > 0) {
        $row = mysqli_fetch_assoc($result_check);
        $dosen_lain = addslashes($row['nama_dosen']);

        $_SESSION['message'] = "Mata kuliah ini sudah diajarkan oleh dosen lain: $dosen_lain. Data tidak dapat disimpan.";
        $_SESSION['message_type'] = "error";
        header("Location: akademik_dosen.php");
        exit;
        // Pastikan kode berhenti agar data tidak tetap masuk
    } else {

        // Cek apakah mata kuliah sudah diajarkan oleh dosen yang sama
        $query_check_own = "SELECT * FROM pengajaran WHERE kode_matkul = '$kode_matkul' AND nidn = '$nidn_login_safe' LIMIT 1";
        $result_check_own = mysqli_query($connection, $query_check_own);

        if ($result_check_own && mysqli_num_rows($result_check_own) > 0) {
            $_SESSION['message'] = "Anda sudah mengajarkan mata kuliah ini. Tidak dapat menambahkan lagi.";
            $_SESSION['message_type'] = "error";
            header("Location: akademik_dosen.php");
            exit;
            // Hentikan eksekusi agar tidak masuk ke database lagi
        }

        // Jika belum diajarkan oleh dosen lain, lanjutkan proses insert
        $query_insert = "INSERT INTO pengajaran (nidn, nama_dosen, kode_matkul, nama_matkul, semester, created_at, standarisasi_nilai, bobot_standarisasi, nilai_matakuliah) 
                     VALUES ('$nidn_login_safe', '$nama_dosen', '$kode_matkul', '$nama_matkul', '$semester', '$created_at', '$standarisasi_nilai', '$bobot_standarisasi', '$nilai_matakuliah')";

        if (mysqli_query($connection, $query_insert)) {
            // Update nilai standarisasi di tabel matakuliah hanya jika dosen tersebut mengajar mata kuliah yang bersangkutan
            $query_update = "UPDATE matakuliah 
                     SET nilai_standarisasi = '$bobot_standarisasi' 
                     WHERE kode_matkul = '$kode_matkul' 
                     AND EXISTS (SELECT 1 FROM pengajaran WHERE pengajaran.kode_matkul = matakuliah.kode_matkul AND pengajaran.nidn = '$nidn_login_safe')";

            mysqli_query($connection, $query_update);

            $_SESSION['message'] = "Data berhasil ditambahkan!";
            $_SESSION['message_type'] = "success"; // Bisa 'error', 'warning', dsb.
            header("Location: akademik_dosen.php");
            exit;
        } else {
            $_SESSION['message'] = "Terjadi kesalahan!";
            $_SESSION['message_type'] = "error"; // Bisa juga 'success', 'warning', dll.
            header("Location: halaman_sebelumnya.php"); // Ganti dengan halaman yang sesuai
            exit;
        }
    }
}

// Ambil data hasil inputan yang sudah disimpan di tabel pengajaran
$query_pengajaran = "SELECT kode_matkul, nama_matkul, nilai_matakuliah, standarisasi_nilai, bobot_standarisasi 
                     FROM pengajaran 
                     WHERE nidn = '$nidn_login_safe' 
                     ORDER BY created_at DESC";
$result_pengajaran = mysqli_query($connection, $query_pengajaran);
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
    <div class="section-header">
        <h1>Pengajaran Dosen</h1>
    </div>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h5>Tambah Mata Kuliah</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Pilih Mata Kuliah</label>
                    <select name="kode_matkul" id="kode_matkul" class="form-control" required>
                        <option value="">-- Pilih Mata Kuliah --</option>
                        <?php foreach ($matakuliah_data as $kode => $data) : ?>
                            <option value="<?= $kode ?>" data-semester="<?= $data['semester'] ?>">
                                <?= $data['nama'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Semester</label>
                    <input type="number" name="semester" id="semester" class="form-control" readonly required>
                </div>

                <div class="form-group">
                    <label>Nilai Rata-rata Mata Kuliah</label>
                    <input type="number" name="nilai_matakuliah" id="nilai_matakuliah" class="form-control" min="0" max="100" required>
                </div>

                <button type="submit" class="btn btn-primary">Tambah</button>
            </form>
            <br>
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#standarisasiModal">
                Keterangan Standarisasi Nilai
            </button>
        </div>
    </div>
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <h5>Matakuliah Yang Diajarkan oleh dosen <?= $nama_dosen ?></h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th>Kode Mata Kuliah</th>
                        <th>Nama Mata Kuliah</th>
                        <th>Nilai Mata Kuliah</th>
                        <th>Standarisasi Nilai</th>
                        <th>Konversi Nilai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_pengajaran)) : ?>
                        <tr>
                            <td><?= $row['kode_matkul'] ?></td>
                            <td><?= $row['nama_matkul'] ?></td>
                            <td><?= $row['nilai_matakuliah'] ?></td>
                            <td><?= $row['standarisasi_nilai'] ?></td>
                            <td><?= $row['bobot_standarisasi'] ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>
<!-- Modal -->
<div class="modal fade" id="standarisasiModal" tabindex="-1" aria-labelledby="standarisasiModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="standarisasiModalLabel">Keterangan Standarisasi Nilai</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nilai</th>
                            <th>Standarisasi</th>
                            <th>Bobot</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>≥ 90</td>
                            <td>A</td>
                            <td>4.00</td>
                        </tr>
                        <tr>
                            <td>≥ 80</td>
                            <td>B</td>
                            <td>3.00</td>
                        </tr>
                        <tr>
                            <td>≥ 70</td>
                            <td>C</td>
                            <td>2.00</td>
                        </tr>
                        <tr>
                            <td>&lt; 70</td>
                            <td>D</td>
                            <td>1.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php require_once '../layout/_bottom.php'; ?>


<script>
    $(document).ready(function() {
        $('#standarisasiModal').on('hidden.bs.modal', function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        });
    });


    document.getElementById('kode_matkul').addEventListener('change', function() {
        let selectedOption = this.options[this.selectedIndex];
        let semester = selectedOption.getAttribute('data-semester');
        document.getElementById('semester').value = semester || '';
    });
</script>