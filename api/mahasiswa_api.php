<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../helper/connection.php';

// Cek apakah dijalankan dari CLI atau Web
if (php_sapi_name() == "cli") {
    echo "Masukkan NIM: ";
    $nim = trim(fgets(STDIN)); // Input dari terminal
} else {
    $nim = $_GET['nim'] ?? null;
}

if ($nim) {
    $nim = $connection->real_escape_string($nim);

    // Cek apakah mahasiswa sudah mendaftar di pendaftaran_asisten
    $sql = "
        SELECT 
            p.nim, 
            p.nama_mahasiswa, 
            p.semester AS semester_mahasiswa, 
            p.ipk, 
            p.status, 
            p.kode_matkul, 
            m.nama_matkul, 
            m.semester AS semester_matakuliah, 
            p.nama_dosen
        FROM pendaftaran_asisten p
        JOIN matakuliah m ON p.kode_matkul = m.kode_matkul
        WHERE p.nim = '$nim'
    ";

    $result = $connection->query($sql);

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();

        // Ambil skor kombinasi dari tabel hasil_kombinasi
        $sql_kombinasi = "SELECT skor_kombinasi_gabungan FROM hasil_kombinasi WHERE nim = '$nim'";
        $result_kombinasi = $connection->query($sql_kombinasi);
        $skor_kombinasi = ($result_kombinasi->num_rows > 0) ? $result_kombinasi->fetch_assoc()['skor_kombinasi_gabungan'] : "Belum ada perhitungan";

        // Format data sesuai permintaan
        $response = [
            "Mahasiswa" => [
                "NIM" => $data['nim'],
                "Nama" => $data['nama_mahasiswa'],
                "Semester" => $data['semester_mahasiswa'],
                "IPK" => $data['ipk']
            ],
            "Pendaftaran" => [
                "Mata Kuliah" => [
                    "Nama" => $data['nama_matkul'],
                    "Kode" => $data['kode_matkul'],
                    "Semester" => $data['semester_matakuliah']
                ],
                "Rekomendasi Dosen" => $data['nama_dosen'],
                "Status Pendaftaran" => $data['status']
            ],
            "Hasil Seleksi" => [
                "Skor Kombinasi Gabungan" => $skor_kombinasi
            ]
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
    } else {
        // Cek apakah mahasiswa ada di tabel mahasiswa
        $sql_mahasiswa = "SELECT * FROM mahasiswa WHERE nim = '$nim'";
        $result_mahasiswa = $connection->query($sql_mahasiswa);

        if ($result_mahasiswa->num_rows > 0) {
            echo json_encode([
                "status" => "Jika Ingin Mendaftar Sebagai Asisten Dosen silahkan Kunjungi Link berikut : addriel-sys.my.id",
                "message" => "Mahasiswa ditemukan, tetapi belum mendaftar sebagai asisten dosen."
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Data mahasiswa dengan NIM ini tidak ditemukan."
            ], JSON_PRETTY_PRINT);
        }
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Harap masukkan NIM sebagai parameter dalam URL. Contoh: http://localhost/system-seleksiasistendosen/api/mahasiswa_api.php?nim=201011402289"
    ], JSON_PRETTY_PRINT);
}

?>
