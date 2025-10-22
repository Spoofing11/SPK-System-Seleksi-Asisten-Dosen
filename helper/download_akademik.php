<?php
ob_start(); // Start output buffering

require '../lib/fpdf.php'; // Pustaka FPDF
require_once 'connection.php';

session_start(); // Pastikan sesi dimulai

// Cek akses hanya untuk mahasiswa
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] != 'mahasiswa') {
    die("Error: Anda tidak memiliki akses.");
}

// Ambil NIM dari sesi
$nim = $_SESSION['login']['nim'];

// Query data mahasiswa
$mahasiswaQuery = mysqli_query($connection, "SELECT * FROM mahasiswa WHERE nim = '$nim'");

// Cek jika mahasiswa ditemukan
if (!$mahasiswaQuery) {
    die("Error: Query mahasiswa gagal. " . mysqli_error($connection));
}

$dataMahasiswa = mysqli_fetch_assoc($mahasiswaQuery);
if (!$dataMahasiswa) {
    die("Error: Data mahasiswa tidak ditemukan untuk NIM $nim.");
}

// Query nilai mahasiswa
$queryNilai = mysqli_query($connection, "SELECT n.kode_matkul, n.semester, n.nilai, n.angka, n.mutu, m.nama_matkul, m.sks
                                        FROM nilai n 
                                        JOIN matakuliah m ON n.kode_matkul = m.kode_matkul
                                        WHERE n.nim = '$nim'
                                        ORDER BY n.semester ASC, m.nama_matkul ASC");
                                       

// Cek jika query nilai berhasil
if (!$queryNilai) {
    die("Error: Query nilai gagal. " . mysqli_error($connection));
}

$totalMutu = 0;
$totalSks = 0;

// Inisialisasi FPDF
$pdf = new FPDF();
$pdf->AddPage();

// Menambahkan logo kiri
$pdf->Image('../assets/img/sasmita_logos.jpg', 10, 8, 30);

// Menambahkan logo kanan
$pdf->Image('../assets/img/Unpam_Logos.png', 170, 8, 30);

// Mengatur posisi tengah
$textWidth = 130;
$pageWidth = $pdf->GetPageWidth();
$centerX = ($pageWidth - $textWidth) / 2;

// Menentukan posisi awal teks
$yPos = 12;

// Menampilkan teks dengan ukuran yang berbeda-beda
$pdf->SetXY($centerX, $yPos);
$offsetX = 0; // Geser ke kanan sejauh 20 mm
$alignmentX = 0;

$pdf->SetTextColor(0, 0, 128);
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetX($centerX + $alignmentX); // Geser ke kanan
$pdf->Cell($textWidth, 6, 'YAYASAN SASMITA JAYA', 0, 1, 'C');

$pdf->SetTextColor(0, 0, 128);
$pdf->SetFont('Arial', 'B', 15);
$pdf->SetX($centerX + $offsetX);
$pdf->Cell($textWidth, 6, 'UNIVERSITAS PAMULANG', 0, 1, 'C');

$pdf->SetTextColor(0, 0, 128);
$pdf->SetFont('Arial', 'B', 12);
$pdf->SetX($centerX + $offsetX);
$pdf->Cell($textWidth, 6, 'FAKULTAS ILMU KOMPUTER', 0, 1, 'C');

$pdf->SetTextColor(0, 0, 128);
$pdf->SetFont('Arial', '', 9);
$pdf->SetX($centerX + $offsetX);
$pdf->MultiCell($textWidth, 5, 'Jl. Surya Kencana No.1 Pamulang, Kota Tangerang Selatan Provinsi Banten', 0, 'C');


// Menambahkan email dan website sebagai hyperlink
$pdf->SetXY($centerX, $pdf->GetY()); // Tambahkan sedikit jarak
$pdf->SetX($centerX + 5);
$pdf->SetFont('Arial', 'U', 10); // Font underline untuk hyperlink
$pdf->SetTextColor(0, 0, 128);  // Warna biru untuk link
$pdf->Write(5, 'Email: teknikinformatika@unpam.ac.id');

$pdf->SetX($centerX + 65); // Geser ke kanan untuk memisahkan email dan website
$pdf->Write(5, ' | Website: informatika.unpam.ac.id');

// Tambahkan garis bawah setelah header
$pdf->SetY($pdf->GetY() + 5); // Geser ke bawah agar ada jarak
$pdf->SetTextColor(0, 0, 0); // Kembalikan warna ke hitam
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());


// Geser ke bawah setelah logo
$pdf->Ln(5); // Memberikan jarak setelah logo

// Judul
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(190, 10, 'Transkip Nilai Sementara', 0, 1, 'C');
$pdf->Ln(5);

// Informasi Mahasiswa
$pdf->SetFont('Arial', '', 10);

