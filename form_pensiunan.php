<?php
require_once 'config.php';

// Keamanan: Pastikan pengguna sudah login
if (!isset($_SESSION['user_id'])) {
    header("location: login.php");
    exit;
}

$action = $_GET['action'] ?? 'add';
$id = $_GET['id'] ?? null;
$errors = [];
$pensiunan_data_for_form = []; // Untuk mengisi ulang form jika ada error

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $action = $_POST['action'];
    
    $data = [
        'nama_lengkap' => $_POST['nama_lengkap'], 'nip' => $_POST['nip'],
        'tanggal_lahir' => !empty($_POST['tanggal_lahir']) ? $_POST['tanggal_lahir'] : null,
        'tanggal_pensiun' => !empty($_POST['tanggal_pensiun']) ? $_POST['tanggal_pensiun'] : null,
        'jabatan_terakhir' => $_POST['jabatan_terakhir'], 'unit_kerja_terakhir' => $_POST['unit_kerja_terakhir'],
        'jenis_kelamin' => $_POST['jenis_kelamin'], 'alamat' => $_POST['alamat'],
        'nomor_telepon' => $_POST['nomor_telepon'], 'alamat_email' => $_POST['alamat_email'],
        'pangkat_golongan' => $_POST['pangkat_golongan'],
        'tmt_golongan' => !empty($_POST['tmt_golongan']) ? $_POST['tmt_golongan'] : null,
        'tmt_jabatan' => !empty($_POST['tmt_jabatan']) ? $_POST['tmt_jabatan'] : null
    ];
    
    $pensiunan_data_for_form = $data;

    // Validasi Sisi Server (PHP)
    if (empty($data['nama_lengkap'])) { $errors['nama_lengkap'] = 'Nama lengkap tidak boleh kosong.'; }
    if (!is_numeric($data['nip']) || strlen($data['nip']) != 18) { $errors['nip'] = 'NIP harus terdiri dari 18 angka.'; }
    if (!empty($data['tanggal_lahir']) && !empty($data['tanggal_pensiun'])) {
        if (strtotime($data['tanggal_pensiun']) <= strtotime($data['tanggal_lahir'])) {
            $errors['tanggal_pensiun'] = 'Tanggal pensiun harus setelah tanggal lahir.';
        }
    }

    if (empty($errors)) {
        try {
            if ($action == 'add') {
                $sql = "INSERT INTO pensiunan (nama_lengkap, nip, tanggal_lahir, tanggal_pensiun, jabatan_terakhir, unit_kerja_terakhir, jenis_kelamin, alamat, nomor_telepon, alamat_email, pangkat_golongan, tmt_golongan, tmt_jabatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssssssssssss", $data['nama_lengkap'], $data['nip'], $data['tanggal_lahir'], $data['tanggal_pensiun'], $data['jabatan_terakhir'], $data['unit_kerja_terakhir'], $data['jenis_kelamin'], $data['alamat'], $data['nomor_telepon'], $data['alamat_email'], $data['pangkat_golongan'], $data['tmt_golongan'], $data['tmt_jabatan']);
            } else { // edit
                $sql = "UPDATE pensiunan SET nama_lengkap=?, nip=?, tanggal_lahir=?, tanggal_pensiun=?, jabatan_terakhir=?, unit_kerja_terakhir=?, jenis_kelamin=?, alamat=?, nomor_telepon=?, alamat_email=?, pangkat_golongan=?, tmt_golongan=?, tmt_jabatan=? WHERE id=?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssssssssssssi", $data['nama_lengkap'], $data['nip'], $data['tanggal_lahir'], $data['tanggal_pensiun'], $data['jabatan_terakhir'], $data['unit_kerja_terakhir'], $data['jenis_kelamin'], $data['alamat'], $data['nomor_telepon'], $data['alamat_email'], $data['pangkat_golongan'], $data['tmt_golongan'], $data['tmt_jabatan'], $id);
            }
            $stmt->execute();
            $record_id = ($action == 'add') ? $mysqli->insert_id : $id;
            $log_action = ($action == 'add') ? 'CREATE_PENSIUNAN' : 'UPDATE_PENSIUNAN';
            $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Data pensiunan berhasil disimpan!'];
            header("location: index.php");
            exit;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { $errors['nip'] = "NIP sudah terdaftar."; } 
            else { $errors['form'] = "Terjadi kesalahan pada database."; }
        }
    }
}

if ($action === 'edit' && $id && empty($_POST)) {
    $result = $mysqli->query("SELECT * FROM pensiunan WHERE id = $id");
    if ($result->num_rows == 1) {
        $pensiunan_data_for_form = $result->fetch_assoc();
    } else {
        // Handle data not found for editing
    }
}

