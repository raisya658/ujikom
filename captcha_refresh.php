<?php
// Buat ngecek status login user lewat session; kalau belum login, tolak aksesnya (error 403)
session_start();
if (!isset($_SESSION['user_email'])) {
    http_response_code(403);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Bikin angka acak baru buat soal captcha dan timpa nilai lama di session
$_SESSION['captcha_num1'] = rand(1, 10);
$_SESSION['captcha_num2'] = rand(1, 10);

// Kirim balik angka barunya ke frontend dalam format JSON supaya form nggak ke-refresh
header('Content-Type: application/json');
echo json_encode([
    'num1' => $_SESSION['captcha_num1'],
    'num2' => $_SESSION['captcha_num2'],
]);