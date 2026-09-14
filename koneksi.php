<?php
// File ini khusus untuk membuka koneksi ke database
// Nanti file lain (proses.php, slip_gaji.php) tinggal "pinjam" koneksi ini

$host     = 'localhost';     // alamat server database, biasanya localhost untuk lokal
$username = 'root';          // username default MySQL di XAMPP/Laragon
$password = '';              // password default MySQL di XAMPP/Laragon (kosong)
$database = 'db_slipgaji';   // nama database yang sudah kamu buat di phpMyAdmin

// mysqli_connect() = fungsi bawaan PHP untuk membuka koneksi ke MySQL
// urutan parameternya: host, username, password, nama_database
$koneksi = mysqli_connect($host, $username, $password, $database);

// cek apakah koneksi berhasil atau tidak
if (!$koneksi) {
    // kalau $koneksi bernilai false (gagal), hentikan program dan tampilkan pesan error
    die('Koneksi database gagal: ' . mysqli_connect_error());
    // mysqli_connect_error() = mengambil pesan error asli dari MySQL, supaya kelihatan penyebabnya
}
?>