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

$nim = $_POST['nim'];
$semester = $_POST['semester'];
$nilai = $_POST['nilai']; // Array nilai dari form

foreach ($nilai as $kode_matkul => $nilai_angka) {
    if ($nilai_angka === '') continue; // Jika nilai kosong, skip

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

    // Konversi nilai angka ke huruf dan angka mutu
    if ($nilai_angka >= 90) {
        $nilai_huruf = 'A';
        $angka_mutu = 4.00;
    } elseif ($nilai_angka >= 80) {
        $nilai_huruf = 'B';
        $angka_mutu = 3.00;
    } elseif ($nilai_angka >= 70) {
        $nilai_huruf = 'C';
        $angka_mutu = 2.00;
    } elseif ($nilai_angka >= 60) {
        $nilai_huruf = 'D';
        $angka_mutu = 1.00;
    } else {
        $nilai_huruf = 'E';
        $angka_mutu = 0.00;
    }

    // Hitung mutu (angka_mutu * SKS)
    $mutu = $angka_mutu * $sks;

    // Query untuk menyimpan nilai ke database
    $query = mysqli_query($connection, "INSERT INTO nilai (nim, kode_matkul, semester, nilai, angka, mutu) 
VALUES ('$nim', '$kode_matkul', '$semester', '$nilai_huruf', '$angka_mutu', '$mutu')");

    if (!$query) {
        $_SESSION['info'] = [
            'status' => 'failed',
            'message' => 'Gagal menyimpan nilai: ' . mysqli_error($connection)
        ];
        header('Location: ./index.php');
        exit;
    }
}



// Setelah semua nilai disimpan, update IPK mahasiswa
$queryNilai = mysqli_query($connection, "SELECT SUM(mutu) AS total_mutu, SUM(m.sks) AS total_sks
                                        FROM nilai n 
                                        JOIN matakuliah m ON n.kode_matkul = m.kode_matkul
                                        WHERE n.nim = '$nim'");

$rowNilai = mysqli_fetch_assoc($queryNilai);
$totalMutu = $rowNilai['total_mutu'];
$totalSks = $rowNilai['total_sks'];

// Hitung IPK baru
$ipk = ($totalSks > 0) ? ($totalMutu / $totalSks) : 0.00;

// Update IPK ke tabel mahasiswa
mysqli_query($connection, "UPDATE mahasiswa SET ipk = '$ipk' WHERE nim = '$nim'");

// Jika semua berhasil
$_SESSION['info'] = [
    'status' => 'success',
    'message' => 'Berhasil menambah data dan memperbarui IPK'
];
header('Location: ./index.php');
exit;

?>
