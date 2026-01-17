<?php
/**
 * File: logout.php
 * Deskripsi: Menghancurkan session dan mengeluarkan pengguna dari sistem.
 */

// Memulai session untuk bisa mengakses dan menghancurkannya
session_start();

// Memanggil file konfigurasi dan fungsi
require_once 'config.php';
require_once 'functions.php';

// Menghapus semua variabel session
$_SESSION = array();

// Menghancurkan session
session_destroy();

// Mengarahkan pengguna kembali ke halaman login
redirect('login.php');
?>