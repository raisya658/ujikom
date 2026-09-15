<?php
session_start();
require 'koneksi.php';

if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// Fungsi bantu format tanggal Indonesia
function formatIndoTanggal($tanggal) {
    if (!$tanggal) return '';
    $bulanIndo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $pecah = explode('-', $tanggal);
    if (count($pecah) === 3) {
        return $pecah . ' ' . ($bulanIndo[$pecah[1]] ?? $pecah) . ' ' . $pecah[0];
    }
    return $tanggal;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal_mulai   = $_POST['tanggal_mulai'] ?? '';
    $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

    // 1. Validasi wajib diisi
    if (empty($tanggal_mulai) || empty($tanggal_selesai)) {
        $_SESSION['error'] = 'Tanggal mulai dan tanggal selesai wajib diisi!';
        header('Location: data_gaji.php');
        exit;
    }

    // 2. Validasi tanggal selesai tidak boleh lebih awal dari tanggal mulai
    if ($tanggal_selesai < $tanggal_mulai) {
        $_SESSION['error'] = 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai!';
        header('Location: data_gaji.php');
        exit;
    }

    // [BAGIAN TUMPANG TINDIH SUDAH DIHAPUS DI SINI]

    // 3. Simpan ke tabel periode_gaji
    $insert_query = "INSERT INTO periode_gaji (tanggal_mulai, tanggal_selesai, status, is_active) VALUES (?, ?, 'Belum Digunakan', 1)";
    $stmt_ins = mysqli_prepare($koneksi, $insert_query);
    mysqli_stmt_bind_param($stmt_ins, 'ss', $tanggal_mulai, $tanggal_selesai);
    
    // Nonaktifkan is_active periode sebelumnya jika ingin periode baru langsung jadi aktif
    mysqli_query($koneksi, "UPDATE periode_gaji SET is_active = 0");

    if (mysqli_stmt_execute($stmt_ins)) {
        $new_periode_id = mysqli_insert_id($koneksi);
        mysqli_query($koneksi, "UPDATE periode_gaji SET is_active = 1 WHERE id = $new_periode_id");

        $_SESSION['success'] = 'Periode penggajian baru berhasil disimpan!';
    } else {
        $_SESSION['error'] = 'Gagal menyimpan periode penggajian.';
    }

    header('Location: data_gaji.php');
    exit;
}