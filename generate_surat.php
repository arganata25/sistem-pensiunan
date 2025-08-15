<?php
require_once 'config.php';
define('FPDF_FONTPATH', 'fpdf/font/');
require_once 'fpdf/fpdf.php';

// Keamanan: Pastikan pengguna sudah login dan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
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

// Buat class PDF kustom untuk Kop Surat
class PDF_Surat extends FPDF
{
    // Page header (Kop Surat)
    function Header()
    {
        // =================================================================
        // PERBAIKAN: Menggunakan path logo Pemkab yang baru
        // =================================================================
        $logo_path = 'assets/logo_pemkab.png'; 
        if (file_exists($logo_path)) {
            $this->Image($logo_path, 20, 8, 25);
        }
        
        $this->SetFont('Times','B',14);
        $this->Cell(0,6,'PEMERINTAH KABUPATEN TULUNGAGUNG',0,1,'C');
        $this->SetFont('Times','B',18);
        $this->Cell(0,8,'DINAS LINGKUNGAN HIDUP',0,1,'C');
        $this->SetFont('Times','',10);
        $this->Cell(0,5,'Jl. Sultan Agung III No.7, Ketanon, Kec. Kedungwaru',0,1,'C');
        $this->Cell(0,5,'Kabupaten Tulungagung, Jawa Timur 66226',0,1,'C');
        
        $this->SetLineWidth(1);
        $this->Line(10, 38, 200, 38);
        $this->SetLineWidth(0.2);
        $this->Line(10, 37, 200, 37);
        
        $this->Ln(12);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo(),0,0,'C');
    }
}

// =================================================================
// MULAI MEMBUAT DOKUMEN PDF
// =================================================================

$pdf = new PDF_Surat('P','mm','A4');
$pdf->AddPage();

// Judul Surat
$pdf->SetFont('Times','BU',12);
$pdf->Cell(0,7,'SURAT KETERANGAN PENSIUN',0,1,'C');
$pdf->SetFont('Times','',11);
$pdf->Cell(0,5,'Nomor: 800.166 / .......................... / 39.01 / ' . date('Y'),0,1,'C');
$pdf->Ln(10);

// Isi Surat
$pdf->MultiCell(0,6,'Yang bertanda tangan di bawah ini:',0,'L');
$pdf->Ln(2);
$pdf->Cell(15); $pdf->Cell(50,6,'Nama',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6,'..................................................................',0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'NIP',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6,'..................................................................',0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'Pangkat/Golongan',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6,'..................................................................',0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'Jabatan',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6,'Kepala Dinas Lingkungan Hidup',0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'Instansi',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6,'Dinas Lingkungan Hidup Kabupaten Tulungagung',0,1);
$pdf->Ln(5);

$pdf->MultiCell(0,6,'Dengan ini menerangkan dengan sesungguhnya bahwa:',0,'L');
$pdf->Ln(2);
$pdf->Cell(15); $pdf->Cell(50,6,'Nama',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6, $pensiunan['nama_lengkap'],0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'NIP',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6, $pensiunan['nip'],0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'Pangkat/Gol. Terakhir',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6, $pensiunan['pangkat_golongan'] ?: '-',0,1);
$pdf->Cell(15); $pdf->Cell(50,6,'Jabatan Terakhir',0,0); $pdf->Cell(5,6,':',0,0); $pdf->Cell(0,6, $pensiunan['jabatan_terakhir'] ?: '-',0,1);
$pdf->Ln(5);

$tgl_pensiun_indo = format_tanggal_indonesia($pensiunan['tanggal_pensiun']);
$pdf->MultiCell(0,6,'Adalah benar Pegawai Negeri Sipil pada Dinas Lingkungan Hidup Kabupaten Tulungagung yang telah memasuki masa purna tugas (pensiun) terhitung mulai tanggal ' . $tgl_pensiun_indo . '.',0,'J');
$pdf->Ln(5);
$pdf->MultiCell(0,6,'Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.',0,'L');
$pdf->Ln(15);

// Tanda Tangan
$pdf->Cell(110);
$pdf->Cell(0,6,'Tulungagung, ' . format_tanggal_indonesia(date('Y-m-d')),0,1,'L');
$pdf->Cell(110);
$pdf->Cell(0,6,'Kepala Dinas Lingkungan Hidup',0,1,'L');
$pdf->Ln(20);
$pdf->Cell(110);
$pdf->SetFont('Times','BU',11);
$pdf->Cell(0,6,'..................................................................',0,1,'L');
$pdf->SetFont('Times','',11);
$pdf->Cell(110);
$pdf->Cell(0,6,'NIP. ................................................................',0,1,'L');

// Output PDF untuk di-download
$pdf->Output('D', 'Surat_Keterangan_Pensiun_' . $pensiunan['nip'] . '.pdf');
exit;
