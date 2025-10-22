<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

$nidn = $_POST['nidn'];
$nama_dosen = $_POST['nama'];
$jenkel = $_POST['jenkel'];
$fakultas = $_POST['fakultas'];
$program_studi = $_POST['program_studi'];
$gmail = $_POST['gmail'];

$query = mysqli_query($connection, "UPDATE dosen SET nama_dosen = '$nama_dosen', jenkel = '$jenkel', fakultas = '$fakultas', program_studi = '$program_studi', gmail = '$gmail' WHERE nidn = '$nidn'");
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
