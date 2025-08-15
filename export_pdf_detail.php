<?php
require_once 'config.php';

define('FPDF_FONTPATH', 'fpdf/font/');
require_once 'fpdf/fpdf.php'; // Memanggil library FPDF SETELAH path didefinisikan

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Validasi ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID Pensiunan tidak valid.");
}
$id = intval($_GET['id']);

// Ambil data pensiunan dari database
$result = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id");
if ($result->num_rows != 1) {
    die("Data pensiunan tidak ditemukan.");
}
$pensiunan = $result->fetch_assoc();
$mysqli->close();

// Fungsi bantu untuk menampilkan tanggal dengan aman
function display_date_pdf($date_string) {
    if (!empty($date_string) && $date_string !== '0000-00-00') {
        return date("d F Y", strtotime($date_string));
    }
    return '-';
}

// Membuat objek PDF
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->AddPage();

// Header Laporan
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'DETAIL DATA PENSIUNAN', 0, 1, 'C');
$pdf->Ln(10);

// Isi Laporan
$pdf->SetFont('Arial', '', 11);

// Fungsi untuk membuat baris data
function create_data_row($pdf, $label, $value) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(50, 8, $label, 0, 0);
    $pdf->Cell(5, 8, ':', 0, 0, 'C');
    $pdf->SetFont('Arial', '', 11);
    // Menggunakan MultiCell untuk menangani teks panjang seperti alamat
    $pdf->MultiCell(0, 8, $value, 0, 'L');
}

// Menampilkan data
create_data_row($pdf, 'Nama Lengkap', $pensiunan['nama_lengkap']);
create_data_row($pdf, 'NIP', $pensiunan['nip']);
create_data_row($pdf, 'Tanggal Lahir', display_date_pdf($pensiunan['tanggal_lahir']));
create_data_row($pdf, 'Tanggal Pensiun', display_date_pdf($pensiunan['tanggal_pensiun']));
create_data_row($pdf, 'Jenis Kelamin', $pensiunan['jenis_kelamin'] ?: '-');
create_data_row($pdf, 'Jabatan Terakhir', $pensiunan['jabatan_terakhir'] ?: '-');
create_data_row($pdf, 'Unit Kerja Terakhir', $pensiunan['unit_kerja_terakhir'] ?: '-');
create_data_row($pdf, 'Pangkat/Golongan', $pensiunan['pangkat_golongan'] ?: '-');
create_data_row($pdf, 'TMT Golongan', display_date_pdf($pensiunan['tmt_golongan']));
create_data_row($pdf, 'TMT Jabatan', display_date_pdf($pensiunan['tmt_jabatan']));
create_data_row($pdf, 'Nomor Telepon', $pensiunan['nomor_telepon'] ?: '-');
create_data_row($pdf, 'Alamat Email', $pensiunan['alamat_email'] ?: '-');
create_data_row($pdf, 'Alamat', $pensiunan['alamat'] ?: '-');

// Output PDF untuk di-download
$pdf->Output('D', 'Detail_Pensiunan_' . $pensiunan['nip'] . '.pdf');
exit;
