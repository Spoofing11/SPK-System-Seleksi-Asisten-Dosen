<?php
require_once 'connection.php';
session_start(); // Tambahkan jika belum

// IP admin yang diizinkan
$allowed_ips = ['114.10.29.110', '127.0.0.1', '::1']; // Tambahkan IP lain jika perlu

// Cek apakah user adalah admin atau memiliki IP yang diizinkan
if (!isset($_SESSION['login']) || $_SESSION['login']['role'] !== 'admin') {
    if (empty($_SERVER['REMOTE_ADDR']) || !in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
        http_response_code(403);
        echo "403 Forbidden - Akses tidak diizinkan!";
        exit();
    }
}

// ------- OPSIONAL: Hapus data lama terlebih dahulu -------
// mysqli_query($connection, "DELETE FROM mahasiswa");
// mysqli_query($connection, "DELETE FROM login");

// Daftar nama mahasiswa (100 nama)
$nama_mahasiswa = [ /* ... (nama-nama seperti sebelumnya) ... */ 
     "Ahmad Fadli", "Budi Santoso", "Citra Dewi", "Dian Pratama", "Eka Saputra",
    "Fajar Maulana", "Gita Permata", "Hadi Setiawan", "Indah Lestari", "Joko Susilo",
    "Karina Putri", "Lukman Hakim", "Maya Sari", "Nugroho Aditya", "Oktaviani Rahma",
    "Putri Maharani", "Qori Annisa", "Rizki Kurniawan", "Sari Melati", "Taufik Hidayat",
    "Umar Zain", "Vina Anggraini", "Wawan Setiawan", "Xenia Kartika", "Yusuf Hamzah",
    "Zahra Ayu", "Abdul Rahman", "Bella Safitri", "Chandra Wijaya", "Dewi Ayu",
    "Erlangga Saputra", "Farah Hanum", "Gilang Ramadhan", "Hesti Ananda", "Ikhsan Maulana",
    "Juwita Dewi", "Khairul Anwar", "Larasati Pertiwi", "Marlina Sari", "Naufal Hakim",
    "Omar Faruq", "Prasetyo Adi", "Qisthi Hanifah", "Rifqi Pratama", "Salsabila Putri",
    "Teguh Wibowo", "Ulya Fitri", "Vito Rahman", "Winda Kurnia", "Xaverius Bayu",
    "Yulia Rahmadani", "Zainal Abidin", "Adi Nugroho", "Bintang Permadi", "Cindy Melinda",
    "Dian Kusuma", "Edo Saputro", "Faisal Hanif", "Gina Permana", "Haryo Wibowo",
    "Irfan Kurniawan", "Jihan Maulida", "Kevin Aditya", "Lina Wijayanti", "Mega Puspita",
    "Nadira Salsabila", "Oscar Pratama", "Pandu Maulana", "Qomar Zulfikar", "Rahmat Hidayat",
    "Siti Nurhaliza", "Teddy Setiawan", "Umi Salamah", "Vega Andika", "Wahyu Pradana",
    "Xenia Indriani", "Yohan Aditya", "Zaki Rahman", "Aisyah Putri", "Bagas Saputra",
    "Cahyo Nugroho", "Della Rizky", "Elang Pradipta", "Ferry Irawan", "Gita Savitri",
    "Handoko Wijaya", "Iskandar Zulkarnain", "Jamaluddin Malik", "Kristina Dewi", "Lukito Dwi",
    "Mila Kusuma", "Naufal Rizky", "Ovi Andriana", "Pramudya Wijaya", "Qayyum Fahreza",
    "Rizal Hakim", "Shinta Paramitha", "Tirta Budi", "Udin Saputra", "Vera Lestari"
];

// Daftar kota dan alamat
$kota_kelahiran = ["Jakarta", "Bogor", "Depok", "Tangerang", "Bekasi"];
$alamat_jabodetabek = [
    "Jl. Sudirman, Jakarta Pusat", "Jl. Thamrin, Jakarta Pusat", "Jl. Kemang, Jakarta Selatan",
    "Jl. Margonda, Depok", "Jl. Juanda, Bogor", "Jl. BSD Raya, Tangerang",
    "Jl. Ahmad Yani, Bekasi", "Jl. Ciputat, Tangerang Selatan", "Jl. Cileungsi, Bogor",
    "Jl. Kebon Jeruk, Jakarta Barat"
];

// Mulai insert data mahasiswa
for ($i = 1; $i <= 100; $i++) {
    // Acak angkatan: 21 atau 22
    $angkatan = rand(21, 22);
    $prefix_nim = $angkatan . "10114022"; // contoh: 2110114022

    // Generate NIM unik (2 digit belakang)
    $nim = $prefix_nim . str_pad($i, 2, '0', STR_PAD_LEFT); // contoh: 211011402201

    // Ambil nama berdasarkan index
    $nama = $nama_mahasiswa[$i - 1];

    // Data lainnya
    $jenis_kelamin = ($i % 2 == 0) ? "Laki-laki" : "Perempuan";
    $kota_lahir = $kota_kelahiran[array_rand($kota_kelahiran)];
    $tanggal_kelahiran = "2000-" . str_pad(rand(1, 12), 2, '0', STR_PAD_LEFT) . "-" . str_pad(rand(1, 28), 2, '0', STR_PAD_LEFT);
    $alamat = $alamat_jabodetabek[array_rand($alamat_jabodetabek)];
    $program_studi = "Teknik Informatika";
    $semester = rand(4, 6);
    $gmail = strtolower(str_replace(" ", "", $nama)) . "@gmail.com";
    $no_handphone = "08965698" . str_pad($i, 2, '0', STR_PAD_LEFT);

    // Password: unpam#6 digit terakhir NIM
    $password_default = "unpam#" . substr($nim, -6);
    $password_hashed = password_hash($password_default, PASSWORD_DEFAULT);

    // Insert ke tabel mahasiswa
    $query_mahasiswa = "INSERT IGNORE INTO mahasiswa (nim, nama, jenis_kelamin, kota_kelahiran, tanggal_kelahiran, alamat, program_studi, semester, gmail, no_handphone) 
                        VALUES ('$nim', '$nama', '$jenis_kelamin', '$kota_lahir', '$tanggal_kelahiran', '$alamat', '$program_studi', '$semester', '$gmail', '$no_handphone')";
    mysqli_query($connection, $query_mahasiswa);

    // Insert ke tabel login
    $query_login = "INSERT IGNORE INTO login (username, password, role, nim) 
                    VALUES ('$nim', '$password_hashed', 'mahasiswa', '$nim')";
    mysqli_query($connection, $query_login);
}

echo "✅ 100 Data Mahasiswa berhasil disimpan dengan NIM awalan 21 atau 22!";
?>
