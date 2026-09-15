<?php
// Endpoint kecil khusus untuk me-refresh soal CAPTCHA lewat AJAX (fetch),
// supaya saat tombol refresh diklik, HANYA soal captcha yang berubah,
// form yang sudah diisi user (nama, gaji pokok, lembur, dst) tidak ikut hilang.
//
// Ini BUKAN fitur captcha baru -- hanya "pintu kecil" untuk mengganti angka
// captcha yang sudah ada di session, dipakai bersama oleh tambah_gaji.php.

session_start();

if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// buat soal captcha baru, timpa yang lama di session
$_SESSION['captcha_num1'] = rand(1, 10);
$_SESSION['captcha_num2'] = rand(1, 10);

header('Content-Type: application/json');
echo json_encode([
    'num1' => $_SESSION['captcha_num1'],
    'num2' => $_SESSION['captcha_num2'],
]);
