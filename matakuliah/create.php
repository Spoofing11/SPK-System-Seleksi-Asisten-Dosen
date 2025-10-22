<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Tambah Mata Kuliah</h1>
    <a href="./index.php" class="btn btn-light">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- // Form -->
          <form action="./store.php" method="POST">
            <table cellpadding="8" class="w-100">
              <tr>
                <td>Kode Mata Kuliah</td>
                <td><input class="form-control" type="text" name="kode_matkul"></td>
              </tr>
              <tr>
                <td>Nama Mata Kuliah</td>
                <td><input class="form-control" type="text" name="nama_matkul"></td>
              </tr>
              <tr>
                <td>SKS</td>
                <td><input class="form-control" type="number" max="6" name="sks"></td>
              </tr>
              <tr>
  <td>Semester</td>
  <td>
    <select class="form-control" name="semester" required>
      <option value="4">Semester 1</option>
      <option value="5">Semester 2</option>
      <option value="6">Semester 3</option>
      <option value="4">Semester 4</option>
      <option value="5">Semester 5</option>
      <option value="6">Semester 6</option>
      <option value="7">Semester 7</option>
    </select>
  </td>
</tr>

              <tr>
                <td>
                  <input class="btn btn-primary" type="submit" name="proses" value="Simpan">
                  <input class="btn btn-danger" type="reset" name="batal" value="Bersihkan">
                </td>
              </tr>
            </table>
          </form>
        </div>
      </div>
    </div>
</section>

<?php
require_once '../layout/_bottom.php';
?>