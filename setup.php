<?php
// --- Konfigurasi Database ---
$servername = "localhost";
$username = "root";
$password = "";   
$dbname = "db_pensiun"; 

// --- Membuat Koneksi ---
$conn = new mysqli($servername, $username, $password);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke server database gagal: " . $conn->connect_error);
}

// --- Membuat Database ---
$sql_create_db = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql_create_db) === TRUE) {
    echo "Database '$dbname' berhasil dibuat atau sudah ada.<br>";
} else {
    die("Error saat membuat database: " . $conn->error);
}

// Pilih database yang baru dibuat
$conn->select_db($dbname);

echo "<h3>Memulai pembuatan tabel...</h3>";

// --- SQL untuk Membuat Tabel-Tabel ---

// 1. Tabel unit_kerja
$sql_unit_kerja = "
CREATE TABLE IF NOT EXISTS unit_kerja (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_unit VARCHAR(255) UNIQUE NOT NULL
)";
if ($conn->query($sql_unit_kerja) === TRUE) echo "Tabel 'unit_kerja' berhasil dibuat.<br>"; else echo "Error membuat tabel unit_kerja: " . $conn->error . "<br>";

// 2. Tabel jabatan
$sql_jabatan = "
CREATE TABLE IF NOT EXISTS jabatan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jabatan VARCHAR(255) UNIQUE NOT NULL
)";
if ($conn->query($sql_jabatan) === TRUE) echo "Tabel 'jabatan' berhasil dibuat.<br>"; else echo "Error membuat tabel jabatan: " . $conn->error . "<br>";

// 3. Tabel users
$sql_users = "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staf') NOT NULL DEFAULT 'staf'
)";
if ($conn->query($sql_users) === TRUE) echo "Tabel 'users' berhasil dibuat.<br>"; else echo "Error membuat tabel users: " . $conn->error . "<br>";

// 4. Tabel pensiunan
$sql_pensiunan = "
CREATE TABLE IF NOT EXISTS pensiunan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_lengkap VARCHAR(255) NOT NULL,
    nip VARCHAR(50) UNIQUE NOT NULL,
    tanggal_lahir DATE,
    tanggal_pensiun DATE,
    jabatan_terakhir VARCHAR(255),
    unit_kerja_terakhir VARCHAR(255),
    jenis_kelamin ENUM('Laki-laki', 'Perempuan'),
    alamat TEXT,
    nomor_telepon VARCHAR(20),
    alamat_email VARCHAR(100),
    pangkat_golongan VARCHAR(50),
    tmt_golongan DATE,
    tmt_jabatan DATE
)";
if ($conn->query($sql_pensiunan) === TRUE) echo "Tabel 'pensiunan' berhasil dibuat.<br>"; else echo "Error membuat tabel pensiunan: " . $conn->error . "<br>";

// 5. Tabel dokumen (DIPERBARUI DENGAN KOLOM 'kategori')
$sql_dokumen = "
CREATE TABLE IF NOT EXISTS dokumen (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pensiunan_id INT NOT NULL,
    nama_file_asli VARCHAR(255) NOT NULL,
    nama_file_unik VARCHAR(255) NOT NULL UNIQUE,
    jalur_file VARCHAR(255) NOT NULL,
    tanggal_unggah DATETIME NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Lainnya',
    FOREIGN KEY (pensiunan_id) REFERENCES pensiunan(id) ON DELETE CASCADE
)";
if ($conn->query($sql_dokumen) === TRUE) echo "Tabel 'dokumen' berhasil diperbarui/dibuat.<br>"; else echo "Error membuat tabel dokumen: " . $conn->error . "<br>";

// 6. Tabel audit_log
$sql_audit_log = "
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(100) NOT NULL,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(100),
    record_id INT,
    old_value TEXT,
    new_value TEXT,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
)";
if ($conn->query($sql_audit_log) === TRUE) echo "Tabel 'audit_log' berhasil dibuat.<br>"; else echo "Error membuat tabel audit_log: " . $conn->error . "<br>";

// 7. TABEL BARU: document_categories
$sql_doc_categories = "
CREATE TABLE IF NOT EXISTS document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL
)";
if ($conn->query($sql_doc_categories) === TRUE) echo "Tabel 'document_categories' berhasil dibuat.<br>"; else echo "Error membuat tabel document_categories: " . $conn->error . "<br>";


