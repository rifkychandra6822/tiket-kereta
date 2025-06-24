<?php
// Logout untuk semua role
session_start();
session_destroy();
header('Location: index.php');
