<?php
session_start(); // tetap perlu, supaya bisa baca $error yang dikirim dari proses.php lewat session

$error = $_SESSION['login_error'] ?? ''; // ambil pesan error (kalau ada), lalu langsung hapus biar gak nongol terus
unset($_SESSION['login_error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="assets/style.css"/> <!--untuk menyambungkan ke css -->
</head>
<body>
    
 <form method="POST" action="proses.php">
        <!-- method POST artinya data form dikirim ke server secara tersembunyi (tidak muncul di URL) -->
        <!-- action="" artinya data form dikirim ke halaman ini sendiri (login.php) -->
        <!--login form -->
        <div class="header">
            <h2>Login</h2>
        </div>
        <?php if ($error): ?>
  <!-- baris ini cuma muncul kalau variabel $error TIDAK kosong -->
  <p style="color: red;"><?= $error ?></p>
  <!-- <?= $error ?> artinya "cetak isi variabel $error di sini" -->
<?php endif; ?>
<!-- endif menutup blok if tadi -->
        <div class="field">
            <!--untuk memanggil css email -->
            <label for="email">Email</label>
            <!--untuk menambahkan tulisan di input email -->
            <input type="text" id="email" name="email" placeholder="namaperusahaan@gmail.com" required/>
        </div>
        <div class="field">
            <!--untuk memanggil css password -->
            <label for="password">Password</label>
            <!--untuk menambahkan tulisan di input password -->
            <input type="password" id="password" name="password" placeholder="..." required/>
        </div>
         <!--untuk memanggil css lupa password -->
        <div class="forgot"><a href="#">Lupa kata sandi</a></div>
        <!--untuk memanggil css button login -->
        <button type="submit" class="btn">Login</button>
    </form>
</body>
</html>