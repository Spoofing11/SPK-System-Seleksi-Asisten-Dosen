<?php
// Koneksi ke database
$conn = new mysqli("localhost", "root", "", "system_skripsi");

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Insert mahasiswa jika belum ada
$sql_mahasiswa = "INSERT INTO login (username, password, role, nidn, nim) 
                  SELECT m.nim, ?, 'mahasiswa', NULL, m.nim 
                  FROM mahasiswa m 
                  WHERE NOT EXISTS (SELECT 1 FROM login l WHERE l.username = m.nim)";

$stmt = $conn->prepare($sql_mahasiswa);
$hashed_pass_mahasiswa = password_hash("mahasiswa123", PASSWORD_BCRYPT);
$stmt->bind_param("s", $hashed_pass_mahasiswa);
$stmt->execute();
$stmt->close();

// Insert dosen jika belum ada
$sql_dosen = "INSERT INTO login (username, password, role, nidn, nim) 
              SELECT d.nidn, ?, 'dosen', d.nidn, NULL 
              FROM dosen d 
              WHERE NOT EXISTS (SELECT 1 FROM login l WHERE l.username = d.nidn)";

$stmt = $conn->prepare($sql_dosen);
$hashed_pass_dosen = password_hash("dosen123", PASSWORD_BCRYPT);
$stmt->bind_param("s", $hashed_pass_dosen);
$stmt->execute();
$stmt->close();

echo "Data mahasiswa dan dosen berhasil dimasukkan tanpa duplikasi!";

// Tutup koneksi
$conn->close();
?>
