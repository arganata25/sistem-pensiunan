<?php
require_once 'config.php';

$page_title = 'Pengaturan Aplikasi - Sistem Pensiunan';
$active_page = 'settings';
require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 p-4 rounded">Akses ditolak.</div>';
    require_once 'template_footer.php';
    exit;
}

// Ambil data untuk setiap tabel
$unit_kerja_list = $mysqli->query("SELECT * FROM unit_kerja ORDER BY nama_unit ASC");
$jabatan_list = $mysqli->query("SELECT * FROM jabatan ORDER BY nama_jabatan ASC");
$kategori_list = $mysqli->query("SELECT * FROM document_categories ORDER BY category_name ASC");
?>

<?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
    <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-6">Data master berhasil diperbarui!</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Unit Kerja</h2>
            <a href="form_master_data.php?type=unit_kerja&action=add" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">+ Tambah</a>
        </div>
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-sm">
                <tbody>
                    <?php while($row = $unit_kerja_list->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo htmlspecialchars($row['nama_unit']); ?></td>
                        <td class="py-2 text-right">
                            <a href="form_master_data.php?type=unit_kerja&action=edit&id=<?php echo $row['id']; ?>" class="text-green-500 hover:text-green-700 mr-2"><i class="fas fa-pencil-alt"></i></a>
                            <a href="form_master_data.php?type=unit_kerja&action=delete&id=<?php echo $row['id']; ?>" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Kolom Jabatan -->
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Jabatan</h2>
            <a href="form_master_data.php?type=jabatan&action=add" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">+ Tambah</a>
        </div>
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-sm">
                <tbody>
                    <?php while($row = $jabatan_list->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo htmlspecialchars($row['nama_jabatan']); ?></td>
                        <td class="py-2 text-right">
                            <a href="form_master_data.php?type=jabatan&action=edit&id=<?php echo $row['id']; ?>" class="text-green-500 hover:text-green-700 mr-2"><i class="fas fa-pencil-alt"></i></a>
                            <a href="form_master_data.php?type=jabatan&action=delete&id=<?php echo $row['id']; ?>" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-semibold text-gray-700">Kategori Dokumen</h2>
            <a href="form_master_data.php?type=kategori_dokumen&action=add" class="bg-blue-500 text-white px-3 py-1 rounded-md text-sm hover:bg-blue-600">+ Tambah</a>
        </div>
        <div class="overflow-y-auto max-h-96">
            <table class="w-full text-sm">
                <tbody>
                    <?php while($row = $kategori_list->fetch_assoc()): ?>
                    <tr class="border-b">
                        <td class="py-2"><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td class="py-2 text-right">
                            <a href="form_master_data.php?type=kategori_dokumen&action=edit&id=<?php echo $row['id']; ?>" class="text-green-500 hover:text-green-700 mr-2"><i class="fas fa-pencil-alt"></i></a>
                            <a href="form_master_data.php?type=kategori_dokumen&action=delete&id=<?php echo $row['id']; ?>" class="text-red-500 hover:text-red-700"><i class="fas fa-trash-alt"></i></a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'template_footer.php'; ?>
