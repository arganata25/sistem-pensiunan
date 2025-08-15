<?php
require_once 'config.php';

$page_title = 'Backup Database - Sistem Pensiunan';
$active_page = 'backup';

require_once 'template_header.php';

if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p></div>';
    require_once 'template_footer.php';
    exit;
}
?>

<div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">Backup Database</h2>
    <p class="text-gray-600 mb-6">
        Gunakan fitur ini untuk mengunduh salinan lengkap dari seluruh database sistem. File yang diunduh akan berformat <code>.sql</code> dan berisi semua data pensiunan, pengguna, dokumen, dan log aktivitas. Simpan file ini di tempat yang aman sebagai cadangan.
    </p>

    <div class="border-t pt-6">
        <a href="backup_script.php" 
           class="w-full inline-block text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
            <i class="fas fa-download mr-2"></i>
            Mulai Proses Backup dan Unduh File
        </a>
    </div>

    <div class="mt-6 p-4 bg-yellow-100 text-yellow-800 rounded-lg text-sm">
        <p><strong class="font-bold">Perhatian:</strong> Proses ini mungkin membutuhkan beberapa saat jika data Anda sangat besar. Harap jangan menutup browser Anda sampai proses unduhan selesai.</p>
    </div>
</div>

<?php
require_once 'template_footer.php';
?>
