<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

$nim = $_GET['nim'];
$query = mysqli_query($connection, "SELECT * FROM mahasiswa WHERE nim='$nim'");
$queryNilai = mysqli_query($connection, "SELECT n.kode_matkul, n.semester, n.nilai, n.angka, n.mutu, m.nama_matkul, m.sks 
                                        FROM nilai n 
                                        JOIN matakuliah m ON n.kode_matkul = m.kode_matkul
                                        WHERE n.nim = '$nim' ORDER BY n.semester ASC, m.nama_matkul ASC");

$totalMutu = 0;
$totalSks = 0;

while ($rowNilai = mysqli_fetch_assoc($queryNilai)) {
    $totalMutu += $rowNilai['mutu'];
    $totalSks += $rowNilai['sks'];
}

// Hitung IPK otomatis
$ipk = ($totalSks > 0) ? ($totalMutu / $totalSks) : 0.00;
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Ubah Data Mahasiswa</h1>
    <a href="./index.php" class="btn btn-light">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- // Form -->
          <form action="./update.php" method="post">
            <?php
            while ($row = mysqli_fetch_array($query)) {
            ?>
              <input type="hidden" name="nim" value="<?= $row['nim'] ?>">
              <table cellpadding="8" class="w-100">
                <tr>
                  <td>NIM</td>
                  <td><input class="form-control" required value="<?= $row['nim'] ?>" disabled></td>
                </tr>
                <tr>
                  <td>Nama Mahasiswa</td>
                  <td><input class="form-control" type="text" name="nama" required value="<?= $row['nama'] ?>"></td>
                </tr>
                <tr>
                  <td>Jenis Kelamin</td>
                  <td>
                    <select class="form-control" name="jenkel" id="jenkel" required>
                      <option value="Pria" <?php if ($row['jenis_kelamin'] == "Pria") {
                                              echo "selected";
                                            } ?>>Pria</option>
                      <option value="Wanita" <?php if ($row['jenis_kelamin'] == "Wanita") {
                                                echo "selected";
                                              } ?>>Wanita</option>
                    </select>
                  </td>
                </tr>
                <tr>
                  <td>Kota Kelahiran</td>
                  <td><input class="form-control" type="text" name="kota_lahir" required value="<?= $row['kota_kelahiran'] ?>"></td>
                </tr>
                <tr>
                  <td>Tanggal Lahir</td>
                  <td><input class="form-control" type="date" name="tanggal_lahir" required value="<?= $row['tanggal_kelahiran'] ?>"></td>
                </tr>
                <tr>
                  <td>Alamat</td>
                  <td colspan="3"><textarea class="form-control" name="alamat" id="alamat" required><?= $row['alamat'] ?></textarea></td>
                </tr>
                <tr>
                  <td>No Handphone</td>
                  <td colspan="3"><textarea class="form-control" name="no_handphone" id="no_handphone" required><?= $row['no_handphone'] ?></textarea></td>
                </tr>
                <tr>
                  <td>E-Mail</td>
                  <td colspan="3"><textarea class="form-control" name="gmail" id="gmail" required><?= $row['gmail'] ?></textarea></td>
                </tr>
                <tr>
                  <td>Program Studi</td>
                  <td>
                    <input type="text" class="form-control" name="prodi" id="prodi" value="Teknik Informatika" readonly>
                  </td>
                </tr>
                <tr>
    <td>IPK</td>
    <td>
        <input class="form-control" type="number" step="0.01" name="ipk" min="2.80" max="4.00" required value="<?= number_format($ipk, 2) ?>" readonly>
    </td>
</tr>


                <tr>
                  <td>
                    <input class="btn btn-primary d-inline" type="submit" name="proses" value="Ubah">
                    <a href="./index.php" class="btn btn-danger ml-1">Batal</a>
                  <td>
                </tr>
              </table>

            <?php } ?>
          </form>
        </div>
      </div>
    </div>
</section>

<?php
require_once '../layout/_bottom.php';
?>