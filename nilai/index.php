<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

// Query untuk menampilkan data dengan JOIN agar lebih informatif
$query = "
SELECT n.id, n.nim, m.nama AS nama_mahasiswa, n.kode_matkul, mk.nama_matkul, mk.semester AS semester_matkul, m.semester AS semester_mahasiswa, n.nilai, n.angka, n.mutu, mk.sks
FROM nilai n
JOIN mahasiswa m ON n.nim = m.nim
JOIN matakuliah mk ON n.kode_matkul = mk.kode_matkul
ORDER BY n.semester ASC
";



$result = mysqli_query($connection, $query);
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Nilai Mahasiswa</h1>
    <a href="./create.php" class="btn btn-primary">Tambah Data</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover table-striped w-100" id="table-1">
              <thead>
                <tr class="text-center">
                  <th>No</th>
                  <th>NIM</th>
                  <th>Nama Mahasiswa</th>
                  <th>Semester</th>
                  <th>Kode Mata Kuliah</th>
                  <th>Nama Mata Kuliah</th>
                  <th>SKS</th>
                  <th>Nilai</th>
                  <th>Angka</th>
                  <th>Mutu</th>
                  <th style="width: 150px">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)) :
                  // Menentukan angka mutu dan nilai huruf berdasarkan nilai angka dari database
                  if ($row['angka'] >= 85) {
                    $nilai_huruf = 'A';
                    $angka_mutu = 4.00;
                  } elseif ($row['angka'] >= 75) {
                    $nilai_huruf = 'B';
                    $angka_mutu = 3.00;
                  } elseif ($row['angka'] >= 65) {
                    $nilai_huruf = 'C';
                    $angka_mutu = 2.00;
                  } elseif ($row['angka'] >= 50) {
                    $nilai_huruf = 'D';
                    $angka_mutu = 1.00;
                  } else {
                    $nilai_huruf = 'E';
                    $angka_mutu = 0.00;
                  }

                  // Menghitung mutu (angka_mutu * SKS)
                  $mutu = $angka_mutu * $row['sks'];
                ?>
                  <tr class="text-center">
                    <td><?= $no++ ?></td>
                    <td><?= $row['nim'] ?></td>
                    <td><?= $row['nama_mahasiswa'] ?></td>
                    <td><?= $row['semester_mahasiswa'] ?></td>
                    <td><?= $row['kode_matkul'] ?></td>
                    <td><?= $row['nama_matkul'] ?></td>
                    <td><?= $row['sks'] ?></td>
                    <td><?= $row['nilai'] ?></td> <!-- Menampilkan Nilai Huruf -->
                    <td><?= number_format($row['angka'], 2) ?></td> <!-- Menampilkan Angka Mutu -->
                    <td><?= number_format($row['mutu'], 2) ?></td> <!-- Menampilkan Mutu -->

                    <td>
                      <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                      <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Hapus</a>
                    </td>
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

<!-- Page Specific JS File -->
<?php
if (isset($_SESSION['info'])) :
  if ($_SESSION['info']['status'] == 'success') {
?>
    <script>
      iziToast.success({
        title: 'Sukses',
        message: `<?= htmlspecialchars($_SESSION['info']['message']) ?>`,
        position: 'topCenter',
        timeout: 5000
      });
    </script>
  <?php
  } else {
  ?>
    <script>
      iziToast.error({
        title: 'Gagal',
        message: `<?= htmlspecialchars($_SESSION['info']['message']) ?>`,
        timeout: 5000,
        position: 'topCenter'
      });
    </script>
<?php
  }
  unset($_SESSION['info']);
  $_SESSION['info'] = null;
endif;
?>
<script src="../assets/js/page/modules-datatables.js"></script>