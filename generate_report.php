<?php
require_once 'config.php';
define('FPDF_FONTPATH', 'fpdf/font/');
require_once 'fpdf/fpdf.php';

// Keamanan: Pastikan pengguna sudah login dan admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Akses ditolak.");
}

// Ambil data filter dari POST
$filter_unit = $_POST['unit_kerja'] ?? '';
$filter_tgl_dari = $_POST['tanggal_dari'] ?? '';
$filter_tgl_sampai = $_POST['tanggal_sampai'] ?? '';

// Bangun query SQL dinamis berdasarkan filter
$where_conditions = [];
if (!empty($filter_unit)) { $where_conditions[] = "unit_kerja_terakhir = '" . $mysqli->real_escape_string($filter_unit) . "'"; }
if (!empty($filter_tgl_dari)) { $where_conditions[] = "tanggal_pensiun >= '" . $mysqli->real_escape_string($filter_tgl_dari) . "'"; }
if (!empty($filter_tgl_sampai)) { $where_conditions[] = "tanggal_pensiun <= '" . $mysqli->real_escape_string($filter_tgl_sampai) . "'"; }

$sql = "SELECT nama_lengkap, nip, jabatan_terakhir, tanggal_pensiun FROM pensiunan";
if (count($where_conditions) > 0) {
    $sql .= " WHERE " . implode(' AND ', $where_conditions);
}
$sql .= " ORDER BY tanggal_pensiun ASC";

$result = $mysqli->query($sql);

class PDF extends FPDF
{
    private $filter_info = '';
    var $widths;
    var $aligns;

    function setFilterInfo($info) {
        $this->filter_info = $info;
    }

    // Page header
    function Header()
    {
        $this->SetFont('Arial','B',14);
        $this->Cell(0,10,'Laporan Data Pensiunan',0,1,'C');
        $this->SetFont('Arial','',9);
        $this->Cell(0,5,$this->filter_info,0,1,'C');
        $this->Cell(0,5,'Dibuat pada: ' . date('d F Y, H:i'),0,1,'C');
        $this->Ln(5);
    }

    // Page footer
    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Halaman '.$this->PageNo().'/{nb}',0,0,'C');
    }

    // Fungsi untuk mengatur lebar kolom
    function SetWidths($w) { $this->widths=$w; }

    function Row($data)
    {
        $nb=0;
        for($i=0;$i<count($data);$i++)
            $nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
        $h=5*$nb;
        $this->CheckPageBreak($h);
        for($i=0;$i<count($data);$i++)
        {
            $w=$this->widths[$i];
            $a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L';
            $x=$this->GetX();
            $y=$this->GetY();
            $this->Rect($x,$y,$w,$h);
            $this->MultiCell($w,5,$data[$i],0,$a);
            $this->SetXY($x+$w,$y);
        }
        $this->Ln($h);
    }

    function CheckPageBreak($h)
    {
        if($this->GetY()+$h>$this->PageBreakTrigger)
            $this->AddPage($this->CurOrientation);
    }

    function NbLines($w,$txt)
    {
        $cw=&$this->CurrentFont['cw'];
        if($w==0) $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n") $nb--;
        $sep=-1; $i=0; $j=0; $l=0; $nl=1;
        while($i<$nb)
        {
            $c=$s[$i];
            if($c=="\n") { $i++; $sep=-1; $j=$i; $l=0; $nl++; continue; }
            if($c==' ') $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax)
            {
                if($sep==-1) { if($i==$j) $i++; }
                else $i=$sep+1;
                $sep=-1; $j=$i; $l=0; $nl++;
            }
            else $i++;
        }
        return $nl;
    }
}

// Buat objek PDF
$pdf = new PDF('P','mm','A4');
$pdf->AliasNbPages();

// Set informasi filter untuk header
$filter_text = "Filter: ";
$filters_applied = [];
if (!empty($filter_unit)) $filters_applied[] = "Unit Kerja '" . $filter_unit . "'";
if (!empty($filter_tgl_dari)) $filters_applied[] = "Dari " . date('d-m-Y', strtotime($filter_tgl_dari));
if (!empty($filter_tgl_sampai)) $filters_applied[] = "Sampai " . date('d-m-Y', strtotime($filter_tgl_sampai));
if (empty($filters_applied)) $filter_text .= "Semua Data"; else $filter_text .= implode(', ', $filters_applied);
$pdf->setFilterInfo($filter_text);

$pdf->AddPage();
$pdf->SetFont('Arial','B',9);
$pdf->SetFillColor(230,230,230);

// Set lebar kolom
$pdf->SetWidths(array(10, 55, 35, 60, 30));

// Header Tabel
$pdf->Row(array('No', 'Nama Lengkap', 'NIP', 'Jabatan Terakhir', 'Tgl Pensiun'));

// Data Tabel
$pdf->SetFont('Arial','',9);
$no = 1;
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pdf->Row(array(
            $no++,
            $row['nama_lengkap'],
            $row['nip'],
            $row['jabatan_terakhir'],
            !empty($row['tanggal_pensiun']) ? date('d-m-Y', strtotime($row['tanggal_pensiun'])) : '-'
        ));
    }
} else {
    $pdf->Cell(190, 10, 'Tidak ada data yang sesuai dengan kriteria.', 1, 1, 'C');
}

$mysqli->close();
$pdf->Output('D', 'Laporan_Pensiunan_'.date('Ymd').'.pdf');
exit;
