<?php
require_once 'connection.php';
session_start();

header('Content-Type: application/json'); // Pastikan response berupa JSON

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ubah_password'])) {
    $username = $_SESSION['login']['username'];
    $password_lama = $_POST['password_lama'];
    $password_baru = $_POST['password_baru'];
    $konfirmasi_password = $_POST['konfirmasi_password'];

    $query = "SELECT password FROM login WHERE username = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        if (password_verify($password_lama, $hashed_password)) {
            if ($password_baru === $konfirmasi_password) {
                if (strlen($password_baru) < 6) {
                    $_SESSION['message'] = "Password baru harus minimal 6 karakter!";
                    $_SESSION['message_type'] = "error";
                     header("Location: ../dashboard/mahasiswa.php");
                    exit;
                }

                $new_hashed = password_hash($password_baru, PASSWORD_BCRYPT);
                $update = "UPDATE login SET password = ? WHERE username = ?";
                $stmt = $connection->prepare($update);
                $stmt->bind_param("ss", $new_hashed, $username);

                if ($stmt->execute()) {
                    $_SESSION['message'] = "Password berhasil diubah!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Gagal mengubah password. Coba lagi!";
                    $_SESSION['message_type'] = "warning";
                }
            } else {
                $_SESSION['message'] = "Konfirmasi password tidak cocok!";
                $_SESSION['message_type'] = "warning";
            }
        } else {
            $_SESSION['message'] = "Password lama salah!";
            $_SESSION['message_type'] = "warning";
        }
    } else {
        $_SESSION['message'] = "Akun tidak ditemukan!";
        $_SESSION['message_type'] = "warning";
    }

    $stmt->close();
    $connection->close();
    header("Location: ../dashboard/mahasiswa.php"); // atau ke halaman sesuai tempat modalnya
    exit;
}   
?>
