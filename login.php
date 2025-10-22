<?php
session_start();
require_once('helper/connection.php');

// Jika sudah login, alihkan ke dashboard sesuai role
if (isset($_SESSION['login'])) {
    $role = $_SESSION['login']['role'];



    $redirects = [
        'admin' => '/dashboard/index.php',
        'mahasiswa' => '/dashboard/mahasiswa.php',
        'dosen' => '/dashboard/dosen.php',
        'koordinator' => '/dashboard/koordinator_dosen.php'
    ];

    if (isset($redirects[$role])) {
        header("Location: " . $redirects[$role]);
        exit();
    }

    header("Location: /login.php"); // Role tidak valid
    exit();
}



// Proses login jika form disubmit

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = trim(filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING));
    $role = trim(filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING));


    // Validasi input
    if (empty($username) || empty($password) || empty($role)) {
        $_SESSION['message'] = "Username, password, dan role tidak boleh kosong!";
        $_SESSION['message_type'] = "warning";
        header('Location: login.php');
        exit();
    } else {
        // Menggunakan prepared statement untuk keamanan
        $sql = "SELECT * FROM login WHERE (username = ? 
        OR (role = 'dosen' AND nidn = ?) 
        OR (role = 'mahasiswa' AND nim = ?)) 
        AND role = ? LIMIT 1";

        if ($stmt = $connection->prepare($sql)) {
            $stmt->bind_param("ssss", $username, $username, $username, $role);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            die("Error dalam query login: " . $connection->error);
        }


        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $hashed_password = $row['password'];

            // Verifikasi password menggunakan password_verify()
            if (password_verify($password, $hashed_password)) {
                // Simpan data login ke session
                $_SESSION['login'] = [
                    'username' => $row['username'],
                    'role' => $row['role'],
                ];

                // Simpan data tambahan sesuai role
                if ($role === 'dosen') {
                    $_SESSION['login']['nidn'] = $row['nidn'];
                    $_SESSION['login']['nama_dosen'] = $row['nama_dosen'];
                } elseif ($role === 'mahasiswa') {
                    $_SESSION['login']['nim'] = $row['nim'];
                }

                // Redirect ke dashboard sesuai role
                $redirect = [
                    'admin' => 'dashboard/index.php',
                    'mahasiswa' => 'dashboard/mahasiswa.php',
                    'dosen' => 'dashboard/dosen.php',
                    'koordinator' => 'dashboard/koordinator_dosen.php',
                ];

                if (isset($redirect[$role])) {
                    header('Location: ' . $redirect[$role]);
                    exit();
                } else {
                    $_SESSION['message'] = "Role tidak valid!";
                    $_SESSION['message_type'] = "warning";
                }
            } else {
                $_SESSION['message'] = "Password salah!";
                $_SESSION['message_type'] = "warning";
            }
        } else {
            $_SESSION['message'] = "Username/NIM atau role tidak ditemukan!";
            $_SESSION['message_type'] = "warning";
        }

        // Redirect kembali ke login.php agar pesan bisa muncul di halaman login
        header('Location: login.php');
        exit();
    }
}


