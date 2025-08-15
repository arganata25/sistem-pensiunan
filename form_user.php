<?php
require_once 'config.php';

// Keamanan: Hanya 'admin' yang dapat mengakses halaman ini.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("location: index.php");
    exit;
}

// Inisialisasi variabel
$action = 'add';
$page_title = 'Tambah Pengguna Baru';
$user = ['id' => '', 'username' => '', 'role' => 'staf'];
$password_required = 'required';
$error_message = '';
$original_user_data = null;

// Jika mode edit, ambil data yang ada
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $action = 'edit';
    $page_title = 'Edit Pengguna';
    $password_required = '';
    $id = intval($_GET['id']);
    $result = $mysqli->query("SELECT id, username, role FROM users WHERE id = $id");
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $original_user_data = $user;
    } else {
        header("location: user_management.php?status=error");
        exit;
    }
}

// Cek jika ada status error dari redirect sebelumnya
if (isset($_GET['status']) && $_GET['status'] == 'duplicate') {
    $error_message = "Username sudah digunakan. Silakan pilih username lain.";
}

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = null;

    $role = $_POST['role'] ?? ($action === 'edit' ? $original_user_data['role'] : 'staf');

    // =================================================================
    // PERBAIKAN: Menggunakan try-catch untuk menangani error database
    // =================================================================
    try {
        if ($_POST['action'] == 'add') {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashed_password, $role);
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $mysqli->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
                $stmt->bind_param("sssi", $username, $hashed_password, $role, $id);
            } else {
                $stmt = $mysqli->prepare("UPDATE users SET username=?, role=? WHERE id=?");
                $stmt->bind_param("ssi", $username, $role, $id);
            }
        }

        $stmt->execute();
        
        $log_action = ($_POST['action'] == 'add') ? 'CREATE_USER' : 'UPDATE_USER';
        $record_id = ($_POST['action'] == 'add') ? $mysqli->insert_id : $id;
        $new_data = ['username' => $username, 'role' => $role];
        log_audit($log_action, 'users', $record_id, $original_user_data, $new_data);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data pengguna berhasil disimpan!'];
        header("location: user_management.php");
        exit;

    } catch (mysqli_sql_exception $e) {
        // Tangkap error spesifik dari database
        if ($e->getCode() == 1062) { // 1062 adalah kode error untuk 'Duplicate entry'
            $redirect_url = "form_user.php?action=" . $_POST['action'] . "&status=duplicate";
            if ($_POST['action'] == 'edit') $redirect_url .= "&id=" . $id;
            header("Location: " . $redirect_url);
        } else {
            // Untuk error database lainnya
            header("location: user_management.php?status=error&code=" . $e->getCode());
        }
        if ($stmt) $stmt->close();
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-gray-100">
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-6 py-4">
            <h1 class="text-xl font-bold text-gray-800">Sistem Pensiunan</h1>
        </div>
    </header>

    <main class="container mx-auto px-6 py-8">
        <div class="bg-white p-8 rounded-lg shadow-lg max-w-lg mx-auto">
            <h2 class="text-2xl font-semibold text-gray-700 mb-6"><?php echo $page_title; ?></h2>
            
            <?php if(!empty($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
            <?php endif; ?>

            <form action="form_user.php" method="post">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">

                <div class="mb-4">
                    <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                    <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="input-field" required>
                </div>

                <div class="mb-4">
                    <label for="role" class="block text-gray-700 text-sm font-bold mb-2">Role</label>
                    <select name="role" id="role" class="input-field" <?php echo ($action == 'edit' && $user['id'] == $_SESSION['user_id']) ? 'disabled' : ''; ?>>
                        <option value="staf" <?php echo ($user['role'] == 'staf') ? 'selected' : ''; ?>>Staf</option>
                        <option value="admin" <?php echo ($user['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <?php if ($action == 'edit' && $user['id'] == $_SESSION['user_id']): ?>
                        <p class="text-xs text-gray-500 mt-1">Anda tidak dapat mengubah role akun Anda sendiri.</p>
                    <?php endif; ?>
                </div>

                <div class="mb-6">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                    <input type="password" name="password" id="password" class="input-field" <?php echo $password_required; ?>>
                    <?php if ($action == 'edit'): ?>
                        <p class="text-xs text-gray-500 mt-1">Kosongkan jika tidak ingin mengubah password.</p>
                    <?php endif; ?>
                </div>

                <div class="mt-8 flex justify-end">
                    <a href="user_management.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</a>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Pengguna</button>
                </div>
            </form>
        </div>
    </main>
    <style>.input-field { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); background-color: white; } .input-field:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4); } .input-field:disabled { background-color: #f3f4f6; cursor: not-allowed; }</style>
</body>
</html>
