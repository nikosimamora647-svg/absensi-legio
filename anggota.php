<?php
$page_title = 'Kelola Anggota Presidium';
require_once __DIR__ . '/includes/header.php';

$message = '';
$message_type = 'success';

// Handle Tambah / Edit Anggota
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $nomor_anggota = trim($_POST['nomor_anggota'] ?? '');
    $jenis_anggota = $_POST['jenis_anggota'] ?? 'Aktif';
    $jabatan = $_POST['jabatan'] ?? 'Anggota';
    $jenis_kelamin = $_POST['jenis_kelamin'] ?? 'P';
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $tanggal_bergabung = $_POST['tanggal_bergabung'] ?: date('Y-m-d');
    $status = $_POST['status'] ?? 'Aktif';

    if (empty($nama_lengkap)) {
        $message = 'Nama lengkap anggota wajib diisi.';
        $message_type = 'danger';
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action === 'create') {
                    // Auto generate nomor anggota jika kosong
                    if (empty($nomor_anggota)) {
                        $count = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn() + 1;
                        $nomor_anggota = 'LM-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                    }

                    $stmt = $pdo->prepare("INSERT INTO anggota 
                        (nomor_anggota, nama_lengkap, jenis_anggota, jabatan, jenis_kelamin, no_telepon, alamat, tanggal_bergabung, status) 
                        VALUES (:no, :nama, :jenis, :jabatan, :jk, :telp, :alamat, :tgl, :status)");
                    $stmt->execute([
                        'no' => $nomor_anggota,
                        'nama' => $nama_lengkap,
                        'jenis' => $jenis_anggota,
                        'jabatan' => $jabatan,
                        'jk' => $jenis_kelamin,
                        'telp' => $no_telepon,
                        'alamat' => $alamat,
                        'tgl' => $tanggal_bergabung,
                        'status' => $status
                    ]);
                    $message = "Anggota baru <strong>$nama_lengkap</strong> ($nomor_anggota) berhasil didaftarkan!";
                    $message_type = 'success';
                } elseif ($action === 'update') {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("UPDATE anggota SET 
                        nomor_anggota = :no, 
                        nama_lengkap = :nama, 
                        jenis_anggota = :jenis, 
                        jabatan = :jabatan, 
                        jenis_kelamin = :jk, 
                        no_telepon = :telp, 
                        alamat = :alamat, 
                        tanggal_bergabung = :tgl, 
                        status = :status 
                        WHERE id = :id");
                    $stmt->execute([
                        'id' => $id,
                        'no' => $nomor_anggota,
                        'nama' => $nama_lengkap,
                        'jenis' => $jenis_anggota,
                        'jabatan' => $jabatan,
                        'jk' => $jenis_kelamin,
                        'telp' => $no_telepon,
                        'alamat' => $alamat,
                        'tgl' => $tanggal_bergabung,
                        'status' => $status
                    ]);
                    $message = "Data anggota <strong>$nama_lengkap</strong> berhasil diperbarui!";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Gagal menyimpan data: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Handle Hapus Anggota
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($db_connected && $pdo && $delete_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM anggota WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $message = 'Anggota berhasil dihapus dari sistem presidium.';
            $message_type = 'warning';
        } catch (PDOException $e) {
            $message = 'Gagal menghapus anggota: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Ambil Semua Anggota
$anggota_list = [];
$filter = $_GET['filter'] ?? 'semua';
if ($db_connected && $pdo) {
    try {
        $sql = "SELECT * FROM anggota ";
        if ($filter === 'aktif') {
            $sql .= "WHERE jenis_anggota = 'Aktif' AND status = 'Aktif' ";
        } elseif ($filter === 'auksiler') {
            $sql .= "WHERE jenis_anggota = 'Auksiler' AND status = 'Aktif' ";
        } elseif ($filter === 'perwira') {
            $sql .= "WHERE jabatan != 'Anggota' AND status = 'Aktif' ";
        }
        $sql .= "ORDER BY 
            CASE jabatan 
                WHEN 'Pemimpin' THEN 1 
                WHEN 'Wakil Pemimpin' THEN 2 
                WHEN 'Sekretaris' THEN 3 
                WHEN 'Bendahara' THEN 4 
                ELSE 5 
            END, nama_lengkap ASC";
        $anggota_list = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {}
}
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- Header & Tombol Tambah Anggota -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-1">
                <i class="fas fa-users-gear me-1"></i> Data Keanggotaan Presidium
            </span>
            <h1 class="font-cinzel h2 fw-bold text-dark mb-0">Daftar Legioner Legio Mariae</h1>
            <small class="text-muted">Manajemen anggota presidium dengan kapasitas dinamis & tak terbatas.</small>
        </div>

        <div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahAnggota">
                <i class="fas fa-user-plus me-1"></i> Tambah Anggota Baru
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Pencarian Cepat -->
    <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4">
        <div class="row g-2 align-items-center justify-content-between">
            <div class="col-md-6 d-flex gap-2">
                <a href="anggota.php?filter=semua" class="btn btn-sm rounded-pill <?= $filter === 'semua' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Semua (<?= count($anggota_list) ?>)
                </a>
                <a href="anggota.php?filter=aktif" class="btn btn-sm rounded-pill <?= $filter === 'aktif' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Legioner Aktif
                </a>
                <a href="anggota.php?filter=auksiler" class="btn btn-sm rounded-pill <?= $filter === 'auksiler' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Auksiler (Doa)
                </a>
                <a href="anggota.php?filter=perwira" class="btn btn-sm rounded-pill <?= $filter === 'perwira' ? 'btn-primary' : 'btn-outline-secondary' ?>">
                    Dewan Perwira
                </a>
            </div>

            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchAnggota" class="form-control border-0 bg-light rounded-end-3" placeholder="Ketik nama / no. anggota...">
                </div>
            </div>
        </div>
    </div>

    <!-- Tabel Data Anggota -->
    <div class="table-card-marian">
        <div class="table-responsive">
            <table class="table table-hover table-marian align-middle mb-0" id="tableAnggota">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nomor</th>
                        <th>Nama Lengkap</th>
                        <th>Tingkat / Jabatan</th>
                        <th>Jenis Kelamin</th>
                        <th>No. Telepon / WA</th>
                        <th>Tanggal Gabung</th>
                        <th>Status</th>
                        <th class="text-end" style="width: 120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($anggota_list)): ?>
                        <?php 
                        $no = 1;
                        foreach ($anggota_list as $ang): 
                        ?>
                            <tr class="row-anggota" data-nama="<?= strtolower(htmlspecialchars($ang['nama_lengkap'])) ?>" data-no="<?= strtolower(htmlspecialchars($ang['nomor_anggota'])) ?>">
                                <td class="text-muted fw-bold"><?= $no++ ?></td>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-dark fw-bold border px-2 py-1">
                                        <?= htmlspecialchars($ang['nomor_anggota']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($ang['nama_lengkap']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ang['alamat'] ?: 'Tidak ada alamat') ?></small>
                                </td>
                                <td>
                                    <?php if ($ang['jabatan'] !== 'Anggota'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill fw-bold px-2 py-1 mb-1 d-inline-block">
                                            <i class="fas fa-crown text-danger me-1"></i> <?= $ang['jabatan'] ?>
                                        </span>
                                    <?php endif; ?>
                                    <div>
                                        <span class="badge <?= $ang['jenis_anggota'] === 'Aktif' ? 'bg-primary' : 'bg-success' ?> rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                            <?= $ang['jenis_anggota'] ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?= $ang['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                <td>
                                    <?php if ($ang['no_telepon']): ?>
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ang['no_telepon']) ?>" target="_blank" class="text-decoration-none text-success small fw-semibold">
                                            <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($ang['no_telepon']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?= $ang['tanggal_bergabung'] ? date('d M Y', strtotime($ang['tanggal_bergabung'])) : '-' ?></span>
                                </td>
                                <td>
                                    <span class="badge-status <?= $ang['status'] === 'Aktif' ? 'badge-hadir' : 'badge-sakit' ?>">
                                        <?= $ang['status'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary rounded-circle" title="Edit" 
                                            onclick='editAnggota(<?= json_encode($ang) ?>)'>
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a href="anggota.php?delete_id=<?= $ang['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle ms-1" title="Hapus" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus data anggota <?= htmlspecialchars(addslashes($ang['nama_lengkap'])) ?>?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Belum ada anggota yang terdaftar pada filter ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Anggota Baru -->
<div class="modal fade" id="modalTambahAnggota" tabindex="-1" aria-labelledby="modalTambahLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom bg-primary text-white rounded-top-4">
                <h5 class="modal-title font-cinzel fw-bold" id="modalTambahLabel">
                    <i class="fas fa-user-plus text-warning me-2"></i> Pendaftaran Anggota Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nama Lengkap Legioner <span class="text-danger">*</span></label>
                            <input type="text" name="nama_lengkap" class="form-control rounded-3" placeholder="Contoh: Maria Theresia Hutabarat" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Anggota (Opsional)</label>
                            <input type="text" name="nomor_anggota" class="form-control rounded-3" placeholder="Kosongkan = Otomatis">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jenis Keanggotaan</label>
                            <select name="jenis_anggota" class="form-select rounded-3">
                                <option value="Aktif">Anggota Aktif (Legioner)</option>
                                <option value="Auksiler">Anggota Auksiler (Doa)</option>
                                <option value="Praetorian">Praetorian</option>
                                <option value="Adjutorian">Adjutorian</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jabatan Perwira</label>
                            <select name="jabatan" class="form-select rounded-3">
                                <option value="Anggota">Anggota Biasa</option>
                                <option value="Pemimpin">Pemimpin (President)</option>
                                <option value="Wakil Pemimpin">Wakil Pemimpin (Vice President)</option>
                                <option value="Sekretaris">Sekretaris (Secretary)</option>
                                <option value="Bendahara">Bendahara (Treasurer)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" class="form-select rounded-3">
                                <option value="P">Perempuan</option>
                                <option value="L">Laki-laki</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">No. Telepon / WhatsApp</label>
                            <input type="text" name="no_telepon" class="form-control rounded-3" placeholder="08xxxxxxxxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" class="form-control rounded-3" value="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Alamat / Lingkungan Paroki</label>
                            <textarea name="alamat" class="form-control rounded-3" rows="2" placeholder="Nama jalan, RT/RW, atau nama lingkungan gereja..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-check me-1"></i> Daftarkan Anggota
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Anggota -->
<div class="modal fade" id="modalEditAnggota" tabindex="-1" aria-labelledby="modalEditLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom bg-warning text-dark rounded-top-4">
                <h5 class="modal-title font-cinzel fw-bold" id="modalEditLabel">
                    <i class="fas fa-user-pen me-2"></i> Edit Data Anggota
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" id="edit_nama" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Anggota</label>
                            <input type="text" name="nomor_anggota" id="edit_no" class="form-control rounded-3" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jenis Keanggotaan</label>
                            <select name="jenis_anggota" id="edit_jenis" class="form-select rounded-3">
                                <option value="Aktif">Anggota Aktif (Legioner)</option>
                                <option value="Auksiler">Anggota Auksiler (Doa)</option>
                                <option value="Praetorian">Praetorian</option>
                                <option value="Adjutorian">Adjutorian</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jabatan Perwira</label>
                            <select name="jabatan" id="edit_jabatan" class="form-select rounded-3">
                                <option value="Anggota">Anggota Biasa</option>
                                <option value="Pemimpin">Pemimpin (President)</option>
                                <option value="Wakil Pemimpin">Wakil Pemimpin (Vice President)</option>
                                <option value="Sekretaris">Sekretaris (Secretary)</option>
                                <option value="Bendahara">Bendahara (Treasurer)</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Status Keaktifan</label>
                            <select name="status" id="edit_status" class="form-select rounded-3">
                                <option value="Aktif">Aktif</option>
                                <option value="Cuti">Cuti</option>
                                <option value="Pindah">Pindah Presidium</option>
                                <option value="Berhenti">Berhenti</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Jenis Kelamin</label>
                            <select name="jenis_kelamin" id="edit_jk" class="form-select rounded-3">
                                <option value="P">Perempuan</option>
                                <option value="L">Laki-laki</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">No. Telepon</label>
                            <input type="text" name="no_telepon" id="edit_telp" class="form-control rounded-3">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Tanggal Bergabung</label>
                            <input type="date" name="tanggal_bergabung" id="edit_tgl" class="form-control rounded-3">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Alamat</label>
                            <textarea name="alamat" id="edit_alamat" class="form-control rounded-3" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-floppy-disk me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Live Search
    document.getElementById('searchAnggota')?.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.row-anggota').forEach(row => {
            const nama = row.getAttribute('data-nama');
            const no = row.getAttribute('data-no');
            if (nama.includes(query) || no.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Populate Modal Edit
    function editAnggota(data) {
        document.getElementById('edit_id').value = data.id;
        document.getElementById('edit_nama').value = data.nama_lengkap;
        document.getElementById('edit_no').value = data.nomor_anggota;
        document.getElementById('edit_jenis').value = data.jenis_anggota;
        document.getElementById('edit_jabatan').value = data.jabatan;
        document.getElementById('edit_status').value = data.status;
        document.getElementById('edit_jk').value = data.jenis_kelamin;
        document.getElementById('edit_telp').value = data.no_telepon || '';
        document.getElementById('edit_tgl').value = data.tanggal_bergabung || '';
        document.getElementById('edit_alamat').value = data.alamat || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditAnggota'));
        modal.show();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

