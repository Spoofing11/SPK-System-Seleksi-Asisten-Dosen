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


// Update status pendaftaran
if (isset($_POST['bulk_update_status']) && isset($_GET['dosen'])) {
    $dosen = $_GET['dosen'];

    $query = "
        SELECT p.nim, p.semester, p.nama_matkul,
               COALESCE(h.skor_kombinasi_gabungan, 0) AS skor_kombinasi_gabungan
        FROM pendaftaran_asisten p
        LEFT JOIN hasil_kombinasi h 
            ON p.nim = h.nim AND p.semester = h.semester AND p.nama_matkul = h.nama_matkul
        WHERE p.nama_dosen = ?
        ORDER BY h.skor_kombinasi_gabungan DESC
    ";

    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $dosen);
    $stmt->execute();
    $result = $stmt->get_result();

    $i = 0;
    while ($row = $result->fetch_assoc()) {
        $nim = $row['nim'];
        $status = ($i < 5) ? 'diterima' : 'ditolak';

        $update = $connection->prepare("UPDATE pendaftaran_asisten SET status = ? WHERE nim = ?");
        $update->bind_param('ss', $status, $nim);
        $update->execute();

        $i++;
    }

    $_SESSION['message'] = "Status mahasiswa berhasil diperbarui berdasarkan peringkat!";
    $_SESSION['message_type'] = "success";
    header("Location: data_pendaftar.php?dosen=" . urlencode($dosen));
    exit;
}


// Ambil data dosen yang ada di tabel pendaftaran_asisten
$dosen_query = "SELECT DISTINCT nama_dosen FROM pendaftaran_asisten";
$dosen_result = $connection->query($dosen_query);

// Ambil data pendaftaran berdasarkan dosen yang dipilih
$mahasiswa_result = null;
if (isset($_GET['dosen']) && !empty($_GET['dosen'])) {
    $dosen = $_GET['dosen'];
    $query = "
        SELECT p.nim, p.nama_mahasiswa, p.nama_dosen, p.semester, p.kode_matkul, p.nama_matkul, 
               COALESCE(h.skor_kombinasi_gabungan, 0) AS skor_kombinasi_gabungan, 
               COALESCE(p.status, 'pending') AS status
        FROM pendaftaran_asisten p
        LEFT JOIN hasil_kombinasi h ON p.nim = h.nim AND p.semester = h.semester 
        WHERE p.nama_dosen = ?
        ORDER BY h.skor_kombinasi_gabungan DESC
    ";
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $dosen);
    $stmt->execute();
    $mahasiswa_result = $stmt->get_result();
}
$top_5_nim = [];
$all_mahasiswa = [];

if ($mahasiswa_result && $mahasiswa_result->num_rows > 0) {
    // Simpan semua data ke array
    while ($row = $mahasiswa_result->fetch_assoc()) {
        $all_mahasiswa[] = $row;
    }

    // Ambil 5 teratas
    $top_5_data = array_slice($all_mahasiswa, 0, 5);
    foreach ($top_5_data as $top) {
        $top_5_nim[] = $top['nim'];
    }
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
        <h1 class="h3 mb-0">Update Status Pendaftaran Asisten Dosen</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card shadow-lg">
                    <div class="card-body">
                        <style>
                            .card {
                                border-radius: 12px;
                                box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.93);
                            }

                            .table thead {
                                background-color: #007bff;
                                color: white;
                            }

                            .custom-select,
                            .form-control {
                                border-radius: 8px;
                            }

                            .btn-success {
                                border-radius: 6px;
                            }

                            .form-group label {
                                font-weight: bold;
                            }
                        </style>

                        <!-- Form untuk memilih dosen -->
                        <form action="data_pendaftar.php" method="get">
                            <div class="form-group">
                                <label for="dosen" class="font-weight-bold">Pilih Dosen</label>
                                <select name="dosen" id="dosen" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- Pilih Dosen --</option>
                                    <?php while ($row = $dosen_result->fetch_assoc()): ?>
                                        <option value="<?= $row['nama_dosen']; ?>" <?= (isset($_GET['dosen']) && $_GET['dosen'] == $row['nama_dosen']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($row['nama_dosen']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>

                        <?php if ($mahasiswa_result && $mahasiswa_result->num_rows > 0): ?>
                            <div class="table-responsive mt-4">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                        <tr class="text-center">
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">No</th>
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">NIM</th>
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Nama Mahasiswa</th>
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Matkul</th>
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Skor Gabungan</th>
                                            <th style="font-weight: bold; color:rgb(0, 0, 0); text-align: center; padding: 10px; background-color:rgb(101, 153, 206); border-radius: 5px; box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.1);">Status Pendaftaran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_mahasiswa as $index => $row): ?>
                                            <?php
                                            // Tandai jika termasuk top 5
                                            $is_top_5 = in_array($row['nim'], $top_5_nim);
                                            $style = $is_top_5
                                                ? "background-color: #ffe066; color: #000; font-weight: bold; border: 2px solid #999;"
                                                : "background-color: #dee2e6; color: #000;";
                                            ?>
                                            <tr>
                                                <!-- Tambahkan nomor urut -->
                                                <td style="<?= $style ?> padding:10px; border-radius:5px; text-align: center; font-weight: bold;"><?= $index + 1; ?></td>

                                                <td style="<?= $style ?> padding:10px; border-radius:5px;"><?= htmlspecialchars($row['nim']); ?></td>
                                                <td style="<?= $style ?> padding:10px; border-radius:5px;"><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                                <td style="<?= $style ?> padding:10px; border-radius:5px;"><?= htmlspecialchars($row['nama_matkul']); ?></td>
                                                <td style="<?= $style ?> padding:10px; border-radius:5px;"><?= htmlspecialchars($row['skor_kombinasi_gabungan']); ?></td>
                                                <td style="<?= $style ?> padding:10px; border-radius:5px;" class="text-center">
                                                    <?php
                                                    $status = strtolower($row['status']);
                                                    if ($status == 'diterima') {
                                                        echo '<span class="badge badge-success">Diterima</span>';
                                                    } elseif ($status == 'ditolak') {
                                                        echo '<span class="badge badge-danger">Ditolak</span>';
                                                    } else {
                                                        echo '<span class="badge badge-warning">Dalam Proses</span>';
                                                    }
                                                    ?>
                                                </td>

                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php if (!empty($all_mahasiswa)): ?>
                                <form method="post" action="data_pendaftar.php?dosen=<?= urlencode($_GET['dosen']) ?>">
                                    <input type="hidden" name="bulk_update_status" value="1">
                                    <button type="submit" class="btn btn-success mt-3">Update Status Mahasiswa</button>
                                </form>
                            <?php endif; ?>

                        <?php else: ?>
                            <p class="alert alert-warning mt-4 text-center">Silahkan Pilih Dosen</p>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


<?php require_once '../layout/_bottom.php'; ?>