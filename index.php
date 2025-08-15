<?php
require_once 'config.php';


// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

// Logika Aksi (Logout & Hapus) dipindahkan ke atas agar dieksekusi terlebih dahulu
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_delete = intval($_GET['id']);
    $result_old = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id_to_delete");
    $old_data = $result_old->fetch_assoc();
    
    // Logika hapus dokumen (sudah benar)
    $docs_to_delete = $mysqli->query("SELECT jalur_file FROM dokumen WHERE pensiunan_id = $id_to_delete");
    if ($docs_to_delete->num_rows > 0) {
        while ($doc = $docs_to_delete->fetch_assoc()) {
            if (file_exists($doc['jalur_file'])) {
                unlink($doc['jalur_file']);
            }
        }
    }
    
    // Logika arsipkan data
    $stmt = $mysqli->prepare("UPDATE pensiunan SET status = 'arsip' WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    
    if ($stmt->execute()) {
        log_audit('ARCHIVE_PENSIUNAN', 'pensiunan', $id_to_delete, $old_data);
        $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data berhasil diarsipkan.'];
    } else {
        $_SESSION['flash_message'] = ['type' => 'error', 'message' => 'Gagal mengarsipkan data.'];
    }
    $stmt->close();
    header("location: index.php");
    exit;
}

// Mendefinisikan judul halaman dan halaman aktif untuk sidebar
$page_title = 'Dashboard - Sistem Pensiunan';
$active_page = 'dashboard';
require_once 'template_header.php';


// --- Statistik Utama (Kartu) ---
$total_pensiunan_res = $mysqli->query("SELECT COUNT(id) as total FROM pensiunan");
$total_pensiunan = $total_pensiunan_res->fetch_assoc()['total'];
$one_year_later = date('Y-m-d', strtotime('+1 year'));
$today = date('Y-m-d');
$upcoming_retirements_res = $mysqli->query("SELECT COUNT(id) as total FROM pensiunan WHERE tanggal_pensiun BETWEEN '$today' AND '$one_year_later'");
$upcoming_retirements_count = $upcoming_retirements_res->fetch_assoc()['total'];
$total_dokumen_res = $mysqli->query("SELECT COUNT(id) as total FROM dokumen");
$total_dokumen = $total_dokumen_res->fetch_assoc()['total'];

// --- Data untuk Grafik ---
$laki_laki_res = $mysqli->query("SELECT COUNT(id) as jumlah FROM pensiunan WHERE jenis_kelamin = 'Laki-laki'");
$jumlah_laki_laki = $laki_laki_res->fetch_assoc()['jumlah'];
$perempuan_res = $mysqli->query("SELECT COUNT(id) as jumlah FROM pensiunan WHERE jenis_kelamin = 'Perempuan'");
$jumlah_perempuan = $perempuan_res->fetch_assoc()['jumlah'];
$gender_data = ['labels' => ['Laki-laki', 'Perempuan'], 'data' => [$jumlah_laki_laki, $jumlah_perempuan]];
$gender_chart_data_json = json_encode($gender_data);
$unit_kerja_chart_res = $mysqli->query("SELECT unit_kerja_terakhir, COUNT(id) as jumlah FROM pensiunan WHERE unit_kerja_terakhir IS NOT NULL AND unit_kerja_terakhir != '' GROUP BY unit_kerja_terakhir ORDER BY jumlah DESC LIMIT 10");
$unit_kerja_chart_data = ['labels' => [], 'data' => []];
while($row = $unit_kerja_chart_res->fetch_assoc()) { $unit_kerja_chart_data['labels'][] = $row['unit_kerja_terakhir']; $unit_kerja_chart_data['data'][] = $row['jumlah']; }
$unit_kerja_chart_data_json = json_encode($unit_kerja_chart_data);
$tren_pensiun_res = $mysqli->query("SELECT YEAR(tanggal_pensiun) as tahun, COUNT(id) as jumlah FROM pensiunan WHERE tanggal_pensiun IS NOT NULL GROUP BY YEAR(tanggal_pensiun) ORDER BY tahun ASC");
$tren_pensiun_chart_data = ['labels' => [], 'data' => []];
while($row = $tren_pensiun_res->fetch_assoc()) { $tren_pensiun_chart_data['labels'][] = $row['tahun']; $tren_pensiun_chart_data['data'][] = $row['jumlah']; }
$tren_pensiun_chart_data_json = json_encode($tren_pensiun_chart_data);

