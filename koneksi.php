<?php
// File ini khusus untuk membuka koneksi ke database
// File lain (proses.php, data_gaji.php, dst) tinggal "pinjam" koneksi ini lewat require

$host     = 'localhost';
$username = 'root';
$password = '';
$database = 'db_slipgaji';

$koneksi = mysqli_connect($host, $username, $password, $database);

if (!$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}
