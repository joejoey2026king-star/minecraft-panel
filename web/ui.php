<?php
session_start();
header('Location: ' . (isset($_SESSION['logged_in']) ? 'worlds.php' : 'login.php'));
exit;
