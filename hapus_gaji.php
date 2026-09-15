<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

$id = (int) ($_GET['id'] ?? 0);

if ($id > 0) {
    // prepared statement untuk DELETE, aman dari SQL Injection
    $stmt = mysqli_prepare($koneksi, "DELETE FROM gaji WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

header('Location: data_gaji.php');
exit;
