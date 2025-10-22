<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

$nim = $_POST['nim'];
$nama = $_POST['nama'];
$jenkel = $_POST['jenkel'];
$kota_lahir = $_POST['kota_lahir'];
$tanggal_lahir = $_POST['tanggal_lahir'];
$alamat = $_POST['alamat'];
$prodi = $_POST['prodi'];
$ipk = $_POST['ipk'];

if ($ipk < 2.8 || $ipk > 4) {
  $_SESSION['info'] = [
    'status' => 'failed',
    'message' => 'IPK harus antara 2.8 hingga 4'
  ];
  header('Location: ./edit.php?nim=' . $nim);
  exit();
}

$query = mysqli_query($connection, "UPDATE mahasiswa SET nama = '$nama', jenis_kelamin = '$jenkel', kota_kelahiran = '$kota_lahir', tanggal_kelahiran = '$tanggal_lahir', alamat = '$alamat', program_studi = '$prodi',  ipk = '$ipk' WHERE nim = '$nim'");
if ($query) {
  $_SESSION['info'] = [
    'status' => 'success',
    'message' => 'Berhasil mengubah data'
  ];
  header('Location: ./index.php');
} else {
  $_SESSION['info'] = [
    'status' => 'failed',
    'message' => mysqli_error($connection)
  ];
  header('Location: ./index.php');
}
