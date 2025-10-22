<?php

require_once 'connection.php';

// Ambil semua mahasiswa yang belum memiliki nilai di tabel 'nilai'
$queryMahasiswa = "SELECT nim, semester FROM mahasiswa WHERE nim NOT IN (SELECT DISTINCT nim FROM nilai)";
$resultMahasiswa = $connection->query($queryMahasiswa);

if ($resultMahasiswa->num_rows == 0) {
    echo "Semua mahasiswa sudah memiliki nilai.\n";
    exit;
}

// Daftar mata kuliah berdasarkan semester
$mata_kuliah = [
    1 => ["TPL0013", "TPL0022", "TPL0052"],
    2 => ["TPL0062", "TPL0082"],
    3 => ["TPL0113", "TPL0192"],
    4 => ["TPL0203", "TPL0212", "TPL0243", "TPL0273"],
    5 => ["TPL0282", "TPL0293", "TPL0302", "TPL0323"],
];

// Proses setiap mahasiswa yang belum memiliki nilai
while ($row = $resultMahasiswa->fetch_assoc()) {
    $nim = $row['nim'];
    $semester_mahasiswa = $row['semester'];

    echo "Memproses mahasiswa: $nim, Semester: $semester_mahasiswa\n";

    // Iterasi mata kuliah yang bisa diambil berdasarkan semester mahasiswa
   for ($i = 1; $i < $semester_mahasiswa; $i++) {
        if (!isset($mata_kuliah[$i])) continue;

        foreach ($mata_kuliah[$i] as $kode_matkul) {
            echo "Cek mata kuliah: $kode_matkul untuk mahasiswa: $nim\n";

            // Generate nilai secara random untuk simulasi (1 - 100)
            $nilai = rand(12, 20) * 5;

            // Konversi nilai ke angka dan mutu
            if ($nilai >= 85) {
                $angka = 4.00;
            } elseif ($nilai >= 70) {
                $angka = 3.00;
            } elseif ($nilai >= 60) {
                $angka = 2.00;
            } elseif ($nilai >= 45) {
                $angka = 1.00;
            } else {
                $angka = 0.00;
            }

            // Ambil SKS dari tabel matakuliah
            $query_sks = "SELECT sks FROM matakuliah WHERE kode_matkul = '$kode_matkul'";
            $result_sks = $connection->query($query_sks);
            $row_sks = $result_sks->fetch_assoc();
            $sks = $row_sks['sks'] ?? 0; // Jika tidak ditemukan, default 0 SKS

            echo "Cek SKS: Mata Kuliah $kode_matkul, SKS = $sks\n"; // Debug

            // Pastikan SKS tidak 0 sebelum menyimpan
            if ($sks == 0) {
                echo "⚠ Mata kuliah $kode_matkul tidak ditemukan dalam tabel matakuliah atau SKS = 0. Lewati!\n";
                continue;
            }

            // Hitung mutu berdasarkan SKS
            $mutu = $angka * $sks;

            // Cek apakah data sudah ada di tabel nilai
            $check_nilai = "SELECT * FROM nilai WHERE nim = '$nim' AND kode_matkul = '$kode_matkul'";
            $result_nilai = $connection->query($check_nilai);
            if ($result_nilai->num_rows > 0) {
                echo "⚠ Data nilai untuk mahasiswa $nim - mata kuliah $kode_matkul sudah ada. Lewati!\n";
                continue;
            }

            // Masukkan ke dalam database
            $sql = "INSERT INTO nilai (nim, kode_matkul, semester, nilai, angka, mutu) 
                    VALUES ('$nim', '$kode_matkul', '$i', '$nilai', '$angka', '$mutu')";
            
            echo "Query SQL: $sql\n"; // Debug query sebelum eksekusi

            if ($connection->query($sql) === TRUE) {
                echo "✅ Data nilai untuk mahasiswa $nim - mata kuliah $kode_matkul berhasil dimasukkan.\n";
            } else {
                echo "❌ Error: " . $sql . "\n" . $connection->error;
            }
        }
    }

    // **Tambahan Logika untuk Mengupdate IPK Mahasiswa**
    $queryMutu = "SELECT SUM(mutu) as total_mutu FROM nilai WHERE nim = '$nim'";
    $resultMutu = $connection->query($queryMutu);
    $rowMutu = $resultMutu->fetch_assoc();
    $total_mutu = $rowMutu['total_mutu'] ?? 0;

    $querySKS = "SELECT SUM(m.sks) as total_sks 
                 FROM nilai n 
                 JOIN matakuliah m ON n.kode_matkul = m.kode_matkul 
                 WHERE n.nim = '$nim'";
    $resultSKS = $connection->query($querySKS);
    $rowSKS = $resultSKS->fetch_assoc();
    $total_sks = $rowSKS['total_sks'] ?? 0;

    $ipk = ($total_sks > 0) ? ($total_mutu / $total_sks) : 0;

    echo "Total Mutu: $total_mutu, Total SKS: $total_sks, IPK dihitung: " . number_format($ipk, 2) . "\n";

    $updateIPK = "UPDATE mahasiswa SET ipk = '$ipk' WHERE nim = '$nim'";
    if ($connection->query($updateIPK) === TRUE) {
        echo "✅ IPK mahasiswa $nim berhasil diperbarui menjadi: " . number_format($ipk, 2) . "\n";
    } else {
        echo "❌ Error updating IPK: " . $connection->error;
    }
}

$connection->close();

?>
