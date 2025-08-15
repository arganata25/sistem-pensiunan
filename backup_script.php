<?php
require_once 'config.php';

// Keamanan: Hanya 'admin' yang dapat menjalankan skrip ini.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak. Anda harus menjadi admin untuk melakukan backup.");
}

// Nama file backup
$backup_file = 'db_backup_' . DB_NAME . '_' . date("Y-m-d_H-i-s") . '.sql';

// Set header untuk download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($backup_file) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Buka output stream
$handle = fopen('php://output', 'w');

// Fungsi untuk menjalankan backup
function backup_tables($mysqli, $handle) {
    // Header file SQL
    fwrite($handle, "-- Sistem Pensiunan - Backup Database\n");
    fwrite($handle, "-- Host: " . DB_SERVER . "\n");
    fwrite($handle, "-- Database: " . DB_NAME . "\n");
    fwrite($handle, "-- Waktu Generate: " . date('Y-m-d H:i:s') . "\n");
    fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\nSET time_zone = \"+00:00\";\n\n");

    // Ambil semua tabel
    $tables = array();
    $result = $mysqli->query('SHOW TABLES');
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    // Loop melalui setiap tabel
    foreach ($tables as $table) {
        $result = $mysqli->query('SELECT * FROM ' . $table);
        $num_fields = $result->field_count;

        // Tulis struktur tabel (CREATE TABLE)
        fwrite($handle, 'DROP TABLE IF EXISTS `' . $table . '`;' . "\n");
        $row2 = $mysqli->query('SHOW CREATE TABLE ' . $table)->fetch_row();
        fwrite($handle, "\n\n" . $row2[1] . ";\n\n");

        // Tulis data (INSERT INTO)
        if ($result->num_rows > 0) {
            for ($i = 0; $i < $num_fields; $i++) {
                while ($row = $result->fetch_row()) {
                    $sql = 'INSERT INTO `' . $table . '` VALUES(';
                    for ($j = 0; $j < $num_fields; $j++) {
                        if (isset($row[$j])) {
                            $sql .= '"' . $mysqli->real_escape_string($row[$j]) . '"';
                        } else {
                            $sql .= 'NULL';
                        }
                        if ($j < ($num_fields - 1)) {
                            $sql .= ',';
                        }
                    }
                    $sql .= ");\n";
                    fwrite($handle, $sql);
                }
            }
        }
        fwrite($handle, "\n\n\n");
    }
}

// Jalankan fungsi backup
backup_tables($mysqli, $handle);

// Tutup koneksi dan handle
fclose($handle);
$mysqli->close();
exit;
