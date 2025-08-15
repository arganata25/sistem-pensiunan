<?php
require_once 'config.php';

// Mendefinisikan judul halaman dan halaman aktif untuk sidebar
$page_title = 'Buat Laporan - Sistem Pensiunan';
$active_page = 'reports'; // Halaman aktif baru

// Memanggil template header
require_once 'template_header.php';

// Keamanan: Hanya 'admin' yang dapat mengakses halaman ini.
if ($_SESSION['role'] !== 'admin') {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4" role="alert"><p>Anda tidak memiliki izin untuk mengakses halaman ini.</p></div>';
    require_once 'template_footer.php';
    exit;
}

// Ambil data master untuk dropdown filter
$unit_kerja_list = $mysqli->query("SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
?>

<div class="bg-white p-6 rounded-lg shadow-lg max-w-2xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">Buat Laporan Pensiunan</h2>
    <p class="text-gray-600 mb-6">
        Pilih kriteria di bawah ini untuk menghasilkan laporan dalam format PDF. Anda dapat membiarkan filter kosong untuk mencakup semua data.
    </p>

    <form action="generate_report.php" method="post" target="_blank">
        <div class="space-y-4">
            <!-- Filter Unit Kerja -->
            <div>
                <label for="unit_kerja" class="block text-sm font-medium text-gray-700 mb-1">Filter Berdasarkan Unit Kerja</label>
                <select name="unit_kerja" id="unit_kerja" class="w-full border rounded-md p-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">-- Semua Unit Kerja --</option>
                    <?php while($unit = $unit_kerja_list->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($unit['nama_unit']); ?>">
                            <?php echo htmlspecialchars($unit['nama_unit']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Filter Rentang Tanggal Pensiun -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="tanggal_dari" class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pensiun Dari</label>
                    <input type="date" name="tanggal_dari" id="tanggal_dari" class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label for="tanggal_sampai" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="tanggal_sampai" id="tanggal_sampai" class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <div class="border-t pt-6 mt-6">
            <button type="submit" 
               class="w-full inline-block text-center bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                <i class="fas fa-file-pdf mr-2"></i>
                Buat Laporan PDF
            </button>
        </div>
    </form>
</div>

<?php

require_once 'template_footer.php';
?>
