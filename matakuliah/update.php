<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

// Pastikan data dari form diambil dengan benar
$kode_matkul = $_POST['kode_matkul'];
$nama_matkul = $_POST['nama_matkul'];
$sks = $_POST['sks'];
$semester = $_POST['semester'];

// Perbarui query untuk mengubah kode_matkul dan kolom lainnya
$query = mysqli_query($connection, "UPDATE matakuliah SET nama_matkul = '$nama_matkul', sks = '$sks', semester = '$semester' WHERE kode_matkul = '$kode_matkul'");

if ($query) {
  // Menyimpan pesan sukses ke sesi
  $_SESSION['info'] = [
    'status' => 'success',
    'message' => 'Berhasil mengubah data'
  ];
  header('Location: ./index.php'); // Mengarahkan ke halaman index setelah sukses
} else {
  // Menyimpan pesan error ke sesi
  $_SESSION['info'] = [
    'status' => 'failed',
    'message' => mysqli_error($connection)
  ];
  header('Location: ./index.php'); // Mengarahkan ke halaman index jika gagal
}
?>
