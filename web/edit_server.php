<?php
session_start();
header('Location: ' . (isset($_SESSION['logged_in']) ? 'settings.php' : 'login.php'));
exit;
