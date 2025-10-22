<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="index.php">
        <img src="../assets/img/Unpam_Logos.png" alt="logo" width="50">
      </a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="index.php"></a>
    </div>
    <ul class="sidebar-menu">
      <li class="menu-header">Dashboard</li>
      <li><a class="nav-link" href="../"><i class="fas fa-fire"></i> <span>Home</span></a></li>

      <?php if ($_SESSION['login']['role'] === 'koordinator'): ?>
        <li class="menu-header">Koordinator Feature</li>

        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i><span>Perhitungan</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../koordinator/perhitungan_topsis.php">Topsis</a></li>
            <li><a class="nav-link" href="../koordinator/perhitungan_pm.php">Profile Matching</a></li>
            <li><a class="nav-link" href="../koordinator/hasil_perhitungan.php">Kombinasi Perhitungan</a></li>
          </ul>
        </li>


        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Update Status</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../koordinator/data_pendaftar.php">Data Pendaftar Asdos</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($_SESSION['login']['role'] === 'admin'): ?>
        <li class="menu-header">Admin Feature</li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Dosen</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dosen/index.php">List</a></li>
            <li><a class="nav-link" href="../dosen/create.php">Tambah Data</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Mata Kuliah</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../matakuliah/index.php">List</a></li>
            <li><a class="nav-link" href="../matakuliah/create.php">Tambah Data</a></li>
            <li><a class="nav-link" href="../matakuliah/matkul_nilai.php">Nilai Standarisasi</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Mahasiswa</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../mahasiswa/index.php">List</a></li>
            <li><a class="nav-link" href="../mahasiswa/create.php">Tambah Data</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Nilai</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../nilai/index.php">List</a></li>
            <li><a class="nav-link" href="../nilai/create.php">Tambah Data</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Data Asdos</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dashboard/data_asdos.php">Data Asisten Dosen</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($_SESSION['login']['role'] === 'mahasiswa'): ?>
        <li class="menu-header">Mahasiswa Feature</li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-user"></i><span>Data Pribadi</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dashboard/data_mahasiswa.php">Lengkapi Data</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Data Akademik</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dashboard/akademik_mahasiswa.php">Input Nilai</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-user"></i><span>Pendaftaran Asdos</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../pendaftaran_asdos/pendaftaran.php">Pendaftaran</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a href="#" class="nav-link has-dropdown"><i class="fas fa-user"></i><span>Riwayat Pendafataran</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../pendaftaran_asdos/history.php">Histoy & Status</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if ($_SESSION['login']['role'] === 'dosen'): ?>
        <li class="menu-header">Dosen Feature</li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Data Dosen</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dashboard/data_dosen.php">Lengkapi Data</a></li>
            <li><a class="nav-link" href="../dashboard/akademik_dosen.php">Akademik</a></li>
          </ul>
        </li>
        <li class="dropdown">
          <a href="#" class="nav-link has-dropdown" data-toggle="dropdown"><i class="fas fa-columns"></i> <span>Data Asisten Dosen</span></a>
          <ul class="dropdown-menu">
            <li><a class="nav-link" href="../dashboard/asdos_dosen.php">Mahasiswa Yang Mendaftar</a></li>
          </ul>
        </li>
      <?php endif; ?>
    </ul>
  </aside>
</div>