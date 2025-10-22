<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin');

$query = "SELECT nama_dosen, kode_matkul, nama_matkul, semester, standarisasi_nilai, bobot_standarisasi, nilai_matakuliah FROM pengajaran";
$result = mysqli_query($connection, $query);
?>

<section class="section">
    <div class="section-header d-flex justify-content-between">
        <h1>Daftar Matakuliah dengan Nilai Standarisasi</h1>
    </div>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Dosen</th>
                                <th>Kode Matakuliah</th>
                                <th>Nama Matakuliah</th>
                                <th>semester</th>
                                <th>Standarisasi Nilai</th>
                                <th>Bobot Standarisasi</th>
                                <th>Nilai Matakuliah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($row = mysqli_fetch_assoc($result)) : ?>
                                <tr>
                                    <td><?= $no++; ?></td>
                                    <td><?= htmlspecialchars($row['nama_dosen']); ?></td>
                                    <td><?= htmlspecialchars($row['kode_matkul']); ?></td>
                                    <td><?= htmlspecialchars($row['nama_matkul']); ?></td>
                                    <td><?= htmlspecialchars($row['semester']); ?></td>
                                    <td><?= htmlspecialchars($row['standarisasi_nilai']); ?></td>
                                    <td><?= htmlspecialchars($row['bobot_standarisasi']); ?></td>
                                    <td><?= htmlspecialchars($row['nilai_matakuliah']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
require_once '../layout/_bottom.php';
?>
