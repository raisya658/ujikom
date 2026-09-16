<?php
session_start();

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

// versi CSS otomatis mengikuti kapan terakhir file diedit,
// supaya browser tidak pernah pakai cache lama lagi
$css_ver = @filemtime(__DIR__ . '/assets/style.css') ?: time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css?v=<?= $css_ver ?>"/>
    <!-- CSS darurat langsung ditulis di sini (inline), supaya tampilan login
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

        /* ============================
           TOGGLE LIHAT PASSWORD (ikon mata)
           ============================ */
        .password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrap input {
            padding-right: 44px; /* kasih ruang buat ikon mata di kanan */
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 6px;
            transform: translateY(-50%);
            width: 32px;
            height: 32px;
            background: none;
            border: none;
            border-radius: 50%;
            padding: 0;
            margin: 0;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9a9a9a;
            transition: background-color 0.15s, color 0.15s;
        }
        .toggle-password:hover,
        .toggle-password:focus-visible {
            background-color: rgba(0, 0, 0, 0.06);
            color: #555;
        }
        .toggle-password:focus-visible {
            outline: 2px solid rgb(110, 108, 217);
            outline-offset: 1px;
        }
        .toggle-password svg {
            width: 19px;
            height: 19px;
            display: block;
            stroke-width: 1.8;
        }
        .toggle-password .icon-eye-off {
            display: none;
        }
        .toggle-password.tampil .icon-eye {
            display: none;
        }
        .toggle-password.tampil .icon-eye-off {
            display: block;
        }
    </style>
</head>
<body class="auth-page">

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
            <div class="password-wrap">
                <input type="password" id="password" name="password" placeholder="Masukkan password" required/>
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Lihat password" title="Lihat password">
                    <!-- ikon mata terbuka (kondisi password disembunyikan) -->
                    <svg class="icon-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <!-- ikon mata dicoret (kondisi password ditampilkan) -->
                    <svg class="icon-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.6 21.6 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.6 21.6 0 0 1-2.94 4.06M14.12 14.12a3 3 0 1 1-4.24-4.24"></path>
                        <line x1="1" y1="1" x2="23" y2="23"></line>
                    </svg>
                </button>
            </div>
        </div>

        <div class="forgot"><a href="lupa-password.php">Lupa kata sandi</a></div>

        <button type="submit" class="btn">Login</button>
    </form>

    <script>
        // Toggle lihat/sembunyikan password dengan klik ikon mata
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput  = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const tersembunyi = passwordInput.type === 'password';
            passwordInput.type = tersembunyi ? 'text' : 'password';

            this.classList.toggle('tampil', tersembunyi);
            this.setAttribute('aria-label', tersembunyi ? 'Sembunyikan password' : 'Lihat password');
            this.setAttribute('title', tersembunyi ? 'Sembunyikan password' : 'Lihat password');
        });
    </script>
</body>
</html>