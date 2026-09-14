<?php
session_start();
require 'koneksi.php'; 
// require = "ambil dan jalankan" file koneksi.php di sini
// supaya variabel $koneksi dari file itu bisa dipakai di file ini
// (require akan menghentikan program kalau filenya tidak ketemu, beda dengan include)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = $_POST['email'];
    $password = $_POST['password'];

    // ambil data karyawan dari database berdasarkan email yang diinput
    // mysqli_real_escape_string() = membersihkan input dari karakter berbahaya (mencegah SQL Injection)
    $email_aman = mysqli_real_escape_string($koneksi, $email);

    $query  = "SELECT * FROM karyawan WHERE email = '$email_aman'";
    $result = mysqli_query($koneksi, $query);
    // mysqli_query() = menjalankan perintah SQL, hasilnya ditampung di $result

    $user = mysqli_fetch_assoc($result);
    // mysqli_fetch_assoc() = mengambil 1 baris hasil query, dijadikan array asosiatif
    // contoh: $user['nama'], $user['email'], $user['password'], dst
    // kalau emailnya tidak ketemu di database, $user akan bernilai NULL

    // cek: apakah user ditemukan DAN passwordnya cocok
    if ($user && $password === $user['password']) {
        // $user artinya "kalau $user tidak NULL" (user ditemukan)
        // $password === $user['password'] artinya password yang diinput cocok dengan yang di database

        $_SESSION['user_email'] = $email;
        $_SESSION['karyawan_id'] = $user['id']; 
        // simpan juga id karyawan ke session
        // nanti dipakai slip_gaji.php untuk ambil data gaji milik karyawan ini

        header('Location: slip_gaji.php');
        exit;

    } else {
        // kalau user tidak ditemukan ATAU password salah
        $_SESSION['login_error'] = 'Email atau password salah.';
        header('Location: login.php');
        exit;
    }
}