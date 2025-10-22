<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../helper/connection.php';

// Cek apakah dijalankan dari CLI atau Web
if (php_sapi_name() == "cli") {
    echo "Masukkan NIDN: ";
    $nidn = trim(fgets(STDIN)); // Input dari terminal
} else {
    $nidn = $_GET['nidn'] ?? null;
}

if ($nidn) {
    $nidn = $connection->real_escape_string($nidn);

    // Query untuk mendapatkan mahasiswa yang mendaftar dengan status diterima
    $sql = "
    SELECT 
        p.nim, 
        p.nama_mahasiswa, 
        p.status, 
        p.kode_matkul, 
        m.nama_matkul, 
        p.nama_dosen, 
        p.ipk,  -- Menambahkan kolom IPK
        hk.skor_kombinasi_gabungan  -- Menambahkan kolom skor kombinasi gabungan
    FROM pendaftaran_asisten p
    JOIN matakuliah m ON p.kode_matkul = m.kode_matkul
    LEFT JOIN hasil_kombinasi hk ON p.nim = hk.nim  -- Gabungkan dengan tabel hasil_kombinasi
    WHERE p.nidn = '$nidn' AND p.status = 'Diterima'
";

    $result = $connection->query($sql);

    // Cek apakah query berhasil dan ada data
    if ($result && $result->num_rows > 0) {
        $mahasiswa_list = [];

        while ($data = $result->fetch_assoc()) {
            $mahasiswa_list[] = [
                "NIM" => $data['nim'],
                "Nama Mahasiswa" => $data['nama_mahasiswa'],
                "Status" => $data['status'],
                "Mata Kuliah" => $data['nama_matkul'] . " (Kode: " . $data['kode_matkul'] . ")",
                "IPK" => $data['ipk'],  // Menambahkan data IPK
                "Skor Kombinasi Gabungan" => $data['skor_kombinasi_gabungan']  // Menambahkan skor kombinasi gabungan
            ];
        }

        echo json_encode([
            "status" => "success",
            "message" => "Berikut adalah daftar mahasiswa yang mendaftar kepada Anda dengan status diterima.",
            "data" => $mahasiswa_list
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Tidak ada mahasiswa yang mendaftar dengan status diterima untuk NIDN ini."
        ], JSON_PRETTY_PRINT);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Harap masukkan NIDN sebagai parameter dalam URL. Contoh: http://localhost/system-seleksiasistendosen/api/dosen_api.php?nidn=1234567890"
    ], JSON_PRETTY_PRINT);
}
?>
