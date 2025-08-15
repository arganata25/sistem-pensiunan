<?php
require_once 'config.php';

// --- BAGIAN 1: PROSES AKSI (GET) SEBELUM HTML DICETAK ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    if ($_GET['action'] == 'restore') {
        $mysqli->query("UPDATE pensiunan SET status = 'aktif' WHERE id = $id");
        log_audit('RESTORE_PENSIUNAN', 'pensiunan', $id);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data berhasil dipulihkan.'];
    } 
    // PERUBAHAN: Aksi hapus permanen hanya bisa dilakukan oleh admin
    elseif ($_GET['action'] == 'force_delete' && $_SESSION['role'] === 'admin') {
        $result_old = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id");
        $old_data = $result_old->fetch_assoc();
        $docs_to_delete = $mysqli->query("SELECT jalur_file FROM dokumen WHERE pensiunan_id = $id");
        if ($docs_to_delete->num_rows > 0) {
            while ($doc = $docs_to_delete->fetch_assoc()) {
                if (file_exists($doc['jalur_file'])) unlink($doc['jalur_file']);
            }
        }
        $mysqli->query("DELETE FROM pensiunan WHERE id = $id");
        log_audit('FORCE_DELETE_PENSIUNAN', 'pensiunan', $id, $old_data);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data berhasil dihapus permanen.'];
    }
    
    header("Location: arsip.php");
    exit;
}

// --- BAGIAN 2: PERSIAPAN TAMPILAN ---
$page_title = 'Arsip Data Pensiunan - Sistem Pensiunan';
$active_page = 'archive';
require_once 'template_header.php';

// PERUBAHAN: Halaman ini tidak lagi memerlukan pengecekan role admin
// Pengecekan login sudah ada di template_header.php

// Logika Paginasi
$records_per_page = 15;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$total_records_res = $mysqli->query("SELECT COUNT(id) as total FROM pensiunan WHERE status = 'arsip'");
$total_records = $total_records_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Ambil data yang diarsipkan
$result = $mysqli->query("SELECT id, nama_lengkap, nip, jabatan_terakhir FROM pensiunan WHERE status = 'arsip' ORDER BY nama_lengkap ASC LIMIT $records_per_page OFFSET $offset");
?>

<!-- KONTEN UTAMA HALAMAN ARSIP -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex items-center mb-4">
        <i class="fas fa-archive fa-2x text-gray-500 mr-4"></i>
        <h2 class="text-2xl font-semibold text-gray-700">Data Pensiunan yang Diarsipkan</h2>
    </div>
    <p class="text-gray-600 mb-6 border-t pt-4">
        Data di bawah ini telah dihapus dari tampilan utama tetapi belum dihapus permanen. Anda dapat memulihkannya ke daftar aktif atau menghapusnya secara permanen dari sistem.
    </p>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <tr>
                    <th class="py-3 px-6 text-left">Nama Lengkap</th>
                    <th class="py-3 px-6 text-left">NIP</th>
                    <th class="py-3 px-6 text-left">Jabatan Terakhir</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 font-medium"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($row['nip']); ?></td>
                        <td class="py-3 px-6"><?php echo htmlspecialchars($row['jabatan_terakhir']); ?></td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center gap-2">
                                <a href="arsip.php?action=restore&id=<?php echo $row['id']; ?>" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-1 px-3 rounded text-xs" title="Pulihkan Data">
                                    <i class="fas fa-undo"></i> Pulihkan
                                </a>
                                <!-- PERUBAHAN: Tombol ini hanya muncul untuk admin -->
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                <a href="javascript:void(0);" onclick="confirmPermanentDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['nama_lengkap'])); ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs" title="Hapus Permanen">
                                    <i class="fas fa-trash-alt"></i> Hapus Permanen
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-10 text-gray-500">Tidak ada data yang diarsipkan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Navigasi Paginasi -->
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Menampilkan <?php echo $result->num_rows; ?> dari <?php echo $total_records; ?> data arsip
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
<div id="deleteModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-50"></div>

<script>
function confirmPermanentDelete(id, name) {
    const modal = document.getElementById('deleteModal');
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Konfirmasi Hapus Permanen</h3>
            <p class="text-gray-600 mb-6">
                PERINGATAN: Anda akan menghapus data untuk <strong>${name}</strong> secara permanen. Tindakan ini tidak dapat dibatalkan. Lanjutkan?
            </p>
            <div class="flex justify-end">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</button>
                <a href="arsip.php?action=force_delete&id=${id}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Ya, Hapus Permanen</a>
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
