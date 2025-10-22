<?php
session_start();
require_once '../helper/connection.php'; // Pastikan path ini benar

// Validasi data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_pdf'])) {
    $nim = $_SESSION['login']['nim'];
    $nidn = $_POST['nidn'];
    $kode_matkul = $_POST['kode_matkul'];

    $file_pdf = $_FILES['file_pdf'];
    $upload_dir = '../uploads/';
    $file_name = time() . '_' . basename($file_pdf['name']);
    $file_path = $upload_dir . $file_name;

    // Membuat direktori jika belum ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (move_uploaded_file($file_pdf['tmp_name'], $file_path)) {
        // Menyimpan data ke database
        $query = "INSERT INTO file_pendaftaran (nim, nidn, kode_matkul, file_path, uploaded_at) 
                  VALUES ('$nim', '$nidn', '$kode_matkul', '$file_name', NOW())";

        $result = mysqli_query($connection, $query);

        if ($result) {
            echo "<script>alert('File berhasil dikirim ke dosen!'); window.location='../pendaftaran_asdos/pendaftaran.php';</script>";
        } else {
            echo "<script>alert('Gagal menyimpan file ke database!');</script>";
        }
    } else {
        echo "<script>alert('Gagal mengunggah file ke server.');</script>";
    }
} else {
    echo "<script>alert('Data tidak valid.');</script>";
}
?>
