<?php
require_once 'config.php';

$page_title = 'Profil Saya - Sistem Pensiunan';
$active_page = ''; 

// Memanggil template header
require_once 'template_header.php';

// Logika untuk ganti password
$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) { $error_message = "Semua field wajib diisi."; } 
    elseif ($new_password !== $confirm_password) { $error_message = "Password baru dan konfirmasi password tidak cocok."; } 
    else {
        $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        if ($user && password_verify($current_password, $user['password'])) {
            $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_new_password, $user_id);
            if ($update_stmt->execute()) { log_audit('CHANGE_PASSWORD_SUCCESS', 'users', $user_id); $success_message = "Password berhasil diperbarui!"; } 
            else { log_audit('CHANGE_PASSWORD_FAIL', 'users', $user_id); $error_message = "Terjadi kesalahan saat memperbarui password."; }
            $update_stmt->close();
        } else { $error_message = "Password saat ini yang Anda masukkan salah."; }
    }
}
?>

<div class="bg-white p-8 rounded-lg shadow-lg max-w-lg mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-2">Profil Saya</h2>
    <p class="text-gray-600 mb-6">Kelola informasi akun Anda di sini.</p>

    <div class="mb-6 border-t pt-6">
        <p><strong class="w-32 inline-block">Username:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <p><strong class="w-32 inline-block">Role:</strong> <?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></p>
    </div>

    <h3 class="text-xl font-semibold text-gray-700 mb-4 border-t pt-6">Ganti Password</h3>

    <?php if(!empty($success_message)): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $success_message; ?></span>
    </div>
    <?php endif; ?>
    <?php if(!empty($error_message)): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
        <span class="block sm:inline"><?php echo $error_message; ?></span>
    </div>
    <?php endif; ?>

    <form action="profile.php" method="post">
        <div class="mb-4">
            <label for="current_password" class="block text-gray-700 text-sm font-bold mb-2">Password Saat Ini</label>
            <input type="password" name="current_password" id="current_password" class="input-field" required>
        </div>
        <div class="mb-4">
            <label for="new_password" class="block text-gray-700 text-sm font-bold mb-2">Password Baru</label>
            <input type="password" name="new_password" id="new_password" class="input-field" required>
        </div>
        <div class="mb-6">
            <label for="confirm_password" class="block text-gray-700 text-sm font-bold mb-2">Konfirmasi Password Baru</label>
            <input type="password" name="confirm_password" id="confirm_password" class="input-field" required>
        </div>
        <div class="mt-8 flex justify-end">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Perubahan</button>
        </div>
    </form>
</div>

<style>.input-field { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1); }</style>

<?php
// Memanggil template footer
require_once 'template_footer.php';
?>
