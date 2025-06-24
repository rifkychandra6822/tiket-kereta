<?php
// Koneksi database
$conn = new mysqli('localhost', 'root', '', 'tiket_kereta');
if ($conn->connect_error) die('Koneksi gagal: ' . $conn->connect_error);
