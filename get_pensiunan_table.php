<?php
require_once 'config.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    exit("Akses ditolak.");
}

// Menangani aksi arsip (soft delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_to_archive = intval($_GET['id']);
    $stmt = $mysqli->prepare("UPDATE pensiunan SET status = 'arsip' WHERE id = ?");
    $stmt->bind_param("i", $id_to_archive);
    if ($stmt->execute()) {
        log_audit('ARCHIVE_PENSIUNAN', 'pensiunan', $id_to_archive);
    }
    $stmt->close();
}

// --- Logika Filter, Pencarian, dan Paginasi ---
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_unit = isset($_GET['unit_kerja']) ? trim($_GET['unit_kerja']) : '';
$filter_jabatan = isset($_GET['jabatan']) ? trim($_GET['jabatan']) : '';
$filter_gender = isset($_GET['jenis_kelamin']) ? trim($_GET['jenis_kelamin']) : '';

$where_conditions = [];
$where_conditions[] = "status = 'aktif'"; // Kondisi default untuk hanya menampilkan data aktif

if (!empty($search_query)) { $safe_search = $mysqli->real_escape_string($search_query); $where_conditions[] = "(nama_lengkap LIKE '%$safe_search%' OR nip LIKE '%$safe_search%')"; }
if (!empty($filter_unit)) { $safe_unit = $mysqli->real_escape_string($filter_unit); $where_conditions[] = "unit_kerja_terakhir = '$safe_unit'"; }
if (!empty($filter_jabatan)) { $safe_jabatan = $mysqli->real_escape_string($filter_jabatan); $where_conditions[] = "jabatan_terakhir = '$safe_jabatan'"; }
if (!empty($filter_gender)) { $safe_gender = $mysqli->real_escape_string($filter_gender); $where_conditions[] = "jenis_kelamin = '$safe_gender'"; }

// PERBAIKAN: Logika WHERE yang lebih rapi
$search_condition = " WHERE " . implode(' AND ', $where_conditions);

$records_per_page = 10;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;

$total_records_sql = "SELECT COUNT(id) as total FROM pensiunan" . $search_condition;
$total_records_res = $mysqli->query($total_records_sql);
$total_records = $total_records_res->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
    $offset = ($current_page - 1) * $records_per_page;
}

$sql = "SELECT id, nama_lengkap, nip, jabatan_terakhir, tanggal_pensiun FROM pensiunan" . $search_condition . " ORDER BY nama_lengkap ASC LIMIT $records_per_page OFFSET $offset";
$result = $mysqli->query($sql);

$query_params = http_build_query([
    'search' => $search_query,
    'unit_kerja' => $filter_unit,
    'jabatan' => $filter_jabatan,
    'jenis_kelamin' => $filter_gender
]);
?>

<!-- Output HTML Tabel dan Paginasi -->
<div class="overflow-x-auto">
    <table class="min-w-full bg-white">
        <thead class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
            <tr>
                <th class="py-3 px-6 text-left">No</th>
                <th class="py-3 px-6 text-left">Nama Lengkap</th>
                <th class="py-3 px-6 text-left">NIP</th>
                <th class="py-3 px-6 text-left">Jabatan Terakhir</th>
                <th class="py-3 px-6 text-center">Tgl Pensiun</th>
                <th class="py-3 px-6 text-center">Aksi</th>
            </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php $no = $offset + 1; while($row = $result->fetch_assoc()): ?>
                <tr class="border-b border-gray-200 hover:bg-gray-100">
                    <td class="py-3 px-6 text-left whitespace-nowrap"><?php echo $no++; ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['nip']); ?></td>
                    <td class="py-3 px-6 text-left"><?php echo htmlspecialchars($row['jabatan_terakhir']); ?></td>
                    <td class="py-3 px-6 text-center"><?php echo !empty($row['tanggal_pensiun']) ? date("d-m-Y", strtotime($row['tanggal_pensiun'])) : '-'; ?></td>
                    <td class="py-3 px-6 text-center">
                        <div class="flex item-center justify-center">
                            <a href="detail_pensiunan.php?id=<?php echo $row['id']; ?>" class="w-8 h-8 rounded-full bg-blue-200 text-blue-600 flex items-center justify-center mr-2 transform hover:scale-110" title="Lihat Detail"><i class="fas fa-eye"></i></a>
                            <a href="form_pensiunan.php?action=edit&id=<?php echo $row['id']; ?>" class="w-8 h-8 rounded-full bg-green-200 text-green-600 flex items-center justify-center mr-2 transform hover:scale-110" title="Edit"><i class="fas fa-pencil-alt"></i></a>
                            <a href="javascript:void(0);" onclick="confirmDelete(<?php echo $row['id']; ?>)" class="w-8 h-8 rounded-full bg-red-200 text-red-600 flex items-center justify-center transform hover:scale-110" title="Arsipkan"><i class="fas fa-archive"></i></a>
                        </div>
                    </td>
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
        <a href="?page=<?php echo $current_page - 1; ?>&<?php echo $query_params; ?>" class="pagination-link py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-l-md <?php echo ($current_page <= 1) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Sebelumnya</a>
        <?php 
        $range = 1;
        for ($i = 1; $i <= $total_pages; $i++):
            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)):
                $active_class = ($current_page == $i) ? 'bg-blue-500 border-blue-500 text-white' : 'bg-white hover:bg-gray-100';
                echo "<a href='?page=$i&$query_params' class='pagination-link py-2 px-4 border-t border-b border-gray-300 text-gray-600 $active_class'>$i</a>";
            elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1):
                echo "<span class='py-2 px-4 border-t border-b border-gray-300 text-gray-600 bg-white'>...</span>";
            endif;
        endfor;
        ?>
        <a href="?page=<?php echo $current_page + 1; ?>&<?php echo $query_params; ?>" class="pagination-link py-2 px-4 bg-white border border-gray-300 text-gray-600 hover:bg-gray-100 rounded-r-md <?php echo ($current_page >= $total_pages) ? 'cursor-not-allowed bg-gray-100' : ''; ?>">Berikutnya</a>
    </div>
    <?php endif; ?>
</div>