// --- Logika Notifikasi dan Lainnya ---
$upcoming_list_res = $mysqli->query("SELECT nama_lengkap, tanggal_pensiun FROM pensiunan WHERE tanggal_pensiun >= '$today' ORDER BY tanggal_pensiun ASC LIMIT 5");
$three_months_later = date('Y-m-d', strtotime('+3 months'));
$notification_res = $mysqli->query("SELECT nama_lengkap, tanggal_pensiun FROM pensiunan WHERE tanggal_pensiun BETWEEN '$today' AND '$three_months_later' ORDER BY tanggal_pensiun ASC");
$notification_count = $notification_res->num_rows;
$unit_kerja_list = $mysqli->query("SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");
$jabatan_list = $mysqli->query("SELECT nama_jabatan FROM jabatan ORDER BY nama_jabatan ASC");

// --- PERUBAHAN: Menambahkan 'jenis_kelamin' ke logika filter ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_unit = isset($_GET['unit_kerja']) ? trim($_GET['unit_kerja']) : '';
$filter_jabatan = isset($_GET['jabatan']) ? trim($_GET['jabatan']) : '';
$filter_gender = isset($_GET['jenis_kelamin']) ? trim($_GET['jenis_kelamin']) : ''; // Filter baru

$where_conditions[] = "status = 'aktif'";
if (!empty($search_query)) { $safe_search = $mysqli->real_escape_string($search_query); $where_conditions[] = "(nama_lengkap LIKE '%$safe_search%' OR nip LIKE '%$safe_search%')"; }
if (!empty($filter_unit)) { $safe_unit = $mysqli->real_escape_string($filter_unit); $where_conditions[] = "unit_kerja_terakhir = '$safe_unit'"; }
if (!empty($filter_jabatan)) { $safe_jabatan = $mysqli->real_escape_string($filter_jabatan); $where_conditions[] = "jabatan_terakhir = '$safe_jabatan'"; }
if (!empty($filter_gender)) { $safe_gender = $mysqli->real_escape_string($filter_gender); $where_conditions[] = "jenis_kelamin = '$safe_gender'"; } // Kondisi baru

$search_condition = "";
if (count($where_conditions) > 0) { $search_condition = " WHERE " . implode(' AND ', $where_conditions); }
$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$total_records_sql = "SELECT COUNT(id) as total FROM pensiunan" . $search_condition;
$total_records_res = $mysqli->query($total_records_sql);
$total_records = $total_records_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$sql = "SELECT id, nama_lengkap, nip, jabatan_terakhir, tanggal_pensiun FROM pensiunan" . $search_condition . " ORDER BY nama_lengkap ASC LIMIT $records_per_page OFFSET $offset";
$result = $mysqli->query($sql);

// --- PERUBAHAN: Menambahkan 'jenis_kelamin' ke parameter URL ---
$query_params = http_build_query([
    'search' => $search_query,
    'unit_kerja' => $filter_unit,
    'jabatan' => $filter_jabatan,
    'jenis_kelamin' => $filter_gender
]);
?>

