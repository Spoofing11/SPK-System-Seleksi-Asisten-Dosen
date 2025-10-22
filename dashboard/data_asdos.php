<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin');

// Ambil daftar dosen untuk dropdown
$dosen_result = $connection->query("SELECT DISTINCT nama_dosen FROM pendaftaran_asisten");

// Cek apakah ada filter dosen dan semester
$selected_dosen = isset($_GET['dosen']) ? $_GET['dosen'] : '';
$selected_semester = isset($_GET['semester']) ? $_GET['semester'] : '';

?>

<section class="section">
    <div class="section-header d-flex justify-content-between align-items-center">
        <h1 class="h3 mb-0">Rekapitulasi Asisten Dosen Terpilih</h1>
    </div>
    <div class="row justify-content-center">
        <div class="col-12">
            <div class="card shadow-lg border-light rounded">
                <div class="card-body">
                    <!-- Form untuk memilih dosen -->
                    <form action="data_asdos.php" method="get">
                        <div class="form-group">
                            <label for="dosen" class="font-weight-bold">Pilih Dosen</label>
                            <select name="dosen" id="dosen" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Pilih Dosen --</option>
                                <?php while ($row = $dosen_result->fetch_assoc()): ?>
                                    <option value="<?= htmlspecialchars($row['nama_dosen']); ?>"
                                        <?= ($selected_dosen == $row['nama_dosen']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($row['nama_dosen']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </form>

                    <!-- Tampilkan dropdown semester jika dosen sudah dipilih -->
                    <?php if (!empty($selected_dosen)): ?>
                        <?php
                        // Ambil daftar semester berdasarkan dosen yang dipilih
                        $semester_query = "SELECT DISTINCT semester FROM pendaftaran_asisten WHERE nama_dosen = ?";
                        $stmt_semester = $connection->prepare($semester_query);
                        $stmt_semester->bind_param("s", $selected_dosen);
                        $stmt_semester->execute();
                        $semester_result = $stmt_semester->get_result();
                        ?>

                        <form action="data_asdos.php" method="get">
                            <input type="hidden" name="dosen" value="<?= htmlspecialchars($selected_dosen); ?>">
                            <div class="form-group">
                                <label for="semester" class="font-weight-bold">Pilih Semester</label>
                                <select name="semester" id="semester" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- Pilih Semester --</option>
                                    <?php while ($row = $semester_result->fetch_assoc()): ?>
                                        <option value="<?= htmlspecialchars($row['semester']); ?>"
                                            <?= ($selected_semester == $row['semester']) ? 'selected' : ''; ?>>
                                            Semester <?= htmlspecialchars($row['semester']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    <?php endif; ?>

                    <!-- Tampilkan data hanya jika dosen dan semester dipilih -->
                    <?php if (!empty($selected_dosen) && !empty($selected_semester)): ?>
                        <?php
                        // Query untuk mengambil data mahasiswa berdasarkan dosen dan semester yang dipilih
                        $query = "SELECT nim, nama_mahasiswa, semester, nama_dosen, nama_matkul, status, created_at 
          FROM pendaftaran_asisten 
          WHERE status IN ('Diterima', 'Ditolak') 
          AND nama_dosen = ? 
          AND semester = ?
          ORDER BY 
            CASE 
              WHEN status = 'Diterima' THEN 1
              WHEN status = 'Ditolak' THEN 2
              ELSE 3
            END,
            created_at DESC";

                        $stmt = $connection->prepare($query);
                        $stmt->bind_param("si", $selected_dosen, $selected_semester);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        ?>

                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive mt-4">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                        <tr class="text-center">
                                            <th>NIM</th>
                                            <th>Semester</th>
                                            <th>Nama Mahasiswa</th>
                                            <th>Mata Kuliah Yang Didaftarkan</th>
                                            <th>Status</th>
                                            <th>Tanggal Daftar</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td class="text-center"><?= htmlspecialchars($row['nim']); ?></td>
                                                <td><?= htmlspecialchars($row['semester']); ?></td>
                                                <td><?= htmlspecialchars($row['nama_mahasiswa']); ?></td>
                                                <td><?= htmlspecialchars($row['nama_matkul']); ?></td>
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

                                                <td class="text-center"><?= htmlspecialchars(date('d-m-Y H:i', strtotime($row['created_at']))); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="alert alert-warning mt-4 text-center">Belum ada mahasiswa yang diterima atau ditolak untuk semester ini.</p>
                        <?php endif; ?>

                    <?php elseif (!empty($selected_dosen)): ?>
                        <p class="alert alert-info mt-4 text-center">Silakan pilih semester terlebih dahulu.</p>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once '../layout/_bottom.php'; ?>

<script src="../assets/js/page/modules-datatables.js"></script>