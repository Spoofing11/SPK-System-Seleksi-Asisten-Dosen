<?php
require_once '../layout/_top.php';
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

// Query untuk mengambil data mahasiswa beserta total mutu dan total sks
$result = mysqli_query($connection, "SELECT m.*, 
    (SELECT SUM(n.mutu) FROM nilai n WHERE n.nim = m.nim) AS totalMutu,
    (SELECT SUM(mk.sks) FROM nilai n JOIN matakuliah mk ON n.kode_matkul = mk.kode_matkul WHERE n.nim = m.nim) AS totalSks
    FROM mahasiswa m");

?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>List Mahasiswa</h1>
    <a href="./create.php" class="btn btn-primary">Tambah Data</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
      <div class="card-body">
  <div class="table-responsive">
    <table class="table table-bordered table-striped w-100 text-nowrap" id="table-1">
      <thead class="thead-dark text-center">
        <tr>
          <th>NIM</th>
          <th>Nama</th>
          <th>IPK</th>
          <th>Semester</th>
          <th>Jenis Kelamin</th>
          <th>Kota Kelahiran</th>
          <th>Tanggal Lahir</th>
          <th>Alamat</th>
          <th>E-Mail</th>
          <th>Program Studi</th>
          <th>No Handphone</th>
          <th style="width: 150px;">Aksi</th>
        </tr>
      </thead>
      <tbody>
        <?php while ($data = mysqli_fetch_array($result)): ?>
          <tr class="text-center">
            <td><?= $data['nim'] ?></td>
            <td class="text-left"><?= $data['nama'] ?></td>
            <td>
              <?php
              $totalMutu = $data['totalMutu'] ?? 0;
              $totalSks = $data['totalSks'] ?? 0;
              echo ($totalSks > 0) ? number_format($totalMutu / $totalSks, 2) : '0.00';
              ?>
            </td>
            <td><?= $data['semester'] ?></td>
            <td><?= $data['jenis_kelamin'] ?></td>
            <td><?= $data['kota_kelahiran'] ?></td>
            <td><?= $data['tanggal_kelahiran'] ?></td>
            <td class="text-left"><?= $data['alamat'] ?></td>
            <td><?= $data['gmail'] ?></td>
            <td><?= $data['program_studi'] ?></td>
            <td><?= $data['no_handphone'] ?></td>
            <td>
              <div class="btn-group" role="group">
                <a class="btn btn-sm btn-danger" href="delete.php?nim=<?= $data['nim'] ?>" title="Hapus">
                  <i class="fas fa-trash fa-fw"></i>
                </a>
                <a class="btn btn-sm btn-info" href="edit.php?nim=<?= $data['nim'] ?>" title="Edit">
                  <i class="fas fa-edit fa-fw"></i>
                </a>
              </div>
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

<?php require_once '../layout/_bottom.php'; ?>
<!-- Page Specific JS File -->
<?php
if (isset($_SESSION['info'])) :
  if ($_SESSION['info']['status'] == 'success') {
?>
    <script>
      iziToast.success({
        title: 'Sukses',
        message: `<?= $_SESSION['info']['message'] ?>`,
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
        message: `<?= $_SESSION['info']['message'] ?>`,
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