<?php
  require_once '../layout/_top.php';
  require_once '../helper/connection.php';
  require_once '../helper/auth.php';
isLogin('admin'); 

  // Ambil data mahasiswa beserta semesternya
  $mahasiswa = mysqli_query($connection, "SELECT nim, nama, semester FROM mahasiswa");

  // Ambil semua mata kuliah beserta semester-nya
  $matkul = mysqli_query($connection, "SELECT kode_matkul, nama_matkul, semester FROM matakuliah");

  // Ambil data mata kuliah yang sudah diinput nilainya
  $nilai_terdaftar = [];
  $result = mysqli_query($connection, "SELECT nim, kode_matkul FROM nilai");
  while ($row = mysqli_fetch_assoc($result)) {
    $nilai_terdaftar[$row['nim']][] = $row['kode_matkul'];
  }

  // Ambil data mahasiswa yang sudah menginput nilai untuk semua mata kuliah mereka
  $mahasiswa_sudah_input = [];
  $result = mysqli_query($connection, "SELECT nim FROM nilai GROUP BY nim HAVING COUNT(DISTINCT kode_matkul) = (SELECT COUNT(*) FROM matakuliah WHERE semester <= (SELECT semester FROM mahasiswa WHERE nim = nilai.nim))");
  while ($row = mysqli_fetch_assoc($result)) {
    $mahasiswa_sudah_input[] = $row['nim'];
  }

 
  $nilai_options = [
    '90' => 'A (90)',
    '80'  => 'B (80)',
    '70'  => 'C (70)',
    '60'  => 'D (60)',
    '0'   => 'E (0)'
  ];
?>

<section class="section">
  <div class="section-header d-flex justify-content-between">
    <h1>Tambah Nilai</h1>
    <a href="./index.php" class="btn btn-light">Kembali</a>
  </div>
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-body">
          <!-- Form -->
          <form action="./store.php" method="POST">
            <table cellpadding="8" class="w-100">
              <tr>
                <td>Nama Mahasiswa</td>
                <td>
                  <select class="form-control" name="nim" id="nim" required>
                    <option value="">--Pilih Mahasiswa--</option>
                    <?php while ($r = mysqli_fetch_array($mahasiswa)) : ?>
                      <?php if (!in_array($r['nim'], $mahasiswa_sudah_input)) : ?>
                        <option value="<?= $r['nim'] ?>" data-semester="<?= $r['semester'] ?>">
                          <?= $r['nama'] ?> (Semester <?= $r['semester'] ?>)
                        </option>
                      <?php endif; ?>
                    <?php endwhile; ?>
                  </select>
                </td>
              </tr>
              <tr>
                <td>Semester</td>
                <td>
                  <input class="form-control" type="text" name="semester" id="semester" readonly>
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  <table id="matkul_table" class="table table-striped">
                    <thead>
                      <tr>
                        <th>Mata Kuliah</th>
                        <th>Nilai</th>
                      </tr>
                    </thead>
                    <tbody id="matkul_tbody">
                      <!-- Mata kuliah akan ditampilkan di sini -->
                    </tbody>
                  </table>
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
  </div>
</section>

<script>
  document.getElementById('nim').addEventListener('change', function() {
    var selectedNim = this.value;
    var selectedSemester = this.options[this.selectedIndex].getAttribute('data-semester');
    var matkulTableBody = document.getElementById('matkul_tbody');
    var semesterInput = document.getElementById('semester');

    // Set semester otomatis
    semesterInput.value = selectedSemester;

    // Kosongkan tabel mata kuliah
    matkulTableBody.innerHTML = '';

    // Data mata kuliah dari PHP
    var matakuliah = <?= json_encode(mysqli_fetch_all($matkul, MYSQLI_ASSOC)) ?>;
    var nilaiTerdaftar = <?= json_encode($nilai_terdaftar) ?>;

    // Dapatkan daftar mata kuliah yang sudah diambil mahasiswa ini
    var matkulSudahDiambil = nilaiTerdaftar[selectedNim] || [];

    // Filter mata kuliah sesuai semester mahasiswa dan belum ada nilainya
    matakuliah.forEach(function(matkul) {
      if (parseInt(matkul.semester) <= parseInt(selectedSemester) && !matkulSudahDiambil.includes(matkul.kode_matkul)) {
        var row = document.createElement('tr');
        var cell1 = document.createElement('td');
        var cell2 = document.createElement('td');
        
        cell1.textContent = matkul.nama_matkul + " (Semester " + matkul.semester + ")";
        
        var selectNilai = document.createElement('select');
selectNilai.classList.add('form-control');
selectNilai.name = "nilai[" + matkul.kode_matkul + "]";
selectNilai.innerHTML = `
  <option value="">--Pilih Nilai--</option>
  <option value="90">A (90)</option>
  <option value="80">B (80)</option>
  <option value="70">C (70)</option>
  <option value="60">D (60)</option>
  <option value="0">E (0)</option>
`;


        cell2.appendChild(selectNilai);
        row.appendChild(cell1);
        row.appendChild(cell2);
        matkulTableBody.appendChild(row);
      }
    });
  });
</script>

<?php
  require_once '../layout/_bottom.php';
?>
