<?php
session_start();

$_SESSION = []; // kosongkan semua data session
session_destroy(); // hancurkan session-nya sepenuhnya

header('Location: login.php');
exit;
