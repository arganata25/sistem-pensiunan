<?php
require_once 'config.php';

// --- BAGIAN 1: PROSES AKSI (POST) SEBELUM HTML DICETAK ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'clear_log') {
    // Keamanan: Pastikan hanya admin yang bisa menjalankan ini
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        // Catat aksi pembersihan log sebelum log itu sendiri dihapus
        log_audit('CLEAR_AUDIT_LOG', 'audit_log');
        
        // Gunakan TRUNCATE untuk mereset tabel log
        $mysqli->query("TRUNCATE TABLE audit_log");
        
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Log aktivitas berhasil dibersihkan.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Anda tidak memiliki izin untuk melakukan aksi ini.'];
    }
    header("Location: audit_log.php");
    exit;
}

// --- BAGIAN 2: PERSIAPAN TAMPILAN ---
$page_title = 'Log Aktivitas - Sistem Pensiunan';
$active_page = 'logs';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 p-4 rounded">Akses ditolak.</div>';
    require_once 'template_footer.php';
    exit;
}

// Logika Paginasi
$records_per_page = 20;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$total_records_res = $mysqli->query("SELECT COUNT(id) as total FROM audit_log");
$total_records = $total_records_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ambil data log
$result = $mysqli->query("SELECT id, username, action, table_name, record_id, timestamp FROM audit_log ORDER BY timestamp DESC LIMIT $records_per_page OFFSET $offset");
?>

<!-- KONTEN UTAMA HALAMAN LOG AKTIVITAS -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4 border-b pb-4">
        <div class="flex items-center">
            <i class="fas fa-clipboard-list fa-2x text-gray-500 mr-4"></i>
            <h2 class="text-2xl font-semibold text-gray-700">Log Aktivitas Sistem</h2>
        </div>
        <!-- ================================================================= -->
        <!-- TOMBOL BARU UNTUK MEMBERSIHKAN LOG                              -->
        <!-- ================================================================= -->
        <button onclick="confirmClearLog()" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            <i class="fas fa-eraser mr-2"></i>Bersihkan Log
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <tr>
                    <th class="py-3 px-6 text-left">Waktu</th>
                    <th class="py-3 px-6 text-left">Pengguna</th>
                    <th class="py-3 px-6 text-left">Aksi</th>
                    <th class="py-3 px-6 text-left">Detail Target</th>
                    <th class="py-3 px-6 text-center">Lihat</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo date("d M Y, H:i:s", strtotime($row['timestamp'])); ?></td>
                        <td class="py-3 px-6 text-left font-medium"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="py-3 px-6 text-left">
                            <?php 
                                $action = $row['action'];
                                $color_class = 'bg-gray-100 text-gray-800'; // Default
                                if (strpos($action, 'CREATE') !== false || strpos($action, 'UPLOAD') !== false || strpos($action, 'SUCCESS') !== false) { $color_class = 'bg-green-100 text-green-800'; } 
                                elseif (strpos($action, 'UPDATE') !== false || strpos($action, 'CHANGE') !== false) { $color_class = 'bg-yellow-100 text-yellow-800'; } 
                                elseif (strpos($action, 'DELETE') !== false || strpos($action, 'ARCHIVE') !== false || strpos($action, 'FAIL') !== false) { $color_class = 'bg-red-100 text-red-800'; } 
                                elseif (strpos($action, 'RESTORE') !== false || strpos($action, 'LOGOUT') !== false) { $color_class = 'bg-blue-100 text-blue-800'; }
                            ?>
                            <span class="px-3 py-1 font-semibold leading-tight rounded-full text-xs <?php echo $color_class; ?>">
                                <?php echo htmlspecialchars(str_replace('_', ' ', $action)); ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-left">
                            <?php 
                                if (!empty($row['table_name'])) { echo "Tabel: " . htmlspecialchars($row['table_name']); }
                                if (!empty($row['record_id'])) { echo " | ID: " . htmlspecialchars($row['record_id']); }
                            ?>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <a href="log_detail.php?id=<?php echo $row['id']; ?>" class="text-blue-500 hover:text-blue-700" title="Lihat Detail Log"><i class="fas fa-search-plus"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-10 text-gray-500">Tidak ada log aktivitas yang tercatat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Navigasi Paginasi -->
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Menampilkan <?php echo $result->num_rows; ?> dari <?php echo $total_records; ?> log
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center">
            <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-link py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-l-md <?php echo ($current_page <= 1) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Sebelumnya</a>
            <?php 
            $range = 1;
            for ($i = 1; $i <= $total_pages; $i++):
                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
                    $active_class = ($current_page == $i) ? 'bg-blue-500 border-blue-500 text-white' : 'bg-white hover:bg-gray-100';
                    echo "<a href='?page=$i' class='pagination-link py-2 px-4 border-t border-b border-gray-300 text-gray-600 $active_class'>$i</a>";
                elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1):
                    echo "<span class='py-2 px-4 border-t border-b border-gray-300 text-gray-600 bg-white'>...</span>";
                endif;
            endfor;
            ?>
            <a href="?page=<?php echo $current_page + 1; ?>" class="pagination-link py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-r-md <?php echo ($current_page >= $total_pages) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Berikutnya</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-50">
    <!-- Konten modal akan diisi oleh JavaScript -->
</div>

<script>
function confirmClearLog() {
    const modal = document.getElementById('deleteModal');
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Konfirmasi Bersihkan Log</h3>
            <p class="text-gray-600 mb-6">
                PERINGATAN: Anda akan menghapus <strong>semua</strong> catatan log aktivitas secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?
            </p>
            <form id="clearLogForm" method="post" action="audit_log.php">
                <input type="hidden" name="action" value="clear_log">
                <div class="flex justify-end">
                    <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</button>
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Ya, Bersihkan Log</button>
                </div>
            </form>
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
