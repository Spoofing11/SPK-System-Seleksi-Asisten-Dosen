<?php
session_start();
require_once '../layout/_top.php';
require_once '../helper/connection.php';

// Cek akses hanya untuk mahasiswa
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'mahasiswa') {
  $_SESSION['message'] = 'Anda tidak memiliki akses ke halaman ini!';
  $_SESSION['message_type'] = 'error';

  // Redirect ke login.php
  header('Location: ../login.php');
  exit();
}


// Ambil data mahasiswa dari sesi login
$nim = $_SESSION['login']['nim'];
$mahasiswa = mysqli_query($connection, "SELECT * FROM mahasiswa WHERE nim = '$nim'");
$dataMahasiswa = mysqli_fetch_assoc($mahasiswa);
$semesterMahasiswa = $dataMahasiswa['semester'];


// Pastikan semua data mahasiswa sudah terisi
$kolomWajib = ['nama', 'semester', 'program_studi']; // Kolom yang wajib terisi
$adaYangKosong = false;

foreach ($kolomWajib as $kolom) {
  if (empty($dataMahasiswa[$kolom])) {
    $adaYangKosong = true;
    break;
  }
}

// Jika ada data yang kosong, simpan pesan ke session dan redirect
if ($adaYangKosong) {
  $_SESSION['message'] = 'Lengkapi data Anda terlebih dahulu sebelum mengakses halaman ini!';
  $_SESSION['message_type'] = 'warning';

  header('Location: data_mahasiswa.php');
  exit();
}

// Ambil semua mata kuliah yang sesuai dengan semester mahasiswa
$queryMatkul = mysqli_query($connection, "SELECT * FROM matakuliah WHERE semester < '$semesterMahasiswa' ORDER BY semester, nama_matkul");


