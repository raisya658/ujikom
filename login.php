<?php
session_start();

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css?v=2"/>
</head>
<body>

 <form method="POST" action="proses.php">
        <div class="header">
            <h2>Login</h2>
        </div>

        <?php if ($error): ?>
            <p style="color: red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="field">
            <label for="email">Email</label>
            <input type="text" id="email" name="email" placeholder="Masukkan email" required/>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Masukkan password" required/>
        </div>

        <div class="forgot"><a href="lupa-password.php">Lupa kata sandi</a></div>

        <button type="submit" class="btn">Login</button>
    </form>
</body>
</html>