<?php if ($notification_count > 0): ?>
<div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 rounded-lg shadow-md mb-8" role="alert">
    <div class="flex">
        <div class="py-1"><i class="fas fa-bell fa-2x mr-4"></i></div>
        <div>
            <p class="font-bold">Pengingat: Ada <?php echo $notification_count; ?> pegawai yang akan pensiun dalam 3 bulan ke depan.</p>
            <ul class="list-disc list-inside mt-2 text-sm">
                <?php mysqli_data_seek($notification_res, 0); while($notif = $notification_res->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($notif['nama_lengkap']); ?> (<?php echo date("d M Y", strtotime($notif['tanggal_pensiun'])); ?>)</li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mb-8">
    <h2 class="text-2xl font-semibold text-gray-700 mb-4">Ringkasan Data Interaktif</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg flex items-center"><div class="bg-blue-500 text-white rounded-full h-16 w-16 flex items-center justify-center text-2xl mr-6">
            <i class="fas fa-users"></i></div><div><p class="text-3xl font-bold text-gray-800"><?php echo $total_pensiunan; ?></p><p class="text-gray-500">Total Pensiunan</p></div></div>
        <div class="bg-white p-6 rounded-lg shadow-lg flex items-center"><div class="bg-green-500 text-white rounded-full h-16 w-16 flex items-center justify-center text-2xl mr-6">
            <i class="fas fa-user-clock"></i></div><div><p class="text-3xl font-bold text-gray-800"><?php echo $upcoming_retirements_count; ?></p><p class="text-gray-500">Pensiun 1 Thn ke Depan</p></div></div>
        <div class="bg-white p-6 rounded-lg shadow-lg flex items-center"><div class="bg-yellow-500 text-white rounded-full h-16 w-16 flex items-center justify-center text-2xl mr-6">
            <i class="fas fa-folder-open"></i></div><div><p class="text-3xl font-bold text-gray-800"><?php echo $total_dokumen; ?></p><p class="text-gray-500">Total Dokumen Diarsipkan</p></div></div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="font-semibold text-gray-700 mb-4">Jumlah Pensiunan per Unit Kerja</h3>
            <div class="h-80"><canvas id="unitKerjaChart"></canvas></div>
        </div>
        <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-lg">
            <h3 class="font-semibold text-gray-700 mb-4">Komposisi Gender</h3>
            <div class="h-80"><canvas id="genderChart"></canvas></div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-lg">
        <h3 class="font-semibold text-gray-700 mb-4">Tren Pensiun per Tahun</h3>
        <div class="h-80"><canvas id="trenPensiunChart"></canvas></div>
    </div>
</div>

<div id="tabel-pensiunan" class="bg-white p-6 rounded-lg shadow-lg mt-8">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <h2 class="text-2xl font-semibold text-gray-700">Data Lengkap Pensiunan</h2>
        <div class="flex gap-2">
            <a href="export_csv.php?<?php echo $query_params; ?>" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                <i class="fas fa-file-excel mr-2"></i>Ekspor ke CSV</a>
            <a href="form_pensiunan.php?action=add" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                <i class="fas fa-plus mr-2"></i>Tambah Data Baru</a>
        </div>
    </div>
    <form method="get" action="index.php" class="mb-6 bg-gray-50 p-4 rounded-lg border">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2"><label for="search" class="block text-sm font-medium text-gray-700 mb-1">Cari Nama / NIP</label>
            <input type="text" name="search" id="search" placeholder="Ketik di sini..." class="w-full border rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($search_query); ?>"></div>
            <div><label for="unit_kerja" class="block text-sm font-medium text-gray-700 mb-1">Unit Kerja</label><select name="unit_kerja" id="unit_kerja" class="w-full border rounded-md p-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="">Semua Unit</option><?php mysqli_data_seek($unit_kerja_list, 0); while($unit = $unit_kerja_list->fetch_assoc()): ?><option value="<?php echo htmlspecialchars($unit['nama_unit']); ?>" <?php echo ($filter_unit == $unit['nama_unit']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($unit['nama_unit']); ?></option><?php endwhile; ?></select></div>
            <div><label for="jabatan" class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label><select name="jabatan" id="jabatan" class="w-full border rounded-md p-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500"><option value="">Semua Jabatan</option><?php mysqli_data_seek($jabatan_list, 0); while($jabatan = $jabatan_list->fetch_assoc()): ?><option value="<?php echo htmlspecialchars($jabatan['nama_jabatan']); ?>" <?php echo ($filter_jabatan == $jabatan['nama_jabatan']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($jabatan['nama_jabatan']); ?></option><?php endwhile; ?></select></div>
            <div class="md:col-span-4 flex justify-end gap-2 mt-2"><a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">Reset Filter</a><button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"><i class="fas fa-filter mr-2"></i>Terapkan Filter</button></div>
        </div>
    </form>
    
    <div class="overflow-x-auto">
        <table class="min-w-full bg-white">
            <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal"><tr><th class="py-3 px-6 text-left">No</th><th class="py-3 px-6 text-left">Nama Lengkap</th><th class="py-3 px-6 text-left">NIP</th><th class="py-3 px-6 text-left">Jabatan Terakhir</th><th class="py-3 px-6 text-center">Tgl Pensiun</th><th class="py-3 px-6 text-center">Aksi</th></tr></thead>
            <tbody class="text-gray-600 text-sm font-light">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
                    <tr class="border-b border-gray-200 hover:bg-gray-100">
                        <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo $no++; ?></td>
                        <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                        <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['nip']); ?></td>
                        <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['jabatan_terakhir']); ?></td>
                        <td class="py-3 px-6 text-center"><?php echo !empty($row['tanggal_pensiun']) ? date("d-m-Y", strtotime($row['tanggal_pensiun'])) : '-'; ?></td>
                        <td class="py-3 px-6 text-center"><div class="flex item-center justify-center">
                            <a href="detail_pensiunan.php?id=<?php echo $row['id']; ?>" class="w-8 h-8 rounded-full bg-blue-200 text-blue-600 flex items-center justify-center mr-2 transform hover:scale-110" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                            <a href="form_pensiunan.php?action=edit&id=<?php echo $row['id']; ?>" class="w-8 h-8 rounded-full bg-green-200 text-green-600 flex items-center justify-center mr-2 transform hover:scale-110" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                           <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nama_lengkap']); ?>')" class="text-red-500 hover:text-red-700" title="Hapus">
  <i class="fas fa-trash-alt"></i>