// Jika mahasiswa menyimpan nilai
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  foreach ($_POST['nilai'] as $kode_matkul => $nilai) {
    $semester = $_POST['semester'][$kode_matkul];
    // Konversi nilai 100 skala ke skala 4 dan huruf
    $angka = 0.00;
    $huruf = 'E';

    if ($nilai >= 85) {
      $angka = 4.00;
      $huruf = 'A';
    } elseif ($nilai >= 70) {
      $angka = 3.00;
      $huruf = 'B';
    } elseif ($nilai >= 60) {
      $angka = 2.00;
      $huruf = 'C';
    } elseif ($nilai >= 45) {
      $angka = 1.00;
      $huruf = 'D';
    }

    // Hitung mutu (angka * sks)
    $matkul = mysqli_fetch_assoc(mysqli_query($connection, "SELECT sks FROM matakuliah WHERE kode_matkul = '$kode_matkul'"));
    $mutu = $angka * $matkul['sks'];

    // Simpan ke database (insert jika belum ada, update jika sudah ada)
    $cekNilai = mysqli_query($connection, "SELECT * FROM nilai WHERE nim = '$nim' AND kode_matkul = '$kode_matkul'");
    if (mysqli_num_rows($cekNilai) > 0) {
      mysqli_query($connection, "UPDATE nilai SET nilai = '$nilai', angka = '$angka', mutu = '$mutu', semester = '$semester' 
      WHERE nim = '$nim' AND kode_matkul = '$kode_matkul'");
    } else {
      mysqli_query($connection, "INSERT INTO nilai (nim, kode_matkul, semester, nilai, angka, mutu) 
VALUES ('$nim', '$kode_matkul', '$semester', '$nilai', '$angka', '$mutu')");
    }
  }
  // Simpan pesan ke session
  $_SESSION['message'] = 'Nilai berhasil disimpan!';
  $_SESSION['message_type'] = 'success';

  // Redirect ke halaman akademik_mahasiswa.php
  header("refresh:3; url=akademik_mahasiswa.php");
  exit();
}
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
  <div class="section-header d-flex justify-content-between align-items-center">
    <h1 class="h3 mb-0">Rangkuman Nilai</h1>
  </div>
  <div class="row justify-content-center">
    <div class="col-12">
      <div class="card shadow-lg border-light rounded">
        <div class="card-header bg-primary text-white text-center">
          <h5 class="card-title mb-0">Data Nilai Mahasiswa</h5>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="table-responsive">
              <table class="table table-bordered table-striped w-100">
                <thead>
                  <tr>
                    <th>Semester</th>
                    <th>Kode Matakuliah</th>
                    <th>Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Nilai Input</th>
                    <th>Angka</th>
                    <th>Nilai</th>
                    <th>Mutu</th>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($row = mysqli_fetch_assoc($queryMatkul)):
                    // Cek apakah nilai sudah ada di database
                    $cekNilai = mysqli_query($connection, "SELECT * FROM nilai WHERE nim = '$nim' AND kode_matkul = '{$row['kode_matkul']}'");
                    $nilaiData = mysqli_fetch_assoc($cekNilai);
                    $semester = $row['semester'];
                    $nilai = isset($nilaiData['nilai']) ? $nilaiData['nilai'] : '';
                    $angka = isset($nilaiData['angka']) ? $nilaiData['angka'] : '';
                    $mutu = isset($nilaiData['mutu']) ? $nilaiData['mutu'] : '';
                    $huruf = isset($nilaiData['angka']) ? (
                      $nilaiData['angka'] >= 4.00 ? 'A' : ($nilaiData['angka'] >= 3.00 ? 'B' : ($nilaiData['angka'] >= 2.00 ? 'C' : ($nilaiData['angka'] >= 1.00 ? 'D' : 'E')))) : '';

                  ?>
                    <tr>
                      <td><?= $row['semester'] ?></td>
                      <td><?= $row['kode_matkul'] ?></td>
                      <td><?= $row['nama_matkul'] ?></td>
                      <td><?= $row['sks'] ?></td>
                      <td>
                        <input type="hidden" name="semester[<?= $row['kode_matkul'] ?>]" value="<?= $row['semester'] ?>">
                        <input type="number" name="nilai[<?= $row['kode_matkul'] ?>]"
                          value="<?= $nilai ?>" min="0" max="100"
                          class="form-control nilai-input"
                          data-kode="<?= $row['kode_matkul'] ?>"
                          <?= !empty($nilai) ? 'disabled' : '' ?>>
                      </td>
                      <td id="skala-<?= $row['kode_matkul'] ?>"><?= $angka ?></td>
                      <td id="huruf-<?= $row['kode_matkul'] ?>"><?= $huruf ?></td>
                      <td id="mutu-<?= $row['kode_matkul'] ?>"><?= $mutu ?></td>
                    </tr>

                  <?php endwhile; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="3" class="text-right">Total:</th>
                    <th id="total-sks">0</th> <!-- Total SKS -->
                    <th colspan="3"></th> <!-- Kosongkan kolom -->
                    <th id="total-mutu">0.00</th> <!-- Total Mutu -->
                  </tr>
                  <tr>
                    <th colspan="7" class="text-right">IPK:</th>
                    <th id="ipk">0.00</th> <!-- IPK -->
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="d-flex justify-content-end me-3 mt-4">
              <button type="submit" class="btn btn-success">
                <i class="fa fa-save"></i> Simpan Nilai
              </button>
            </div>
            <div class="d-flex justify-content-end me-3 mt-4 ">
              <a href="../helper/download_akademik.php" class="btn btn-danger" target="_blank">
                <i class="fa fa-file-pdf-o"></i> Cetak PDF
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
  document.querySelectorAll(".nilai-input").forEach(input => {
    input.addEventListener("input", function() {
      let nilai = parseInt(this.value) || 0;
      let kodeMatkul = this.getAttribute("data-kode");

      let skalaTarget = document.getElementById(`skala-${kodeMatkul}`);
      let hurufTarget = document.getElementById(`huruf-${kodeMatkul}`);

      let angka = 0.00,
        huruf = "E";

      if (nilai >= 85 && nilai <= 100) {
        angka = 4.00;
        huruf = "A";
      } else if (nilai >= 70) {
        angka = 3.00;
        huruf = "B";
      } else if (nilai >= 60) {
        angka = 2.00;
        huruf = "C";
      } else if (nilai >= 45) {
        angka = 1.00;
        huruf = "D";
      }

      if (skalaTarget) {
        skalaTarget.textContent = angka.toFixed(2);
      }
      if (hurufTarget) {
        hurufTarget.textContent = huruf;
      }
    });
  });



  function hitungTotal() {
    let totalSks = 0;
    let totalMutu = 0;

    document.querySelectorAll("tbody tr").forEach(row => {
      let sks = parseInt(row.querySelector("td:nth-child(4)").textContent) || 0;
      let mutu = parseFloat(row.querySelector("td:nth-child(8)").textContent) || 0;

      totalSks += sks;
      totalMutu += mutu;
    });

    let ipk = totalSks > 0 ? (totalMutu / totalSks).toFixed(2) : "0.00";

    document.getElementById("total-sks").textContent = totalSks;
    document.getElementById("total-mutu").textContent = totalMutu.toFixed(2);
    document.getElementById("ipk").textContent = ipk;
  }

  document.querySelectorAll(".nilai-input").forEach(input => {
    input.addEventListener("input", function() {
      let nilai = parseInt(this.value) || 0;
      let kodeMatkul = this.getAttribute("data-kode");
      let sks = parseInt(this.closest("tr").querySelector("td:nth-child(4)").textContent); // Ambil jumlah SKS

      let angka = 0.00,
        huruf = "E";
      if (nilai >= 80) {
        angka = 4.00;
        huruf = "A";
      } else if (nilai >= 70) {
        angka = 3.00;
        huruf = "B";
      } else if (nilai >= 60) {
        angka = 2.00;
        huruf = "C";
      } else if (nilai >= 45) {
        angka = 1.00;
        huruf = "D";
      }

      let mutu = angka * sks;

      document.getElementById("konversi-" + kodeMatkul).textContent = angka;
      this.closest("tr").querySelector("td:nth-child(7)").textContent = huruf; // Update huruf
      document.getElementById("mutu-" + kodeMatkul).textContent = mutu.toFixed(2); // Update mutu

      hitungTotal(); // Hitung total setelah input berubah
    });
  });

  window.onload = hitungTotal; // Hitung total saat halaman dimuat
</script>

<?php require_once '../layout/_bottom.php'; ?>