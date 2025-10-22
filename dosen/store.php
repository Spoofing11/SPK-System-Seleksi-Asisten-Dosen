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

// Insert ke tabel dosen
$query = mysqli_query($connection, "INSERT INTO dosen(nidn, nama_dosen, jenkel, fakultas, program_studi, gmail) 
                                    VALUES('$nidn', '$nama_dosen', '$jenkel', '$fakultas', '$program_studi', '$gmail')");

if ($query) {
    // Buat password default seperti unpam#8901
    $last4 = substr($nidn, -4);
    $raw_password = 'unpam#' . $last4;
    $default_password = password_hash($raw_password, PASSWORD_DEFAULT);
    $role = 'dosen';

    // Insert ke tabel login
    $query_login = mysqli_query($connection, "INSERT INTO login(username, password, role, nidn) 
                                              VALUES('$nidn', '$default_password', '$role', '$nidn')");

    if ($query_login) {
        $_SESSION['info'] = [
            'status' => 'success',
            'message' => 'Berhasil menambah data dosen dan akun login'
        ];
    } else {
        $_SESSION['info'] = [
            'status' => 'failed',
            'message' => 'Dosen berhasil ditambahkan, tetapi akun login gagal dibuat: ' . mysqli_error($connection)
        ];
    }

    header('Location: ./index.php');
}
 else {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => mysqli_error($connection)
    ];
    header('Location: ./index.php');
}
?>
