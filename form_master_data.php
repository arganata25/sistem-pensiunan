<?php
require_once 'config.php';

// Keamanan: Pastikan pengguna adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirect ke login jika tidak ada sesi atau bukan admin
    header("location: login.php");
    exit;
}

$type = $_GET['type'] ?? '';
$config = [];
switch ($type) {
    case 'unit_kerja':
        $config = ['table' => 'unit_kerja', 'column' => 'nama_unit', 'title' => 'Unit Kerja'];
        break;
    case 'jabatan':
        $config = ['table' => 'jabatan', 'column' => 'nama_jabatan', 'title' => 'Jabatan'];
        break;
    case 'kategori_dokumen':
        $config = ['table' => 'document_categories', 'column' => 'category_name', 'title' => 'Kategori Dokumen'];
        break;
    default:
        // Jika tipe tidak valid, kita bisa redirect atau menampilkan pesan error nanti
        // Untuk sekarang, kita set flag error
        $is_invalid_type = true;
}

if (isset($config['table']) && $_SERVER["REQUEST_METHOD"] == "POST") {
    $posted_action = $_POST['action'] ?? '';
    $posted_id = $_POST['id'] ?? null;
    $posted_value = isset($_POST['value']) ? trim($_POST['value']) : '';

    try {
        if ($posted_action === 'add' && !empty($posted_value)) {
            $stmt = $mysqli->prepare("INSERT INTO {$config['table']} ({$config['column']}) VALUES (?)");
            $stmt->bind_param("s", $posted_value);
            $stmt->execute();
        } elseif ($posted_action === 'edit' && $posted_id && !empty($posted_value)) {
            $stmt = $mysqli->prepare("UPDATE {$config['table']} SET {$config['column']} = ? WHERE id = ?");
            $stmt->bind_param("si", $posted_value, $posted_id);
            $stmt->execute();
        } elseif ($posted_action === 'delete' && $posted_id) {
            $stmt = $mysqli->prepare("DELETE FROM {$config['table']} WHERE id = ?");
            $stmt->bind_param("i", $posted_id);
            $stmt->execute();
        }
        // Jika berhasil, redirect kembali ke halaman pengaturan
        header("Location: settings.php?status=success");
        exit;
    } catch (mysqli_sql_exception $e) {
        // Jika gagal, siapkan pesan error untuk ditampilkan nanti
        if ($e->getCode() == 1062) {
            $error_message = "Nama '{$posted_value}' sudah ada. Silakan gunakan nama lain.";
        } else {
            $error_message = "Terjadi kesalahan database: " . $e->getMessage();
        }
        // Simpan data yang diinput agar form bisa diisi kembali
        $_SESSION['form_error'] = ['message' => $error_message, 'value' => $posted_value];
        $redirect_url = "form_master_data.php?type={$type}&action={$posted_action}";
        if ($posted_id) $redirect_url .= "&id={$posted_id}";
        header("Location: " . $redirect_url);
        exit;
    }
}

$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;
$data = null;

// Ambil pesan error dari sesi jika ada
if (isset($_SESSION['form_error'])) {
    $error_message = $_SESSION['form_error']['message'];
    $current_value_on_error = $_SESSION['form_error']['value'];
    unset($_SESSION['form_error']);
}

if (($action === 'edit' || $action === 'delete') && $id) {
    $stmt = $mysqli->prepare("SELECT * FROM {$config['table']} WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
}

$page_title = 'Kelola Data Master - Sistem Pensiunan';
$active_page = 'settings';
require_once 'template_header.php';

// Tampilkan error jika tipe tidak valid
if (isset($is_invalid_type)) {
    echo '<div class="bg-red-100 p-4 rounded">Tipe data tidak valid.</div>';
    require_once 'template_footer.php';
    exit;
}
// Tampilkan error jika data tidak ditemukan
if (($action === 'edit' || $action === 'delete') && !$data) {
    echo '<div class="bg-red-100 p-4 rounded">Data tidak ditemukan.</div>';
    require_once 'template_footer.php';
    exit;
}
?>

<!-- Tampilan Halaman -->
<div class="bg-white p-6 rounded-lg shadow-lg max-w-lg mx-auto">
    <?php if ($action === 'delete'): ?>
        <!-- Tampilan Konfirmasi Hapus -->
        <h2 class="text-2xl font-semibold text-gray-700 mb-4">Konfirmasi Hapus</h2>
        <p class="text-gray-600 mb-6">
            Apakah Anda yakin ingin menghapus <?php echo strtolower($config['title']); ?>: 
            <strong class="font-bold text-red-600">"<?php echo htmlspecialchars($data[$config['column']]); ?>"</strong>?
        </p>
        <form method="post" action="form_master_data.php?type=<?php echo $type; ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?php echo $data['id']; ?>">
            <div class="mt-6 flex justify-end gap-2">
                <a href="settings.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Batal</a>
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Ya, Hapus</button>
            </div>
        </form>
    <?php else: ?>
        <!-- Tampilan Form Tambah/Edit -->
        <h2 class="text-2xl font-semibold text-gray-700 mb-6"><?php echo ucfirst($action) . ' ' . $config['title']; ?></h2>
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form method="post" action="form_master_data.php?type=<?php echo $type; ?>">
            <input type="hidden" name="action" value="<?php echo $action; ?>">
            <input type="hidden" name="id" value="<?php echo $data['id'] ?? ''; ?>">
            <div class="mb-4">
                <label for="value" class="block text-gray-700 text-sm font-bold mb-2">Nama <?php echo $config['title']; ?></label>
                <input type="text" name="value" id="value" value="<?php echo htmlspecialchars($current_value_on_error ?? $data[$config['column']] ?? ''); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div class="mt-6 flex justify-end gap-2">
                <a href="settings.php" class="bg-gray-300 text-gray-800 px-4 py-2 rounded hover:bg-gray-400">Batal</a>
                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Simpan</button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php require_once 'template_footer.php'; ?>
