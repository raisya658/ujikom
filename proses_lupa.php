<?php
session_start();

// Sesuaikan koneksi database ini dengan punya kamu
$koneksi = new mysqli('localhost', 'root', '', 'db_slipgaji');

if ($koneksi->connect_error) {
    die('Koneksi database gagal: ' . $koneksi->connect_error);
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';

// Validasi dasar
if (!$email || !$password || !$password_confirm) {
    $_SESSION['reset_error'] = 'Semua field wajib diisi.';
    header('Location: lupa-password.php');
    exit;
}

if ($password !== $password_confirm) {
    $_SESSION['reset_error'] = 'Password dan konfirmasi tidak sama.';
    header('Location: lupa-password.php');
    exit;
}

if (strlen($password) < 6) {
    $_SESSION['reset_error'] = 'Password minimal 6 karakter.';
    header('Location: lupa-password.php');
    exit;
}

// Cek email ada di tabel mana: karyawan atau admin
$tabel_ditemukan = null;

foreach (['karyawan', 'admin'] as $tabel) {
    $stmt = $koneksi->prepare("SELECT id FROM `$tabel` WHERE email = ?");
    if (!$stmt) {
        die('Prepare gagal (SELECT ' . $tabel . '): ' . $koneksi->error);
    }
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $tabel_ditemukan = $tabel;
        break;
    }
}

if (!$tabel_ditemukan) {
    $_SESSION['reset_error'] = 'Email tidak ditemukan.';
    header('Location: lupa-password.php');
    exit;
}

// Update password di tabel yang sesuai (plain text, mengikuti sistem yang ada)
$update = $koneksi->prepare("UPDATE `$tabel_ditemukan` SET password = ? WHERE email = ?");
if (!$update) {
    die('Prepare gagal (UPDATE): ' . $koneksi->error);
}
$update->bind_param('ss', $password, $email);
$update->execute();

if ($update->affected_rows === 0) {
    $_SESSION['reset_error'] = 'Password gagal diubah. Coba lagi.';
    header('Location: lupa-password.php');
    exit;
}

$_SESSION['reset_success'] = 'Password berhasil diubah. Silakan login.';
header('Location: login.php');
exit;