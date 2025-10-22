<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

// Ambil kode_matkul dari URL
$kode_matkul = $_GET['kode_matkul'];

// Query untuk mengambil data mata kuliah berdasarkan kode_matkul
$query = mysqli_query($connection, "SELECT * FROM matakuliah WHERE kode_matkul='$kode_matkul'");

?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Ubah Data Prodi</h1>
    <a href="./index.php" class="btn btn-light">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- Form untuk mengubah data -->
          <form action="./update.php" method="post">
            <?php
            // Loop untuk mengambil data dari hasil query
            while ($row = mysqli_fetch_array($query)) {
            ?>
              <input type="hidden" name="kode_matkul" value="<?= $row['kode_matkul'] ?>">
              <table cellpadding="8" class="w-100">
                <tr>
                  <td>Kode Mata Kuliah</td>
                  <td><input class="form-control" required value="<?= $row['kode_matkul'] ?>" disabled></td>
                </tr>
                <tr>
                  <td>Nama Mata Kuliah</td>
                  <td><input class="form-control" type="text" name="nama_matkul" required value="<?= $row['nama_matkul'] ?>"></td>
                </tr>
                <tr>
                  <td>SKS</td>
                  <td><input class="form-control" type="number" name="sks" max="6" required value="<?= $row['sks'] ?>"></td>
                </tr>
                <tr>
                  <td>Semester</td>
                  <td>
                    <select class="form-control" name="semester" required>
                      <option value="1" <?= $row['semester'] == 1 ? 'selected' : '' ?>>Semester 1</option>
                      <option value="2" <?= $row['semester'] == 2 ? 'selected' : '' ?>>Semester 2</option>
                      <option value="3" <?= $row['semester'] == 3 ? 'selected' : '' ?>>Semester 3</option>
                      <option value="4" <?= $row['semester'] == 4 ? 'selected' : '' ?>>Semester 4</option>
                      <option value="5" <?= $row['semester'] == 5 ? 'selected' : '' ?>>Semester 5</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>
                    <input class="btn btn-primary d-inline" type="submit" name="proses" value="Ubah">
                    <a href="./index.php" class="btn btn-danger ml-1">Batal</a>
                  </td>
                </tr>
              </table>
            <?php } ?>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<?php
require_once '../layout/_bottom.php';
?>
