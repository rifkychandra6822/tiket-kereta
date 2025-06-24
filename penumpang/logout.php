<?php
// Logout Penumpang
session_start();
session_unset();
session_destroy();
header('Location: login.php');
exit;
