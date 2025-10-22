<?php
require_once '../helper/connection.php';

$nama_pengguna = 'User'; // Default jika tidak ditemukan
$username = $_SESSION['login']['username']; // Ambil username dari session
$role = $_SESSION['login']['role']; // Ambil role dari session


// Ambil nama berdasarkan role
if ($role === 'mahasiswa') {
  // Cari berdasarkan NIM di tabel mahasiswa
  $query = "SELECT nama FROM mahasiswa WHERE nim = (SELECT nim FROM login WHERE username = '$username')";
  $result = mysqli_query($connection, $query);
  if ($row = mysqli_fetch_assoc($result)) {
    $nama_pengguna = $row['nama'];
  }
} elseif ($role === 'dosen') {
  // Escape username untuk mencegah error SQL
  $username_safe = mysqli_real_escape_string($connection, $username);

  // Cari berdasarkan NIDN di tabel dosen
  $query = "SELECT nama_dosen FROM dosen WHERE nidn = (SELECT nidn FROM login WHERE username = '$username_safe')";
  $result = mysqli_query($connection, $query);

  if (!$result) {
    die("Query Error: " . mysqli_error($connection)); // Debugging jika terjadi error SQL
  }

  if ($row = mysqli_fetch_assoc($result)) {
    $nama_pengguna = $row['nama_dosen'];
  }
} elseif ($role === 'koordinator' || $role === 'admin') {
  // Ambil langsung dari username di tabel login
  $query = "SELECT username FROM login WHERE username = '$username'";
  $result = mysqli_query($connection, $query);
  if ($row = mysqli_fetch_assoc($result)) {
    $nama_pengguna = $row['username'];
  }
}
?>

<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
  <form class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li><a href="#" data-toggle="sidebar" class="nav-link nav-link-lg"><i class="fas fa-bars"></i></a></li>
      <li><a href="#" data-toggle="search" class="nav-link nav-link-lg d-sm-none"><i class="fas fa-search"></i></a></li>
    </ul>
  </form>
  <ul class="navbar-nav navbar-right">
    <li class="dropdown"><a href="#" data-toggle="dropdown" class="nav-link dropdown-toggle nav-link-lg nav-link-user">
        <img alt="image" src="../assets/img/avatar/avatar-1.png" class="rounded-circle mr-1">
        <div class="d-sm-none d-lg-inline-block">Hi, <?= htmlspecialchars($nama_pengguna) ?></div>
      </a>
      <div class="dropdown-menu dropdown-menu-right">
        <a href="#" class="dropdown-item has-icon" data-toggle="modal" data-target="#ubahPasswordModal">
          <i class="fas fa-key"></i> Ubah Password
        </a>
        <a href="../logout.php" class="dropdown-item has-icon text-danger">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </div>
    </li>
  </ul>
</nav>

<!-- Modal Ubah Password -->
<div class="modal fade" id="ubahPasswordModal" tabindex="-1" role="dialog" aria-labelledby="ubahPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="ubahPasswordModalLabel">Ubah Password</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="ubahPasswordForm" method="POST" action="../helper/ubah_password.php">
        <input type="hidden" name="ubah_password" value="1">
        <div class="modal-body">
          <div class="form-group">
            <label for="password_lama">Password Lama</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password_lama" name="password_lama" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_lama">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="password_baru">Password Baru</label>
            <div class="input-group">
              <input type="password" class="form-control" id="password_baru" name="password_baru" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#password_baru">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="text-danger d-none" id="passwordError">Password minimal 6 karakter!</small>
          </div>
          <div class="form-group">
            <label for="konfirmasi_password">Konfirmasi Password Baru</label>
            <div class="input-group">
              <input type="password" class="form-control" id="konfirmasi_password" name="konfirmasi_password" required>
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-password" data-target="#konfirmasi_password">
                  <i class="fa fa-eye"></i>
                </button>
              </div>
            </div>
            <small class="text-danger d-none" id="konfirmasiError">Konfirmasi password tidak cocok!</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Masukkan jQuery melalui CDN (di bagian atas file atau sebelum tag penutup </body>) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
  $(document).ready(function() {
    // Toggle show/hide password
    $(".toggle-password").click(function() {
      let target = $(this).data("target");
      let input = $(target);
      if (input.attr("type") === "password") {
        input.attr("type", "text");
        $(this).html('<i class="fa fa-eye-slash"></i>');
      } else {
        input.attr("type", "password");
        $(this).html('<i class="fa fa-eye"></i>');
      }
    });

    // Validasi sebelum submit (bukan AJAX)
    $("#ubahPasswordForm").submit(function() {
      let passwordBaru = $("#password_baru").val();
      let konfirmasiPassword = $("#konfirmasi_password").val();

      $("#passwordError, #konfirmasiError").addClass("d-none");

      if (passwordBaru.length < 6) {
        $("#passwordError").removeClass("d-none");
        return false;
      }

      if (passwordBaru !== konfirmasiPassword) {
        $("#konfirmasiError").removeClass("d-none");
        return false;
      }

      return true; // Submit jika valid
    });
  });
</script>