$jabatan_list = $mysqli->query("SELECT nama_jabatan FROM jabatan ORDER BY nama_jabatan ASC");
$unit_kerja_list = $mysqli->query("SELECT nama_unit FROM unit_kerja ORDER BY nama_unit ASC");

$page_title = ($action == 'add' ? 'Tambah' : 'Edit') . ' Data Pensiunan';
$active_page = '';
require_once 'template_header.php';
?>

<div class="bg-white p-8 rounded-lg shadow-lg max-w-4xl mx-auto">
    <h2 class="text-2xl font-semibold text-gray-700 mb-6"><?php echo ($action == 'add' ? 'Tambah' : 'Edit'); ?> Data Pensiunan</h2>
    
    <?php if (!empty($errors['form'])): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded mb-4"><?php echo $errors['form']; ?></div>
    <?php endif; ?>

    <form id="pensiunan-form" method="post" action="form_pensiunan.php?action=<?php echo $action; ?><?php if($action == 'edit') echo '&id='.$id; ?>" novalidate>
        <input type="hidden" name="action" value="<?php echo $action; ?>">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Kolom Kiri -->
            <div>
                <div class="mb-4">
                    <label for="nama_lengkap" class="block text-gray-700 text-sm font-bold mb-2">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" id="nama_lengkap" value="<?php echo htmlspecialchars($pensiunan_data_for_form['nama_lengkap'] ?? ''); ?>" class="input-field" required>
                    <span class="error-text"><?php echo $errors['nama_lengkap'] ?? ''; ?></span>
                </div>
                <div class="mb-4">
                    <label for="nip" class="block text-gray-700 text-sm font-bold mb-2">NIP</label>
                    <input type="text" name="nip" id="nip" value="<?php echo htmlspecialchars($pensiunan_data_for_form['nip'] ?? ''); ?>" class="input-field" required maxlength="18" pattern="\d{18}">
                    <span id="nip_error" class="error-text"><?php echo $errors['nip'] ?? ''; ?></span>
                </div>
                <div class="mb-4">
                    <label for="tanggal_lahir" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Lahir</label>
                    <input type="date" name="tanggal_lahir" id="tanggal_lahir" value="<?php echo htmlspecialchars($pensiunan_data_for_form['tanggal_lahir'] ?? ''); ?>" class="input-field">
                </div>
                <div class="mb-4">
                    <label for="tanggal_pensiun" class="block text-gray-700 text-sm font-bold mb-2">Tanggal Pensiun</label>
                    <input type="date" name="tanggal_pensiun" id="tanggal_pensiun" value="<?php echo htmlspecialchars($pensiunan_data_for_form['tanggal_pensiun'] ?? ''); ?>" class="input-field">
                    <span id="tanggal_pensiun_error" class="error-text"><?php echo $errors['tanggal_pensiun'] ?? ''; ?></span>
                </div>
                <div class="mb-4">
                    <label for="jenis_kelamin" class="block text-gray-700 text-sm font-bold mb-2">Jenis Kelamin</label>
                    <select name="jenis_kelamin" id="jenis_kelamin" class="input-field">
                        <option value="">-- Pilih --</option>
                        <option value="Laki-laki" <?php echo (($pensiunan_data_for_form['jenis_kelamin'] ?? '') == 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                        <option value="Perempuan" <?php echo (($pensiunan_data_for_form['jenis_kelamin'] ?? '') == 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
                    </select>
                </div>
                 <div class="mb-4">
                    <label for="alamat" class="block text-gray-700 text-sm font-bold mb-2">Alamat</label>
                    <textarea name="alamat" id="alamat" rows="3" class="input-field"><?php echo htmlspecialchars($pensiunan_data_for_form['alamat'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Kolom Kanan -->
            <div>
                <div class="mb-4">
                    <label for="jabatan_terakhir" class="block text-gray-700 text-sm font-bold mb-2">Jabatan Terakhir</label>
                    <select name="jabatan_terakhir" id="jabatan_terakhir" class="input-field">
                        <option value="">-- Pilih Jabatan --</option>
                        <?php mysqli_data_seek($jabatan_list, 0); while($jabatan = $jabatan_list->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($jabatan['nama_jabatan']); ?>" <?php echo (($pensiunan_data_for_form['jabatan_terakhir'] ?? '') == $jabatan['nama_jabatan']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($jabatan['nama_jabatan']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="unit_kerja_terakhir" class="block text-gray-700 text-sm font-bold mb-2">Unit Kerja Terakhir</label>
                     <select name="unit_kerja_terakhir" id="unit_kerja_terakhir" class="input-field">
                        <option value="">-- Pilih Unit Kerja --</option>
                        <?php mysqli_data_seek($unit_kerja_list, 0); while($unit = $unit_kerja_list->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($unit['nama_unit']); ?>" <?php echo (($pensiunan_data_for_form['unit_kerja_terakhir'] ?? '') == $unit['nama_unit']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($unit['nama_unit']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="mb-4">
                    <label for="pangkat_golongan" class="block text-gray-700 text-sm font-bold mb-2">Pangkat / Golongan</label>
                    <input type="text" name="pangkat_golongan" id="pangkat_golongan" value="<?php echo htmlspecialchars($pensiunan_data_for_form['pangkat_golongan'] ?? ''); ?>" class="input-field">
                </div>
                <div class="mb-4">
                    <label for="tmt_golongan" class="block text-gray-700 text-sm font-bold mb-2">TMT Golongan</label>
                    <input type="date" name="tmt_golongan" id="tmt_golongan" value="<?php echo htmlspecialchars($pensiunan_data_for_form['tmt_golongan'] ?? ''); ?>" class="input-field">
                </div>
                <div class="mb-4">
                    <label for="tmt_jabatan" class="block text-gray-700 text-sm font-bold mb-2">TMT Jabatan</label>
                    <input type="date" name="tmt_jabatan" id="tmt_jabatan" value="<?php echo htmlspecialchars($pensiunan_data_for_form['tmt_jabatan'] ?? ''); ?>" class="input-field">
                </div>
                <div class="mb-4">
                    <label for="nomor_telepon" class="block text-gray-700 text-sm font-bold mb-2">Nomor Telepon</label>
                    <input type="tel" name="nomor_telepon" id="nomor_telepon" value="<?php echo htmlspecialchars($pensiunan_data_for_form['nomor_telepon'] ?? ''); ?>" class="input-field">
                </div>
                 <div class="mb-4">
                    <label for="alamat_email" class="block text-gray-700 text-sm font-bold mb-2">Alamat Email</label>
                    <input type="email" name="alamat_email" id="alamat_email" value="<?php echo htmlspecialchars($pensiunan_data_for_form['alamat_email'] ?? ''); ?>" class="input-field">
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <a href="index.php" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-4 rounded mr-2">Batal</a>
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Simpan Data</button>
        </div>
    </form>
</div>

<style>
    .input-field { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .error-text { color: #e53e3e; font-size: 0.875rem; margin-top: 0.25rem; display: block; min-height: 1.25rem; }
    .input-error { border-color: #e53e3e; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('pensiunan-form');
    const nipInput = document.getElementById('nip');
    const tglLahirInput = document.getElementById('tanggal_lahir');
    const tglPensiunInput = document.getElementById('tanggal_pensiun');
    
    const nipError = document.getElementById('nip_error');
    const tglPensiunError = document.getElementById('tanggal_pensiun_error');

    function validateNIP() {
        const nipValue = nipInput.value;
        if (nipValue && !/^\d{18}$/.test(nipValue)) {
            nipError.textContent = 'NIP harus terdiri dari 18 angka.';
            nipInput.classList.add('input-error');
            return false;
        }
        if (nipError.textContent === 'NIP harus terdiri dari 18 angka.') {
            nipError.textContent = '';
        }
        nipInput.classList.remove('input-error');
        return true;
    }

    function validateDates() {
        const tglLahir = tglLahirInput.value;
        const tglPensiun = tglPensiunInput.value;
        if (tglLahir && tglPensiun && tglPensiun <= tglLahir) {
            tglPensiunError.textContent = 'Tanggal pensiun harus setelah tanggal lahir.';
            tglPensiunInput.classList.add('input-error');
            return false;
        }
        if (tglPensiunError.textContent === 'Tanggal pensiun harus setelah tanggal lahir.') {
            tglPensiunError.textContent = '';
        }
        tglPensiunInput.classList.remove('input-error');
        return true;
    }

    nipInput.addEventListener('input', validateNIP);
    tglLahirInput.addEventListener('change', validateDates);
    tglPensiunInput.addEventListener('change', validateDates);

    form.addEventListener('submit', function(event) {
        const isNipValid = validateNIP();
        const areDatesValid = validateDates();
        
        if (!isNipValid || !areDatesValid) {
            event.preventDefault();
        }
    });
});
</script>

<?php require_once 'template_footer.php'; ?>
