<?php
// =================================================================
// CONFIG.PHP - FILE KONFIGURASI KONEKSI DATABASE
// =================================================================

// Mulai session di sini agar tersedia di semua halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Mengatur zona waktu default ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');


// --- Definisikan Kredensial Database ---
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'db_pensiun');

// --- Buat Koneksi ke Database ---
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// --- Cek Koneksi ---
if ($mysqli === false || $mysqli->connect_error) {
    die("Error: Tidak dapat terhubung ke database.");
}

// --- Fungsi untuk mencatat log audit ---
function log_audit($action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    global $mysqli;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    $timestamp = date('Y-m-d H:i:s');
    
    $old_value_str = is_array($old_value) || is_object($old_value) ? json_encode($old_value) : $old_value;
    $new_value_str = is_array($new_value) || is_object($new_value) ? json_encode($new_value) : $new_value;

    $stmt = $mysqli->prepare("INSERT INTO audit_log (user_id, username, action, table_name, record_id, old_value, new_value, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssisss", $user_id, $username, $action, $table_name, $record_id, $old_value_str, $new_value_str, $timestamp);
    $stmt->execute();
    $stmt->close();
}

// =================================================================
// FUNGSI GLOBAL: Untuk memformat tanggal ke Bahasa Indonesia
// =================================================================
function format_tanggal_indonesia($date_string) {
    if (empty($date_string) || $date_string === '0000-00-00') {
        return '-';
    }
    $bulan = array (
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $split = explode('-', $date_string);
    if (count($split) != 3) {
        return '-';
    }
    return $split[2] . ' ' . $bulan[ (int)$split[1] ] . ' ' . $split[0];
}

?>
