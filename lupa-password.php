<?php
session_start();

$error = $_SESSION['reset_error'] ?? '';
$success = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);

// versi CSS otomatis mengikuti kapan terakhir file diedit,
// supaya browser tidak pernah pakai cache lama lagi
$css_ver = @filemtime(__DIR__ . '/assets/style.css') ?: time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= $css_ver ?>"/>
    <!-- CSS darurat langsung ditulis di sini (inline), supaya tampilan
         PASTI center di tengah layar meskipun file style.css eksternal
         gagal ter-update / masih ke-cache oleh browser. -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body {
            min-height: 100vh !important;
            min-height: 100dvh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 20px !important;
            box-sizing: border-box !important;
        }
        form {
            width: 100% !important;
            max-width: 340px !important;
            margin: 0 !important;
        }
    </style>
</head>
<body class="auth-page">

 <form method="POST" action="proses_lupa.php">
        <div class="header">
            <h2>Reset Password</h2>
        </div>

        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color: green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" placeholder="Masukkan email" required/>
        </div>
        <div class="field">
            <label for="password">Password Baru</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password baru" required minlength="6"/>
        </div>
        <div class="field">
            <label for="password_confirm">Konfirmasi Password</label>
            <input type="password" id="password_confirm" name="password_confirm" placeholder="Ulangi password baru" required minlength="6"/>
        </div>

        <button type="submit" class="btn">Reset Password</button>

        <div class="forgot"><a href="login.php">Kembali ke Login</a></div>
    </form>
</body>
</html>