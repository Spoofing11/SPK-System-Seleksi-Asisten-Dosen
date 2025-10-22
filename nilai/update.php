<?php
session_start();
require_once '../helper/connection.php';
require_once '../helper/auth.php';
isLogin('admin'); 

// Validasi input
if (empty($_POST['nim']) || empty($_POST['nilai']) || empty($_POST['semester'])) {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'Semua kolom harus diisi.'
    ];
    header('Location: ./index.php');
    exit;
}

$nim = mysqli_real_escape_string($connection, $_POST['nim']);
$semester = mysqli_real_escape_string($connection, $_POST['semester']);
$nilai = $_POST['nilai']; // Nilai yang dikirimkan dari form
$kode_matkul = mysqli_real_escape_string($connection, $_POST['matkul']); // Ambil kode matkul dari form

// Ambil SKS dari tabel matakuliah berdasarkan kode_matkul
$query_sks = mysqli_query($connection, "SELECT sks FROM matakuliah WHERE kode_matkul = '$kode_matkul'");
if (!$query_sks) {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'Error pada query SKS: ' . mysqli_error($connection)
    ];
    header('Location: ./index.php');
    exit;
}
$row_sks = mysqli_fetch_assoc($query_sks);
$sks = $row_sks['sks'];

// **Konversi Nilai dari 1-100 ke Skala 4, 3, 2, 1, 0**
if ($nilai >= 90) {
    $nilai_huruf = 'A';
    $angka_mutu = 4.00;
} elseif ($nilai >= 80) {
    $nilai_huruf = 'B';
    $angka_mutu = 3.00;
} elseif ($nilai >= 70) {
    $nilai_huruf = 'C';
    $angka_mutu = 2.00;
} elseif ($nilai >= 60) {
    $nilai_huruf = 'D';
    $angka_mutu = 1.00;
} else {
    $nilai_huruf = 'E';
    $angka_mutu = 0.00;
}

// Menghitung mutu
$mutu = $angka_mutu * $sks;

// Persiapkan query untuk update data
$query = "UPDATE nilai SET nilai = ?, angka = ?, mutu = ? WHERE nim = ? AND kode_matkul = ? AND semester = ?";
$stmt = mysqli_prepare($connection, $query);
if ($stmt === false) {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'Gagal menyiapkan query: ' . mysqli_error($connection)
    ];
    header('Location: ./index.php');
    exit;
}

// Bind parameters
mysqli_stmt_bind_param($stmt, "sddsss", $nilai_huruf, $angka_mutu, $mutu, $nim, $kode_matkul, $semester);

// Eksekusi query untuk memperbarui nilai
if (!mysqli_stmt_execute($stmt)) {
    $_SESSION['info'] = [
        'status' => 'failed',
        'message' => 'Gagal memperbarui nilai: ' . mysqli_error($connection)
    ];
    header('Location: ./index.php');
    exit;
}

// Commit perubahan dan tampilkan pesan sukses
mysqli_commit($connection);

$_SESSION['info'] = [
    'status' => 'success',
    'message' => 'Berhasil memperbarui data'
];
header('Location: ./index.php');
exit;
