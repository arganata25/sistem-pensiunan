<?php
require_once 'config.php';

// Mendefinisikan judul halaman dan halaman aktif untuk sidebar
$page_title = 'Detail Log Aktivitas - Sistem Pensiunan';
$active_page = 'logs';

// Memanggil template header
require_once 'template_header.php';

// Keamanan: Hanya 'admin' yang dapat mengakses halaman ini.
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p></div>';
    require_once 'template_footer.php';
    exit;
}

// Validasi ID log
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>ID Log tidak valid.</p></div>';
    require_once 'template_footer.php';
    exit;
}

$log_id = intval($_GET['id']);

// Ambil data log dari database
$stmt = $mysqli->prepare("SELECT * FROM audit_log WHERE id = ?");
$stmt->bind_param("i", $log_id);
$stmt->execute();
$result = $stmt->get_result();
$log = $result->fetch_assoc();
$stmt->close();

if (!$log) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>Data log tidak ditemukan.</p></div>';
    require_once 'template_footer.php';
    exit;
}

// Decode data JSON dari old_value dan new_value
$old_data = !empty($log['old_value']) ? json_decode($log['old_value'], true) : [];
$new_data = !empty($log['new_value']) ? json_decode($log['new_value'], true) : [];

// Gabungkan semua kunci dari data lama dan baru untuk perbandingan
$all_keys = array_keys($old_data + $new_data);

?>

<div class="bg-white p-6 rounded-lg shadow-lg max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <div>
            <h2 class="text-2xl font-semibold text-gray-700">Detail Log #<?php echo $log['id']; ?></h2>
            <p class="text-sm text-gray-500">Waktu: <?php echo date("d F Y, H:i:s", strtotime($log['timestamp'])); ?></p>
        </div>
        <a href="audit_log.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            &larr; Kembali ke Daftar Log
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
        <div><strong class="block text-gray-500">Pengguna</strong> <span class="text-gray-800"><?php echo htmlspecialchars($log['username']); ?></span></div>
        <div><strong class="block text-gray-500">Aksi</strong> <span class="text-gray-800 font-semibold"><?php echo htmlspecialchars($log['action']); ?></span></div>
        <div><strong class="block text-gray-500">Tabel Target</strong> <span class="text-gray-800"><?php echo htmlspecialchars($log['table_name'] ?: '-'); ?></span></div>
        <div><strong class="block text-gray-500">ID Record</strong> <span class="text-gray-800"><?php echo htmlspecialchars($log['record_id'] ?: '-'); ?></span></div>
    </div>

    <?php if (!empty($old_data) || !empty($new_data)): ?>
    <div class="mt-8 border-t pt-6">
        <h3 class="text-xl font-semibold text-gray-700 mb-4">Detail Perubahan Data</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="py-2 px-4 border-b text-left font-semibold text-gray-600">Field</th>
                        <th class="py-2 px-4 border-b text-left font-semibold text-gray-600">Data Lama</th>
                        <th class="py-2 px-4 border-b text-left font-semibold text-gray-600">Data Baru</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_keys as $key): ?>
                        <?php
                            $old_value = isset($old_data[$key]) ? htmlspecialchars($old_data[$key]) : '<em>(kosong)</em>';
                            $new_value = isset($new_data[$key]) ? htmlspecialchars($new_data[$key]) : '<em>(kosong)</em>';
                            // Jangan tampilkan baris jika tidak ada perubahan
                            if ($old_value === $new_value) continue;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="py-2 px-4 border-b font-medium text-gray-800"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></td>
                            <td class="py-2 px-4 border-b text-gray-600 bg-red-50"><?php echo $old_value; ?></td>
                            <td class="py-2 px-4 border-b text-gray-600 bg-green-50"><?php echo $new_value; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php else: ?>
    <div class="mt-8 border-t pt-6">
        <p class="text-center text-gray-500">Tidak ada detail perubahan data untuk log ini.</p>
    </div>
    <?php endif; ?>
</div>

<?php
// Memanggil template footer
require_once 'template_footer.php';
?>
