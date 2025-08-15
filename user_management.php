<?php
require_once 'config.php';

// --- BAGIAN 1: PROSES AKSI (GET) SEBELUM HTML DICETAK ---
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $current_user_id = $_SESSION['user_id'];

    if ($id_to_delete !== $current_user_id) {
        $result_old = $mysqli->query("SELECT * FROM users WHERE id = $id_to_delete");
        $old_data = $result_old->fetch_assoc();
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id_to_delete);
        if ($stmt->execute()) {
            log_audit('DELETE_USER', 'users', $id_to_delete, $old_data);
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Pengguna berhasil dihapus.'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Gagal menghapus pengguna.'];
        }
        $stmt->close();
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
    }
    header("location: user_management.php");
    exit;
}

// --- BAGIAN 2: PERSIAPAN TAMPILAN ---
$page_title = 'Manajemen Pengguna - Sistem Pensiunan';
$active_page = 'users';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 p-4 rounded">Akses ditolak.</div>';
    require_once 'template_footer.php';
    exit;
}

// Mengambil semua data pengguna
$result = $mysqli->query("SELECT id, username, role FROM users ORDER BY username ASC");
?>

<!-- KONTEN UTAMA HALAMAN MANAJEMEN PENGGUNA -->
<div class="bg-white p-6 rounded-lg shadow-lg">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4 border-b pb-4">
        <div class="flex items-center">
            <i class="fas fa-users-cog fa-2x text-gray-500 mr-4"></i>
            <h2 class="text-2xl font-semibold text-gray-700">Daftar Pengguna Sistem</h2>
        </div>
        <a href="form_user.php?action=add" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
            <i class="fas fa-user-plus mr-2"></i>Tambah Pengguna Baru
        </a>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
                <tr>
                    <th class="py-3 px-6 text-left">No</th>
                    <th class="py-3 px-6 text-left">Username</th>
                    <th class="py-3 px-6 text-left">Role</th>
                    <th class="py-3 px-6 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="text-gray-700 text-sm">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $no = 1; while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo $no++; ?></td>
                        <td class="py-3 px-6 text-left font-medium"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="py-3 px-6 text-left">
                            <span class="px-3 py-1 font-semibold leading-tight rounded-full text-xs <?php echo $row['role'] == 'admin' ? 'bg-red-100 text-red-700' : 'bg-green-100 text-green-700'; ?>">
                                <?php echo ucfirst($row['role']); ?>
                            </span>
                        </td>
                        <td class="py-3 px-6 text-center">
                            <div class="flex item-center justify-center gap-2">
                                <a href="form_user.php?action=edit&id=<?php echo $row['id']; ?>" class="bg-green-500 hover:bg-green-600 text-white font-bold py-1 px-3 rounded text-xs" title="Edit Pengguna">
                                    <i class="fas fa-pencil-alt"></i> Edit
                                </a>
                                <?php if ($row['id'] !== $_SESSION['user_id']): ?>
                                <a href="javascript:void(0);" onclick="confirmUserDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['username'])); ?>')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-3 rounded text-xs" title="Hapus Pengguna">
                                    <i class="fas fa-trash-alt"></i> Hapus
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4" class="text-center py-10 text-gray-500">Tidak ada data pengguna.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteModal" class="fixed inset-0 flex items-center justify-center z-50 hidden bg-black bg-opacity-50">
    <!-- Konten modal akan diisi oleh JavaScript -->
</div>

<script>
function confirmUserDelete(id, username) {
    const modal = document.getElementById('deleteModal');
    modal.innerHTML = `
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-sm m-4">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Konfirmasi Hapus Pengguna</h3>
            <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus pengguna <strong>${username}</strong>? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex justify-end">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</button>
                <a href="user_management.php?action=delete&id=${id}" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Ya, Hapus</a>
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

<?php
require_once 'template_footer.php';
?>