echo "<h3>Memasukkan data awal...</h3>";

// Tambah user admin default
$admin_user = 'admin';
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$sql_check_admin = "SELECT id FROM users WHERE username = '$admin_user'";
$result = $conn->query($sql_check_admin);
if ($result->num_rows == 0) {
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$admin_user', '$admin_pass', 'admin')");
    echo "User 'admin' berhasil ditambahkan. Password: admin123<br>";
} else {
    echo "User 'admin' sudah ada.<br>";
}

// Tambah data master unit kerja
$default_units = [
    'Bidang Penaatan & Peningkatan Kapasitas LH',
    'Bidang Tata Lingkungan',
    'Bidang Pengelolaan Sampah dan Limbah B3',
    'Bidang Pengendalian Pencemaran dan Kerusakan LH'
];
$stmt_unit = $conn->prepare("INSERT IGNORE INTO unit_kerja (nama_unit) VALUES (?)");
foreach ($default_units as $unit) {
    $stmt_unit->bind_param("s", $unit);
    $stmt_unit->execute();
}
$stmt_unit->close();
echo "Data master 'unit_kerja' berhasil ditambahkan/diperiksa.<br>";

// Tambah data master jabatan
$default_jabatan = [
    'Plt Kepala Dinas LH', 'Sekretaris', 'Kabid Penaatan&Peningkatan Kapasitas LH',
    'Pengendali Dampak Lingkungan Ahli Muda', 'Kabid Tata Lingkungan', 'Perencana Ahli Muda',
    'Kabid Pengelolaan Sampah&Limbah B3', 'Kabid Pengendalian Pencemaran & Kerusakan LH',
    'Penyuluh LH Ahli Muda', 'Pengawasan LH Ahli Muda', 'Pramu Kebersihan (Pemukiman Jalan Saluran&Selokan)',
    'Analis Lingkungan Hidup', 'Kasubbag Keuanga', 'Kepala Unit Pelaksana Teknis Laboratorium',
    'Pramu Kebersihan', 'Kepala Unit Pelaksana Teknis Pengelolaan Sampah', 'Pengawas Lapangan Petugas Kebersihan',
    'Bendahara', 'Kasubbag Umum dan Kepegawaian', 'Penyuluh Lingkungan Hidup',
    'Analis Perencanaan, Evaluasi dan Pelaporan.', 'Penelaah Dampak Lingkungan', 'Pengawas Lapangan Petugas Pertamanan',
    'Pengawas LH Ahli Pertama', 'Pengadministrasi Umum', 'Pemeliharaan Sarpras',
    'Pengadministrasi Keuangan', 'Pengadministrasi Perencanaan dan Program', 'Pranata Taman',
    'Juru Pungut Retribusi', 'Pramu Taman', 'Operator Alat Berat', 'Pramu Kebersihan/TPA',
    'Pengemudi', 'Pengendali Dampak Lingkungan Ahli Madya', 'Pranata Komputer Terampil',
    'Perencana Ahli Pertama', 'Arsiparis Ahli Pertama', 'Analis Hukum Ahli Pertama',
    'Penata Layanan Operasional', 'Pengadministrasi Perkantoran'
];
$stmt_jabatan = $conn->prepare("INSERT IGNORE INTO jabatan (nama_jabatan) VALUES (?)");
foreach ($default_jabatan as $jabatan) {
    $stmt_jabatan->bind_param("s", $jabatan);
    $stmt_jabatan->execute();
}
$stmt_jabatan->close();
echo "Data master 'jabatan' berhasil ditambahkan/diperiksa.<br>";

// DATA AWAL BARU: Kategori Dokumen
$default_doc_categories = [
    'SK Pensiun', 'KTP', 'Kartu Keluarga', 'NPWP', 'Buku Tabungan', 'Lainnya'
];
$stmt_cat = $conn->prepare("INSERT IGNORE INTO document_categories (category_name) VALUES (?)");
foreach ($default_doc_categories as $cat) {
    $stmt_cat->bind_param("s", $cat);
    $stmt_cat->execute();
}
$stmt_cat->close();
echo "Data master 'document_categories' berhasil ditambahkan/diperiksa.<br>";

echo "<hr><strong>Inisialisasi database selesai. Hapus atau ganti nama file 'setup.php' ini demi keamanan.</strong>";

$conn->close();
?>
