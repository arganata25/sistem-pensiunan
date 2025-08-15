<?php
require_once 'config.php';


if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("location: index.php");
    exit;
}
$id = intval($_GET['id']);


$error_message = '';

// Proses unggah dokumen (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['dokumen'])) {
    $pensiunan_id = $_POST['pensiunan_id'];
    $kategori = $_POST['kategori'];
    $file = $_FILES['dokumen'];
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $nama_file_asli = basename($file['name']);
        $file_ext = strtolower(pathinfo($nama_file_asli, PATHINFO_EXTENSION));
        $nama_file_unik = uniqid('doc_', true) . '.' . $file_ext;
        $jalur_file = $upload_dir . $nama_file_unik;
        if (move_uploaded_file($file['tmp_name'], $jalur_file)) {
            $tanggal_unggah = date('Y-m-d H:i:s');
            $stmt = $mysqli->prepare("INSERT INTO dokumen (pensiunan_id, nama_file_asli, nama_file_unik, jalur_file, tanggal_unggah, kategori) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $pensiunan_id, $nama_file_asli, $nama_file_unik, $jalur_file, $tanggal_unggah, $kategori);
            $stmt->execute();
            log_audit('UPLOAD_DOKUMEN', 'dokumen', $mysqli->insert_id, null, ['filename' => $nama_file_asli, 'kategori' => $kategori, 'pensiunan_id' => $pensiunan_id]);
            $stmt->close();
            header("location: detail_pensiunan.php?id=$pensiunan_id&status=upload_success");
            exit;
        } else {
            $error_message = "Gagal memindahkan file yang diunggah.";
        }
    } else {
        $error_message = "Terjadi error saat mengunggah file. Kode: " . $file['error'];
    }
}

// Proses hapus dokumen (GET)
if (isset($_GET['action']) && $_GET['action'] == 'delete_doc' && isset($_GET['doc_id'])) {
    $id = intval($_GET['id']);
    $doc_id = intval($_GET['doc_id']);
    $res_doc = $mysqli->query("SELECT * FROM dokumen WHERE id = $doc_id");
    if ($res_doc->num_rows == 1) {
        $doc = $res_doc->fetch_assoc();
        if (file_exists($doc['jalur_file'])) unlink($doc['jalur_file']);
        $mysqli->query("DELETE FROM dokumen WHERE id = $doc_id");
        log_audit('DELETE_DOKUMEN', 'dokumen', $doc_id, $doc);
    }
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Dokumen berhasil dihapus.'];
    header("location: detail_pensiunan.php?id=$id");
    exit;
}

// --- BAGIAN 2: PERSIAPAN TAMPILAN ---
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$result_pensiunan = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id");
$pensiunan = $result_pensiunan->fetch_assoc();

$page_title = 'Detail Pensiunan: ' . ($pensiunan ? htmlspecialchars($pensiunan['nama_lengkap']) : 'Data Tidak Ditemukan');
$active_page = '';
require_once 'template_header.php';

// Ambil data lain yang diperlukan
$dokumen_list = $mysqli->query("SELECT * FROM dokumen WHERE pensiunan_id = $id ORDER BY tanggal_unggah DESC");
$kategori_res = $mysqli->query("SELECT category_name FROM document_categories ORDER BY category_name ASC");

// Fungsi untuk menampilkan tanggal dengan aman
function display_date_detail($date_string) { if (!empty($date_string) && $date_string !== '0000-00-00') { return date("d F Y", strtotime($date_string)); } return '-'; }
function display_datetime_detail($date_string) { if (!empty($date_string) && $date_string !== '0000-00-00 00:00:00') { return date("d F Y, H:i", strtotime($date_string)); } return '-'; }
?>