// Nama Mahasiswa
$pdf->Cell(50, 10, strtoupper('Nama Mahasiswa'), 0, 0);  // Label 'Nama Mahasiswa' dalam huruf kapital
$pdf->Cell(5, 10, ':', 0, 0); // Titik dua (:) sejajar
$pdf->Cell(90, 10, strtoupper($dataMahasiswa['nama']), 0, 1); // Nama mahasiswa dalam huruf kapital

// NIM Mahasiswa
$pdf->Cell(50, 10, strtoupper('NIM'), 0, 0);  // Label 'NIM' dalam huruf kapital
$pdf->Cell(5, 10, ':', 0, 0); // Titik dua (:) sejajar
$pdf->Cell(90, 10, strtoupper($nim), 0, 1); // NIM mahasiswa dalam huruf kapital

// Semester Mahasiswa
$pdf->Cell(50, 10, strtoupper('Semester'), 0, 0);  // Label 'Semester' dalam huruf kapital
$pdf->Cell(5, 10, ':', 0, 0); // Titik dua (:) sejajar
$pdf->Cell(90, 10, strtoupper(trim($dataMahasiswa['semester'])), 0, 1); // semester mahasiswa dalam huruf kapital

// PRODI Mahasiswa
$pdf->Cell(50, 10, strtoupper('Prodi'), 0, 0);  // Label 'PRODI' dalam huruf kapital
$pdf->Cell(5, 10, ':', 0, 0); // Titik dua (:) sejajar
$pdf->Cell(90, 10, strtoupper('TEKNIK INFORMATIKA S1'), 0, 1); // Program Studi dalam huruf kapital


$pdf->Ln(5); // Menambahkan spasi setelah informasi mahasiswa


// Header Tabel
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(25, 8, 'Kode', 1, 0, 'C', true);
$pdf->Cell(90, 8, 'Mata Kuliah', 1, 0, 'C', true);
$pdf->Cell(15, 8, 'SKS', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Nilai', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Angka', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Mutu', 1, 1, 'C', true);

// Isi Tabel
$pdf->SetFont('Arial', '', 10);
while ($row = mysqli_fetch_assoc($queryNilai)) {
    $totalMutu += $row['mutu'];
    $totalSks += $row['sks'];

    // Tambahkan data ke dalam tabel
    $pdf->Cell(25, 8, $row['kode_matkul'], 1);
    $pdf->Cell(90, 8, $row['nama_matkul'], 1);
    $pdf->Cell(15, 8, $row['sks'], 1, 0, 'C');
    // Konversi Nilai Angka ke Huruf
    $nilai_angka = isset($row['angka']) ? $row['angka'] : 0;
    $nilai_huruf = '';

    if ($nilai_angka >= 4.00) $nilai_huruf = 'A';
    elseif ($nilai_angka >= 3.75) $nilai_huruf = 'A-';
    elseif ($nilai_angka >= 3.50) $nilai_huruf = 'B+';
    elseif ($nilai_angka >= 3.00) $nilai_huruf = 'B';
    elseif ($nilai_angka >= 2.75) $nilai_huruf = 'B-';
    elseif ($nilai_angka >= 2.50) $nilai_huruf = 'C+';
    elseif ($nilai_angka >= 2.00) $nilai_huruf = 'C';
    elseif ($nilai_angka >= 1.00) $nilai_huruf = 'D';
    else $nilai_huruf = 'E';

    $pdf->Cell(20, 8, $nilai_huruf, 1, 0, 'C'); // Menampilkan huruf, bukan angka

    $pdf->Cell(20, 8, number_format($row['angka'], 2), 1, 0, 'C');
    $pdf->Cell(25, 8, number_format($row['mutu'], 2), 1, 1, 'C');
}

// Hitung IPK
$ipk = ($totalSks > 0) ? number_format($totalMutu / $totalSks, 2) : "0.00";

// Tambahkan Total SKS, Total Mutu, dan IPK
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(115, 8, 'Total SKS', 1);
$pdf->Cell(15, 8, $totalSks, 1, 0, 'C');
$pdf->Cell(65, 8, '', 1, 1, 1);

$pdf->Cell(115, 8, 'Total Mutu', 1);
$pdf->Cell(55, 8, '', 1);
$pdf->Cell(25, 8, number_format($totalMutu, 2), 1, 1, 'C');

$pdf->Cell(115, 8, 'IPK', 1);
$pdf->Cell(55, 8, '', 1);
$pdf->Cell(25, 8, $ipk, 1, 1, 'C');

// Pastikan tidak ada output sebelum ini
// Pastikan tidak ada output sebelum ini
ob_end_clean();
$nama_file = "Rangkuman_Nilai_{$nim}_" . preg_replace('/[^A-Za-z0-9_]/', '', $dataMahasiswa['nama']) . ".pdf";

// Simpan file ke dalam folder 'files'
$pdf->Output("F", "../document/".$nama_file); 

// Redirect ke file yang sudah disimpan agar user bisa mendownloadnya
header("Location: ../document/".$nama_file);
exit;

