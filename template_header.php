<?php
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Pensiunan'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css">
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #888; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #555; }
        .sidebar { transition: transform 0.3s ease-in-out; }
        .sidebar-link { transition: background-color 0.2s ease-in-out, transform 0.1s ease-in-out; transform: scale(1); }
        .sidebar-link:active { transform: scale(0.98); background-color: #1F2937; }
    </style>
</head>
<body class="bg-gray-100">
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notyf = new Notyf({ duration: 5000, position: { x: 'right', y: 'top' }, dismissible: true });
            <?php
            if (isset($_SESSION['flash_message'])) {
                $flash = $_SESSION['flash_message'];
                $type = $flash['type'];
                $message = addslashes($flash['message']);
                echo "notyf.$type('$message');";
                unset($_SESSION['flash_message']);
            }
            ?>
        });
    </script>

    <div class="flex h-screen bg-gray-200">
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar fixed inset-y-0 left-0 z-30 w-64 bg-gray-800 text-white transform -translate-x-full md:relative md:translate-x-0">
            <div class="p-4 text-center text-2xl font-bold bg-gray-900">Sistem Pensiunan</div>
            <nav class="mt-4">
                <a href="index.php" class="sidebar-link flex items-center px-6 py-3 <?php echo ($active_page == 'dashboard') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-tachometer-alt w-6"></i><span class="mx-3">Dashboard</span></a>
                
                <!-- ================================================================= -->
                <!-- PERUBAHAN: Menu Arsip dipindahkan ke sini agar bisa diakses Staf -->
                <!-- ================================================================= -->
                <a href="arsip.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'archive') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>">
                    <i class="fas fa-archive w-6"></i>
                    <span class="mx-3">Arsip</span>
                </a>

                <?php if ($_SESSION['role'] === 'admin'): ?>
                <a href="user_management.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'users') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-users-cog w-6"></i><span class="mx-3">Manajemen User</span></a>
                <a href="audit_log.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'logs') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-clipboard-list w-6"></i><span class="mx-3">Log Aktivitas</span></a>
                <a href="laporan.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'reports') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-file-alt w-6"></i><span class="mx-3">Laporan</span></a>
                <a href="backup_page.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'backup') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-database w-6"></i><span class="mx-3">Backup Database</span></a>
                <a href="settings.php" class="sidebar-link flex items-center px-6 py-3 mt-2 <?php echo ($active_page == 'settings') ? 'bg-gray-900' : 'hover:bg-gray-700'; ?>"><i class="fas fa-cogs w-6"></i><span class="mx-3">Pengaturan</span></a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="flex justify-between items-center p-4 bg-white border-b">
                <button id="hamburger-btn" class="text-gray-500 focus:outline-none md:hidden"><i class="fas fa-bars fa-lg"></i></button>
                <div class="flex-grow"></div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="font-bold text-gray-800"><?php echo htmlspecialchars($_SESSION['username']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?></div>
                    </div>
                    <a href="profile.php" class="text-gray-600 hover:text-blue-500" title="Profil Saya"><i class="fas fa-user-circle text-2xl"></i></a>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-3 rounded-full transition duration-300" title="Logout">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </header>
            
            <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6">