if (isset($_POST['register'])) {
    $username = mysqli_real_escape_string($connection, trim($_POST['reg_username']));
    $password = trim($_POST['reg_password']);
    $role = mysqli_real_escape_string($connection, trim($_POST['reg_role']));
    $nim_or_nidn = mysqli_real_escape_string($connection, trim($_POST['reg_nim_or_nidn']));

    // Hash password dengan bcrypt
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Cek apakah username sudah ada di login
    $stmt = $connection->prepare("SELECT * FROM login WHERE username = ? OR nim = ? OR nidn = ?");
    $stmt->bind_param("sss", $username, $nim_or_nidn, $nim_or_nidn);
    $stmt->execute();
    $result_check = $stmt->get_result();

    if ($result_check->num_rows > 0) {
        $_SESSION['message'] = "Username sudah digunakan!";
        $_SESSION['message_type'] = "warning";
        header("Location: login.php");
        exit();
    } else {
        // Cek dan validasi khusus untuk mahasiswa
        if ($role === 'mahasiswa') {
            // VALIDASI NIM MAHASISWA UNPAM 
            $angkatan = substr($nim_or_nidn, 0, 2); // misal: "25" untuk 2025
            $kode_prodi = substr($nim_or_nidn, 2, 6); // misal: "101140"

            if (!in_array($angkatan, ['20', '21', '22']) || $kode_prodi !== '101140') {
                $_SESSION['message'] = "NIM tidak valid! Masukan Nim Dengan Benar";
                $_SESSION['message_type'] = "warning";
                header("Location: login.php");
                exit();
            }

            // Lanjutkan cek/insert mahasiswa
            $cek_nim = $connection->prepare("SELECT * FROM mahasiswa WHERE nim = ?");
            $cek_nim->bind_param("s", $nim_or_nidn);
            $cek_nim->execute();
            $result_nim = $cek_nim->get_result();

            if ($result_nim->num_rows == 0) {
                $stmt = $connection->prepare("INSERT INTO mahasiswa (nim, nama) VALUES (?, ?)");
                $stmt->bind_param("ss", $nim_or_nidn, $username);
                $stmt->execute();
            }
        } elseif ($role === 'dosen') {
            $cek_nidn = $connection->prepare("SELECT * FROM dosen WHERE nidn = ?");
            $cek_nidn->bind_param("s", $nim_or_nidn);
            $cek_nidn->execute();
            $result_nidn = $cek_nidn->get_result();

            if ($result_nidn->num_rows == 0) {
                $stmt = $connection->prepare("INSERT INTO dosen (nidn, nama_dosen) VALUES (?, ?)");
                $stmt->bind_param("ss", $nim_or_nidn, $username);
                $stmt->execute();
            }
        }

        // Simpan ke login
        $sql_login = "INSERT INTO login (username, password, role, " . ($role === 'mahasiswa' ? 'nim' : 'nidn') . ") 
                      VALUES (?, ?, ?, ?)";
        $stmt = $connection->prepare($sql_login);
        $stmt->bind_param("ssss", $username, $hashed_password, $role, $nim_or_nidn);

        if ($stmt->execute()) {
            $_SESSION['message'] = "Registrasi berhasil! Silakan login.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Registrasi gagal: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
    }

    // Tutup statement
    $stmt->close();

    // Redirect ke login
    header("Location: login.php");
    exit();
}


// lupa password 
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["reset_password"])) {
    $nim_nidn = $_POST["nim_nidn"];
    $new_password = $_POST["new_password"];
    $confirm_password = $_POST["confirm_password"];

    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = "Password baru dan konfirmasi password tidak cocok!";
        $_SESSION['message_type'] = "error";
        header('Location: login.php'); // arahkan ke halaman form reset lagi
        exit;
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);


        // Cek apakah NIM/NIDN ada di database
        $query = "SELECT * FROM login WHERE nim = ? OR nidn = ?";
        $stmt = $connection->prepare($query);
        if (!$stmt) {
            error_log("Query error: " . $connection->error);
            echo "<script>alert('Terjadi kesalahan, coba lagi nanti.');</script>";
        }

        $stmt->bind_param("ss", $nim_nidn, $nim_nidn);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update password baru
            $update_query = "UPDATE login SET password = ? WHERE nim = ? OR nidn = ?";
            $update_stmt = $connection->prepare($update_query);
            if (!$update_stmt) {
                die("Update query error: " . $connection->error);
            }
            $update_stmt->bind_param("sss", $hashed_password, $nim_nidn, $nim_nidn);

            if ($update_stmt->execute()) {
                $_SESSION['message'] = "Password berhasil direset! Silakan login dengan password baru.";
                $_SESSION['message_type'] = "success";
                header('Location: login.php');
                exit();
            } else {
                $_SESSION['message'] = "Gagal mengupdate password!";
                $_SESSION['message_type'] = "danger";
                header('Location: login.php');
                exit();
            }
        } else {
            $_SESSION['message'] = "NIM atau NIDN tidak ditemukan!";
            $_SESSION['message_type'] = "warning";
            header('Location: login.php');
            exit();
        }
    }
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Login &mdash; Universitas Pamulang</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="icon" type="image/x-icon" href="./assets/img/favicon/logo.png">

    <!-- Tambahkan Bootstrap Icons jika belum -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <!-- Tambahkan Bootstrap Icons jika belum -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Background -->
    <style>
        body {
            background-image: url('./assets/img/unpam-1.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }

        @media (max-width: 768px) {
            body {
                background-attachment: scroll;
                /* Mengubah menjadi scroll untuk menghindari masalah pada perangkat mobile */
            }
        }


        @media (max-width: 768px) {
            .modal-dialog {
                max-width: 90%;
                /* Menyesuaikan lebar modal pada layar kecil */
            }
        }


        /* Responsif untuk layar kecil */
        @media (max-width: 768px) {
            body {
                background-size: cover;
                background-position: center 50px;
                /* Geser sedikit ke bawah */
            }
        }

        /* Modal Responsif */
        @media (max-width: 576px) {
            .modal-dialog {
                max-width: 90%;
                /* Modal lebih kecil di layar kecil */
                margin: 10px auto;
                /* Centang modal */
            }

            .modal-content {
                border-radius: 10px;
                /* Sudut lebih membulat pada modal */
            }

            .modal-header .close {
                font-size: 1.5rem;
                /* Memperbesar tombol close agar lebih mudah ditekan di HP */
            }

            .modal-footer .btn {
                width: 100%;
                /* Tombol mengisi lebar modal */
            }

            .modal-body img {
                width: 60%;
                /* Sesuaikan gambar agar tidak terlalu besar di layar kecil */
                height: auto;
            }
        }

        /* Untuk modal di layar kecil */
        @media (max-width: 768px) {
            .modal-dialog {
                max-width: 90%;
                /* Lebar maksimal modal agar lebih kecil pada ponsel */
            }

            .modal-header,
            .modal-footer {
                padding: 10px 15px;
                /* Menyesuaikan padding agar tidak terlalu besar */
            }

            .modal-body {
                padding: 20px 15px;
                /* Menyesuaikan padding modal body */
            }

            .btn-block {
                width: 100%;
                /* Membuat tombol lebih lebar pada layar kecil */
            }
        }

        /* Mengatur gambar logo agar lebih responsif */
        .login-brand img {
            max-width: 100%;
            height: auto;
            box-shadow: 0px 4px 15px rgb(185, 189, 190);
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }

        /* Agar kolom form responsif pada ukuran layar kecil */
        @media (max-width: 768px) {
            .login-form {
                width: 90%;
                /* Mengurangi lebar form untuk tampilan mobile */
                padding: 15px;
            }

            .card-header img {
                height: 30px;
            }

            .btn-lg {
                width: 100%;
                /* Tombol login lebar penuh di mobile */
            }
        }



        /* Sesuaikan font size pada elemen penting */
        @media (max-width: 480px) {
            .text-h5 {
                font-size: 1.2rem;
                /* Menyesuaikan ukuran font pada header */
            }

            .btn-link {
                font-size: 0.9rem;
                /* Mengurangi ukuran font pada tombol link */
            }
        }
    </style>
</head>

<body>
    <?php if (isset($_SESSION['message'])) : ?>
        <style>
            .custom-alert {
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 15px 20px;
                border-radius: 5px;
                min-width: 300px;
                text-align: center;
                font-weight: bold;
                box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
                z-index: 1050;
                opacity: 1;
                transition: opacity 0.5s ease-in-out;
            }

            .success {
                background-color: #28a745;
                color: white;
            }

            .error {
                background-color: #dc3545;
                color: white;
            }

            .warning {
                background-color: #ffc107;
                color: black;
            }

            .info {
                background-color: #17a2b8;
                color: white;
            }
        </style>

        <div id="alertBox" class="custom-alert <?= $_SESSION['message_type']; ?>">
            <?= $_SESSION['message']; ?>
        </div>

        <script>
            setTimeout(() => {
                let alertBox = document.getElementById('alertBox');
                if (alertBox) {
                    alertBox.style.opacity = "0";
                    setTimeout(() => alertBox.remove(), 500);
                }
            }, 3000);
        </script>

    <?php unset($_SESSION['message'], $_SESSION['message_type']);
    endif; ?>





    <div id="app">
        <section class="section">
            <div class="container mt-5">
                <div class="row">
                    <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">

                        <body style="background-color:rgba(112, 128, 145); margin: 0; padding: 0;">
                            <div class="login-brand"
                                style="text-align: center; margin-bottom: 20px;  padding: 20px; border-radius: 10px;">
                                <!-- <img src="https://semesterantara.unpam.ac.id/pluginfile.php?file=%2F1%2Ftheme_edumy%2Fheaderlogo1%2F1721720263%2Flogo%20LMS%20new.png"
                                    alt="logo"
                                    style="width: 100%; max-width: 900px; height: auto; box-shadow: 0px 4px 15px rgb(185, 189, 190); padding: 10px;  border: 1px solid #ccc; border-radius: 8px;"> -->
                            </div>

                            <div class="login-form" style="background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(2px); padding: 20px; border-radius: 9px; box-shadow: 0px 4px 10px rgba(190, 187, 187, 0.77,); width: 350px; margin: 0 auto;">
                                <div class="card card-primary" style="background-color: rgba(255, 255, 255, 0.2); backdrop-filter: blur(0px);">

                                    <div class="card-header" style="display: grid; grid-template-columns: auto 1fr auto; align-items: center; text-align: center;">
                                        <img src="./assets/img/sasmita_logos.png" alt="Logo Kiri" style="height: 40px;">
                                        <span class="text-h5" style="letter-spacing: 2px; font-weight: 500;">LOGIN</span>
                                        <img src="./assets/img/Unpam_Logos.png" alt="Logo Kanan" style="height: 40px;">
                                    </div>



                                    <div class="card-body">
                                        <form method="POST" action="" class="needs-validation" novalidate="" autocomplete="off">
                                            <div class="form-group">
                                                <label for="username" style="font-weight: bold;">Nim / Nidn</label>
                                                <input id="username" type="text" class="form-control" name="username" required autofocus>
                                            </div>
                                            <div class="form-group">
                                                <label for="password" style="font-weight: bold;">Password</label>
                                                <div class="input-group">
                                                    <input id="password" type="password" class="form-control" name="password" required>
                                                    <div class="input-group-append">
                                                        <span class="input-group-text">
                                                            <i class="bi bi-eye" id="togglePassword" style="cursor: pointer;"></i>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="form-group">
                                                <label for="role" style="font-weight: bold;">Role</label>
                                                <select id="role" name="role" class="form-control" required>
                                                    <option value="" selected disabled>Login Sebagai</option>
                                                    <option value="admin">Administrator</option>
                                                    <option value="koordinator">Koordinator Dosen</option>
                                                    <option value="mahasiswa">Mahasiswa</option>
                                                    <option value="dosen">Dosen</option>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <button name="submit" type="submit" class="btn btn-primary btn-lg btn-block">
                                                    Login
                                                </button>
                                            </div>
                                        </form>
                                        <div class="text-center">
                                            <button class="btn btn-link" data-toggle="modal" data-target="#registerModal" style="font-weight: bold; color: black;">Registrasi</button>
                                            |
                                            <button class="btn btn-link" data-toggle="modal" data-target="#forgotPasswordModal" style="font-weight: bold; color: black;">Lupa Password?</button>
                                        </div>


                                    </div>
                                </div>
                                <div class="simple-footer text-center">
                                    Copyright &copy; Unknown
                                </div>
                            </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
    <!-- Modal Lupa Password -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" role="dialog" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel">Lupa Password</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="login.php" autocomplete="off">
                        <input type="hidden" name="reset_password" value="1">
                        <div class="form-group">
                            <label for="nim_nidn">Masukkan NIM atau NIDN</label>
                            <input type="text" class="form-control" name="nim_nidn" placeholder="Masukkan NIM atau NIDN" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Password Baru</label>
                            <input type="password" class="form-control" name="new_password" placeholder="Masukkan Password Baru" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Konfirmasi Password</label>
                            <input type="password" class="form-control" name="confirm_password" placeholder="Konfirmasi Password Baru" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- Modal Registrasi -->
    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="registerModalLabel">Registrasi</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="reg_username">Username</label>
                            <input type="text" class="form-control" id="reg_username" name="reg_username" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_password">Password</label>
                            <input type="password" class="form-control" id="reg_password" name="reg_password" required>
                        </div>
                        <div class="form-group">
                            <label for="reg_role">Role</label>
                            <input type="text" class="form-control" value="Mahasiswa" readonly>
                            <input type="hidden" name="reg_role" value="mahasiswa">
                        </div>

                        <div class="form-group">
                            <label for="reg_nim_or_nidn">NIM (Mahasiswa) / NIDN (Dosen)</label>
                            <input type="text" class="form-control" id="reg_nim_or_nidn" name="reg_nim_or_nidn" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                        <button type="submit" name="register" class="btn btn-primary">Registrasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Modal Pengumuman
    <div class="modal fade" id="pengumumanModal" tabindex="-1" aria-labelledby="pengumumanLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold d-flex align-items-center" id="pengumumanLabel">
                        <i class="fas fa-bullhorn mr-2"></i> System Introduction
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/1436/1436664.png" alt="Announcement" class="mb-3" width="80">
                    <h5 class="font-weight-bold">System Seleksi Asisten Dosen</h5>
                    <p class="text-muted">System ini khusus untuk mahasiswa <strong>Universitas Pamulang</strong> semester <strong>4 - 6 (Kelas Karyawan)</strong></p>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i> Harap login menggunakan <strong>NIM / NIDN</strong>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-center">
                    <button type="button" class="btn btn-primary btn-lg px-4" data-dismiss="modal">
                        <i class="fas fa-check-circle"></i> Got it!
                    </button>
                </div>
            </div>
        </div>
    </div> -->


    <!-- jQuery harus sebelum Bootstrap -->
    <script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#pengumumanModal').modal('show');

            // Toggle password visibility
            $("#togglePassword").click(function() {
                var password = $("#password");
                var icon = $(this);
                if (password.attr("type") === "password") {
                    password.attr("type", "text");
                    icon.removeClass("bi-eye").addClass("bi-eye-slash");
                } else {
                    password.attr("type", "password");
                    icon.removeClass("bi-eye-slash").addClass("bi-eye");
                }
            });
        });
    </script>
</body>

</html>