</a>

                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center py-6">Tidak ada data untuk ditampilkan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-6 flex justify-between items-center">
        <div class="text-sm text-gray-600">Menampilkan <?php echo $result->num_rows; ?> dari <?php echo $total_records; ?> data</div>
        <?php if ($total_pages > 1): ?>
        <div class="flex items-center">
            <a href="?page=<?php echo $current_page - 1; ?>&<?php echo $query_params; ?>" class="py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-l-md <?php echo ($current_page <= 1) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Sebelumnya</a>
            <?php 
            $range = 1;
            for ($i = 1; $i <= $total_pages; $i++):
                if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
                    $active_class = ($current_page == $i) ? 'bg-blue-500 border-blue-500 text-white' : 'bg-white hover:bg-gray-100';
                    echo "<a href='?page=$i&$query_params' class='py-2 px-4 border-t border-b border-gray-300 text-gray-600 $active_class'>$i</a>";
                elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1):
                    echo "<span class='py-2 px-4 border-t border-b border-gray-300 text-gray-600 bg-white'>...</span>";
                endif;
            endfor;
            ?>
            <a href="?page=<?php echo $current_page + 1; ?>&<?php echo $query_params; ?>" class="py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-r-md <?php echo ($current_page >= $total_pages) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Berikutnya</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<div id="confirmDeleteModal" class="fixed inset-0 z-50 bg-black bg-opacity-40 hidden flex items-center justify-center">
  <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">Konfirmasi Hapus</h2>
    <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus data ini? Data akan dipindahkan ke arsip dan tidak muncul di daftar utama.</p>
    <div class="flex justify-end space-x-2">
      <button onclick="closeDeleteModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-2 px-4 rounded">Batal</button>
      <a id="confirmDeleteBtn" href="#" class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded">Ya, Hapus</a>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Fungsi helper untuk menangani klik pada grafik
    const handleChartClick = (event, elements, chart, filterKey) => {
        if (elements.length > 0) {
            const clickedIndex = elements[0].index;
            const label = chart.data.labels[clickedIndex];
            const currentParams = new URLSearchParams(window.location.search);
            currentParams.set(filterKey, label);
            currentParams.set('page', 1);
            window.location.href = 'index.php?' + currentParams.toString();

             async function loadTableData(url, shouldScroll = false) {
        tableContainer.innerHTML = `<div class="text-center py-10"><i class="fas fa-spinner fa-spin fa-3x text-gray-400"></i></div>`;
        try {
            const response = await fetch(url);
            if (!response.ok) throw new Error('Network response was not ok.');
            const html = await response.text();
            tableContainer.innerHTML = html;
            const params = new URL(url).searchParams;
            const newUrl = 'index.php?' + params.toString();
            history.pushState(null, '', newUrl);
            exportCsvLink.href = 'export_csv.php?' + params.toString();

            if (shouldScroll) {
                dataPensiunanSection.scrollIntoView({ behavior: 'smooth' });
            }

        } catch (error) {
            tableContainer.innerHTML = `<div class="text-center py-10 text-red-500">Gagal memuat data.</div>`;
            console.error('Fetch error:', error);
        }
    }

    filterForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const params = new URLSearchParams(formData);
        loadTableData('get_pensiunan_table.php?' + params.toString(), true); // Scroll saat filter
    });

    resetBtn.addEventListener('click', function(event) {
        event.preventDefault();
        filterForm.reset();
        loadTableData('get_pensiunan_table.php');
    });

    tableContainer.addEventListener('click', function(event) {
        if (event.target.tagName === 'A' && event.target.classList.contains('pagination-link')) {
            event.preventDefault();
            const url = event.target.href;
            if (url) {
                const urlObject = new URL(url);
                const targetUrl = 'get_pensiunan_table.php' + urlObject.search;
                loadTableData(targetUrl, true); // Scroll saat ganti halaman
            }
        }
    });

    const initialParams = new URLSearchParams(window.location.search);
    loadTableData('get_pensiunan_table.php?' + initialParams.toString());

    const handleChartClick = (event, elements, chart, filterKey) => {
        if (elements.length > 0) {
            const clickedIndex = elements[0].index;
            const label = chart.data.labels[clickedIndex];
            filterForm.reset();
            const filterInput = filterForm.querySelector(`[name="${filterKey}"]`);
            if(filterInput) {
                filterInput.value = label;
            }
            filterForm.dispatchEvent(new Event('submit'));
document.getElementById("tabel-pensiunan").scrollIntoView({ behavior: "smooth" });
        }
    };
        }
    };
    const handleChartHover = (event, elements, chart) => {
        chart.canvas.style.cursor = elements.length > 0 ? 'pointer' : 'default';
    };

    // Grafik Gender (Doughnut Chart)
    const genderChartData = <?php echo $gender_chart_data_json; ?>;
    const ctxGender = document.getElementById('genderChart').getContext('2d');
    if (genderChartData.data.some(d => d > 0)) {
        const genderChart = new Chart(ctxGender, {
            type: 'doughnut',
            data: { labels: genderChartData.labels, datasets: [{ data: genderChartData.data, backgroundColor: ['rgba(59, 130, 246, 0.8)', 'rgba(236, 72, 153, 0.8)'], borderColor: ['#fff'], borderWidth: 2 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, onClick: (event, elements) => handleChartClick(event, elements, genderChart, 'jenis_kelamin'), onHover: (event, elements) => handleChartHover(event, elements, genderChart) }
        });
    } else { document.getElementById('genderChart').parentElement.innerHTML = '<p class="text-center text-gray-500 h-full flex items-center justify-center">Data gender tidak tersedia.</p>'; }

    // Grafik Unit Kerja (Bar Chart)
    const unitKerjaData = <?php echo $unit_kerja_chart_data_json; ?>;
    const ctxUnitKerja = document.getElementById('unitKerjaChart').getContext('2d');
    if (unitKerjaData.labels.length > 0) {
        const unitKerjaChart = new Chart(ctxUnitKerja, {
            type: 'bar',
            data: { labels: unitKerjaData.labels, datasets: [{ label: 'Jumlah Pensiunan', data: unitKerjaData.data, backgroundColor: 'rgba(22, 163, 74, 0.7)', borderColor: 'rgba(22, 163, 74, 1)', borderWidth: 1 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } }, onClick: (event, elements) => handleChartClick(event, elements, unitKerjaChart, 'unit_kerja'), onHover: (event, elements) => handleChartHover(event, elements, unitKerjaChart) }
        });
    } else { document.getElementById('unitKerjaChart').parentElement.innerHTML = '<p class="text-center text-gray-500 h-full flex items-center justify-center">Data unit kerja tidak tersedia.</p>'; }

    // Grafik Tren Pensiun (Line Chart)
    const trenPensiunData = <?php echo $tren_pensiun_chart_data_json; ?>;
    const ctxTrenPensiun = document.getElementById('trenPensiunChart').getContext('2d');
    if (trenPensiunData.labels.length > 1) {
        new Chart(ctxTrenPensiun, {
            type: 'line',
            data: { labels: trenPensiunData.labels, datasets: [{ label: 'Jumlah Pensiun', data: trenPensiunData.data, fill: true, backgroundColor: 'rgba(249, 115, 22, 0.2)', borderColor: 'rgba(249, 115, 22, 1)', tension: 0.1 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
        });
    } else { document.getElementById('trenPensiunChart').parentElement.innerHTML = '<p class="text-center text-gray-500 h-full flex items-center justify-center">Data tren pensiun tidak cukup untuk ditampilkan.</p>'; }
});

function confirmDelete(id, name) {
    const modal = document.getElementById('confirmDeleteModal');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    confirmBtn.href = `hapus.php?id=${id}`;
    modal.classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('confirmDeleteModal').classList.add('hidden');
}
    
    // Membuat konten untuk modal secara dinamis
    modal.innerHTML = `
        <div class="bg-white p-6 rounded-lg shadow-xl max-w-sm mx-auto" onclick="event.stopPropagation();">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Konfirmasi Penghapusan</h3>
            <p class="text-gray-600 mb-6">Apakah Anda yakin ingin menghapus data ini? Data yang Anda hapus akan masuk ke Arsip</p>
            <div class="flex justify-end gap-4">
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Batal</button>
                <a href="index.php?action=delete&id=${id}" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Hapus</a>
            </div>
        </div>
    `;

    // Atur agar modal tertutup jika area luar diklik
    modal.setAttribute('onclick', 'closeModal()');
    
    // Tampilkan modal
    modal.classList.remove('hidden');

function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
}

function archiveData(id, element) {
    if (!confirm("Apakah Anda yakin ingin mengarsipkan data ini?")) return;

    fetch(`archive_pensiunan.php?id=${id}`, {
        method: 'GET',
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Hapus baris dari tabel secara langsung
            const row = element.closest('tr');
            row.remove();
        } else {
            alert("Gagal mengarsipkan data.");
        }
    })
    .catch(err => {
        console.error(err);
        alert("Terjadi kesalahan.");
    });
}

function openArsipModal(id) {
    const modal = document.getElementById('confirmArsipModal');
    const confirmBtn = document.getElementById('confirmArsipBtn');
    confirmBtn.href = `index.php?action=arsip&id=${id}`;
    modal.classList.remove('hidden');
}

function closeArsipModal() {
    const modal = document.getElementById('confirmArsipModal');
    modal.classList.add('hidden');
}
</script>

<?php
// Memanggil template footer
require_once 'template_footer.php';
?>
