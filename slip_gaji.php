<?php
session_start();
require 'koneksi.php';

// gerbang penjaga: kalau belum login, tendang ke login.php
if (!isset($_SESSION['user_email'])) {
    header('Location: login.php');
    exit;
}

// ==============================
// CAPTCHA
// ==============================
if (!isset($_SESSION['captcha_num1']) || isset($_GET['refresh_captcha'])) {
    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);

    if (isset($_GET['refresh_captcha'])) {
        header('Location: slip_gaji.php');
        exit;
    }
}

$captcha_pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['captcha_jawaban'])) {

    $jawaban_benar = $_SESSION['captcha_num1'] * $_SESSION['captcha_num2'];
    $jawaban_user  = (int) $_POST['captcha_jawaban'];

    if ($jawaban_user === $jawaban_benar) {
        $captcha_pesan = '<p style="color: green;">Captcha benar! Slip gaji terverifikasi.</p>';
    } else {
        $captcha_pesan = '<p style="color: red;">Captcha salah, silakan coba lagi.</p>';
    }

    $_SESSION['captcha_num1'] = rand(1, 10);
    $_SESSION['captcha_num2'] = rand(1, 10);
}

// ==============================
// AMBIL DATA KARYAWAN & GAJI
// ==============================
$karyawan_id = $_SESSION['karyawan_id'];

$query_karyawan  = "SELECT * FROM karyawan WHERE id = '$karyawan_id'";
$result_karyawan = mysqli_query($koneksi, $query_karyawan);
$karyawan        = mysqli_fetch_assoc($result_karyawan);

$query_gaji  = "SELECT * FROM gaji WHERE karyawan_id = '$karyawan_id'";
$result_gaji = mysqli_query($koneksi, $query_gaji);
$gaji        = mysqli_fetch_assoc($result_gaji);

// ==============================
// PERHITUNGAN GAJI
// ==============================
$gaji_pokok        = $gaji['gaji_pokok'];
$lembur            = $gaji['lembur'];
$pinjaman_karyawan = $gaji['pinjaman_karyawan'];

$total_penghasilan = $gaji_pokok + $lembur;
$total_potongan    = $pinjaman_karyawan;
$gaji_bersih       = $total_penghasilan - $total_potongan;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Slip Gaji</title>
    <link rel="stylesheet" href="assets/style.css"/>
</head>
<body>

    <div class="slip-container">
        <h2 class="slip-title">SLIP GAJI KARYAWAN</h2>
        <p class="slip-periode">Periode <?= htmlspecialchars($gaji['periode']) ?></p>

        <div class="field-baca">
            <label>Nama:</label>
            <div class="kotak"><?= htmlspecialchars($karyawan['nama']) ?></div>
        </div>
        <div class="field-baca">
            <label>NIK:</label>
            <div class="kotak"><?= htmlspecialchars($karyawan['nik']) ?></div>
        </div>
        <div class="field-baca">
            <label>Jabatan:</label>
            <div class="kotak"><?= htmlspecialchars($karyawan['jabatan']) ?></div>
        </div>

        <div class="dua-kolom">
            <div class="kolom">
                <div class="kolom-judul">PENGHASILAN</div>

                <div class="field-baca">
                    <label>Gaji pokok:</label>
                    <div class="kotak"><?= number_format($gaji_pokok, 0, ',', '.') ?></div>
                </div>
                <div class="field-baca">
                    <label>Lembur:</label>
                    <div class="kotak"><?= number_format($lembur, 0, ',', '.') ?></div>
                </div>
                <div class="field-baca">
                    <label>Total penghasilan:</label>
                    <div class="kotak"><?= number_format($total_penghasilan, 0, ',', '.') ?></div>
                </div>
            </div>

            <div class="kolom">
                <div class="kolom-judul">POTONGAN</div>

                <div class="field-baca">
                    <label>Pinjaman karyawan:</label>
                    <div class="kotak"><?= number_format($pinjaman_karyawan, 0, ',', '.') ?></div>
                </div>
                <div class="field-baca">
                    <label>Total potongan:</label>
                    <div class="kotak"><?= number_format($total_potongan, 0, ',', '.') ?></div>
                </div>
            </div>
        </div>

        <div class="field-baca gaji-bersih">
            <label>Gaji bersih:</label>
            <div class="kotak"><?= number_format($gaji_bersih, 0, ',', '.') ?></div>
        </div>

        <!-- form khusus untuk captcha + submit -->
        <form method="POST" action="slip_gaji.php">

            <div class="captcha-box">
                <span id="captcha-soal">
                    Captcha: <?= $_SESSION['captcha_num1'] ?> x <?= $_SESSION['captcha_num2'] ?>
                </span>
                <a href="slip_gaji.php?refresh_captcha=1" class="refresh-btn" title="Refresh captcha">&#8635;</a>
            </div>

            <input type="text" name="captcha_jawaban" class="captcha-input" placeholder="Masukkan hasil captcha" required>

            <?= $captcha_pesan ?>

            <button type="submit" class="btn-submit">Submit</button>

        </form>

        <!-- tombol cetak, di luar form captcha supaya tidak ikut ke-submit -->
        <button type="button" class="btn-cetak" onclick="window.print()">Cetak Slip</button>

    </div>

</body>
</html>