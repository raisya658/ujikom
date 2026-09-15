<?php
session_start();

$error = $_SESSION['reset_error'] ?? '';
$success = $_SESSION['reset_success'] ?? '';
unset($_SESSION['reset_error'], $_SESSION['reset_success']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link rel="stylesheet" href="assets/style.css?v=2"/>
</head>
<body>

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