<?php if ($pensiunan): ?>
<div class="space-y-6">
    <!-- Bagian Detail Informasi Utama -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex flex-wrap justify-between items-center mb-6 border-b pb-4 gap-4">
            <div>
                <h2 class="text-3xl font-semibold text-gray-800"><?php echo htmlspecialchars($pensiunan['nama_lengkap']); ?></h2>
                <p class="text-lg text-gray-500">NIP: <?php echo htmlspecialchars($pensiunan['nip']); ?></p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="generate_surat.php?id=<?php echo $pensiunan['id']; ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="fas fa-print mr-2"></i>Cetak Surat</a>
                <a href="export_pdf_detail.php?id=<?php echo $pensiunan['id']; ?>" target="_blank" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="fas fa-file-pdf mr-2"></i>Cetak Detail</a>
                <a href="form_pensiunan.php?action=edit&id=<?php echo $pensiunan['id']; ?>" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-300"><i class="fas fa-pencil-alt mr-2"></i>Edit Data</a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 text-sm">
            <div class="info-item"><strong class="block text-gray-500">Tanggal Lahir</strong> <span class="text-gray-800"><?php echo format_tanggal_indonesia($pensiunan['tanggal_lahir']); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Tanggal Pensiun</strong> <span class="text-gray-800"><?php echo format_tanggal_indonesia($pensiunan['tanggal_pensiun']); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Jenis Kelamin</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['jenis_kelamin'] ?: '-'); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Jabatan Terakhir</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['jabatan_terakhir'] ?: '-'); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Unit Kerja Terakhir</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['unit_kerja_terakhir'] ?: '-'); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Pangkat/Golongan</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['pangkat_golongan'] ?: '-'); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">TMT Golongan</strong> <span class="text-gray-800"><?php echo format_tanggal_indonesia($pensiunan['tmt_golongan']); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">TMT Jabatan</strong> <span class="text-gray-800"><?php echo format_tanggal_indonesia($pensiunan['tmt_jabatan']); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">No. Telepon</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['nomor_telepon'] ?: '-'); ?></span></div>
            <div class="info-item"><strong class="block text-gray-500">Email</strong> <span class="text-gray-800"><?php echo htmlspecialchars($pensiunan['alamat_email'] ?: '-'); ?></span></div>
            <div class="info-item col-span-1 md:col-span-2"><strong class="block text-gray-500">Alamat</strong> <span class="text-gray-800"><?php echo nl2br(htmlspecialchars($pensiunan['alamat'] ?: '-')); ?></span></div>
        </div>
    </div>

    <!-- Bagian Arsip Dokumen -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">Arsip Dokumen</h3>
        <form action="detail_pensiunan.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" class="bg-gray-50 p-4 rounded-lg border border-dashed mb-6">
            <!-- Form Upload (tidak ada perubahan) -->
            <input type="hidden" name="pensiunan_id" value="<?php echo $id; ?>">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2">
                    <label for="dokumen" class="block text-sm font-medium text-gray-700 mb-1">Pilih File Dokumen</label>
                    <input type="file" name="dokumen" id="dokumen" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" required>
                </div>
                <div>
                    <label for="kategori" class="block text-sm font-medium text-gray-700 mb-1">Kategori Dokumen</label>
                    <select name="kategori" id="kategori" class="w-full border rounded-md p-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php while($kat = $kategori_res->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($kat['category_name']); ?>"><?php echo htmlspecialchars($kat['category_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="text-right mt-4">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded">Unggah Dokumen</button>
            </div>
        </form>

        <div class="space-y-3">
            <?php if ($dokumen_list && $dokumen_list->num_rows > 0): ?>
                <?php while($doc = $dokumen_list->fetch_assoc()): 
                    // Escape nama file untuk JavaScript dengan aman
                    $docNameJs = htmlspecialchars($doc['nama_file_asli'], ENT_QUOTES, 'UTF-8');
                ?>
                <div class="flex items-center justify-between bg-white p-3 rounded-md border hover:shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-file-alt text-blue-500 text-xl mr-4"></i>
                        <div>
                            <a href="<?php echo htmlspecialchars($doc['jalur_file']); ?>" target="_blank" class="font-medium text-blue-600 hover:underline"><?php echo htmlspecialchars($doc['nama_file_asli']); ?></a>
                            <p class="text-xs text-gray-500 mt-1">
                                <span class="bg-gray-200 text-gray-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded"><?php echo htmlspecialchars($doc['kategori'] ?: 'Tanpa Kategori'); ?></span>
                                Diunggah pada: <?php echo display_datetime_detail($doc['tanggal_unggah']); ?>
                            </p>
                        </div>
                    </div>
                    <!-- PERUBAHAN: Menggunakan onclick untuk memanggil modal kustom -->
                    <a href="javascript:void(0);" onclick="confirmDocumentDelete(<?php echo $id; ?>, <?php echo $doc['id']; ?>, '<?php echo $docNameJs; ?>')" class="text-red-500 hover:text-red-700" title="Hapus Dokumen">
                        <i class="fas fa-trash-alt"></i>
                    </a>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center text-gray-500 py-4">Belum ada dokumen yang diunggah.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php else: ?>
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md mx-auto text-center">
        <i class="fas fa-exclamation-triangle text-red-500 text-5xl mb-4"></i>
        <h2 class="text-2xl font-bold text-gray-800">Data Tidak Ditemukan</h2>
        <p class="text-gray-600 mt-2">Maaf, data pensiunan dengan ID yang Anda minta tidak dapat ditemukan di sistem.</p>
    </div>
<?php endif; ?>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-50">
    <!-- Konten modal akan diisi oleh JavaScript -->
</div>

<!-- ================================================================= -->
<!-- SCRIPT BARU UNTUK MODAL HAPUS DOKUMEN                             -->
<!-- ================================================================= -->
<script>
function confirmDocumentDelete(pensiunanId, docId, docName) {
    const modal = document.getElementById('deleteModal');
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Konfirmasi Hapus Dokumen</h3>
            <p class="text-gray-600 mb-6">
                Apakah Anda yakin ingin menghapus dokumen: <strong>${docName}</strong>?
            </p>
            <div class="flex justify-end">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</button>
                <a href="detail_pensiunan.php?action=delete_doc&id=${pensiunanId}&doc_id=${docId}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Ya, Hapus</a>
            </div>
        </div>
    `;
    modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.innerHTML = '';
}
</script>

<?php require_once 'template_footer.php'; ?>
