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

// =================================================================
// FUNGSI LOG AUDIT (DIPERBARUI)
// =================================================================
function log_audit($action, $table_name = null, $record_id = null, $old_value = null, $new_value = null) {
    global $mysqli;
    
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
    
    // PERBAIKAN: Hasilkan timestamp di PHP, bukan di database
    $timestamp = date('Y-m-d H:i:s');
    
    $old_value_str = is_array($old_value) || is_object($old_value) ? json_encode($old_value) : $old_value;
    $new_value_str = is_array($new_value) || is_object($new_value) ? json_encode($new_value) : $new_value;

    // PERBAIKAN: Tambahkan kolom 'timestamp' ke query INSERT
    $stmt = $mysqli->prepare("INSERT INTO audit_log (user_id, username, action, table_name, record_id, old_value, new_value, timestamp) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // PERBAIKAN: Tambahkan 's' untuk tipe data string timestamp
    $stmt->bind_param("isssisss", $user_id, $username, $action, $table_name, $record_id, $old_value_str, $new_value_str, $timestamp);
    
    $stmt->execute();
    $stmt->close();
}

?>
