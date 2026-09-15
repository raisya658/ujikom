<?php
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // Ambil data admin berdasarkan email
    $stmt = mysqli_prepare($koneksi, "SELECT * FROM admin WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user   = mysqli_fetch_assoc($result);

    // Cek apakah user ada dan password-nya cocok langsung (teks biasa)
    if ($user && $password === $user['password']) {

        $_SESSION['user_email']  = $email;
        $_SESSION['nama']        = 'Administrator';

        header('Location: data_gaji.php');
        exit;

    } else {
        $_SESSION['login_error'] = 'Email atau password salah.';
        header('Location: login.php');
        exit;
    }
}