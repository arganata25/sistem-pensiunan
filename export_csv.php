<?php
require_once 'config.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Menyiapkan header untuk download file CSV
$filename = "data_pensiunan_" . date('Ymd') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Membuka output stream PHP
$output = fopen('php://output', 'w');

// Menulis header kolom ke file CSV
fputcsv($output, [
    'ID', 'Nama Lengkap', 'NIP', 'Tanggal Lahir', 'Tanggal Pensiun', 
    'Jabatan Terakhir', 'Unit Kerja Terakhir', 'Jenis Kelamin', 'Alamat', 
    'Nomor Telepon', 'Alamat Email', 'Pangkat/Golongan', 'TMT Golongan', 'TMT Jabatan'
]);

// Mengambil data dari database
// Jika ada pencarian, gunakan filter yang sama
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM pensiunan";
if (!empty($search_query)) {
    $search_query_safe = $mysqli->real_escape_string($search_query);
    $sql .= " WHERE nama_lengkap LIKE '%$search_query_safe%' OR nip LIKE '%$search_query_safe%'";
}
$sql .= " ORDER BY nama_lengkap ASC";

$result = $mysqli->query($sql);

// Menulis setiap baris data ke file CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$mysqli->close();
exit;
