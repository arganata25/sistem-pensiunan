<?php
require_once 'config.php';

$error_message = '';

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("location: index.php");
    exit;
}

// Proses form saat disubmit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password tidak boleh kosong.";
    } else {
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        
        if ($stmt = $mysqli->prepare($sql)) {
            $stmt->bind_param("s", $username);
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $role);
                    if ($stmt->fetch()) {
                        if (password_verify($password, $hashed_password)) {
                            // Password benar, mulai session baru
                            session_start();
                            
                            $_SESSION['user_id'] = $id;
                            $_SESSION['username'] = $username;
                            $_SESSION['role'] = $role;
                            
                            log_audit('LOGIN_SUCCESS', 'users', $id);

                            // Redirect ke halaman utama
                            header("location: index.php");
                            exit;
                        } else {
                            $error_message = "Password yang Anda masukkan salah.";
                            log_audit('LOGIN_FAIL: Wrong Password', 'users', null, json_encode(['username' => $username]));
                        }
                    }
                } else {
                    $error_message = "Username tidak ditemukan.";
                    log_audit('LOGIN_FAIL: User Not Found', 'users', null, json_encode(['username' => $username]));
                }
            } else {
                $error_message = "Terjadi kesalahan. Silakan coba lagi nanti.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Pensiunan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        
        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0px);
            }
        }
        .logo-animation {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8 space-y-6">
        
        <!-- Header Form dengan Logo -->
        <div class="text-center">
            <img src="https://upload.wikimedia.org/wikipedia/commons/c/cf/Logo_Kementerian_Lingkungan_Hidup_-_Badan_Pengendalian_Lingkungan_Hidup_%282024%29.png" 
                 alt="Logo Dinas Lingkungan Hidup" 
                 class="w-40 h-40 mx-auto mb-4 logo-animation"
                 onerror="this.onerror=null; 
                 this.src='https://placehold.co/128x128/e2e8f0/334155?text=Logo';">
            
            <h2 class="text-3xl font-bold text-gray-800">Sistem Pensiunan</h2>
            <p class="text-gray-500 mt-2">Dinas Lingkungan Hidup</p>
        </div>
        
        <!-- Pesan Error -->
        <?php if(!empty($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-md" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Form Login -->
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-6">
            <!-- Input Username -->
            <div>
                <label for="username" class="sr-only">Username</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" name="username" id="username" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" placeholder="Username" required>
                </div>
            </div>
            <!-- Input Password -->
            <div>
                <label for="password" class="sr-only">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="password" id="password" class="w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition" placeholder="Password" required>
                </div>
            </div>
            <!-- Tombol Submit -->
            <div>
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-blue-500/50">
                    Masuk
                </button>
            </div>
        </form>
    </div>
</body>
</html>
