<?php
$page_title = 'Jadwal Rapat Mingguan';
require_once __DIR__ . '/includes/header.php';

$message = '';
$message_type = 'success';

// Handle Tambah / Edit Pertemuan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nomor_pertemuan = (int)$_POST['nomor_pertemuan'];
    $tanggal = $_POST['tanggal'] ?: date('Y-m-d');
    $buku_pegangan_bab = trim($_POST['buku_pegangan_bab'] ?? '');
    $tema_alokusi = trim($_POST['tema_alokusi'] ?? '');
    $pembaca_alokusi = trim($_POST['pembaca_alokusi'] ?? '');
    $catatan_rapat = trim($_POST['catatan_rapat'] ?? '');

    if ($nomor_pertemuan <= 0) {
        $message = 'Nomor pertemuan harus lebih besar dari 0.';
        $message_type = 'danger';
    } else {
        if ($db_connected && $pdo) {
            try {
                if ($action === 'create') {
                    $stmt = $pdo->prepare("INSERT INTO pertemuan 
                        (nomor_pertemuan, tanggal, buku_pegangan_bab, tema_alokusi, pembaca_alokusi, catatan_rapat) 
                        VALUES (:no, :tgl, :bab, :tema, :pembaca, :catatan)");
                    $stmt->execute([
                        'no' => $nomor_pertemuan,
                        'tgl' => $tanggal,
                        'bab' => $buku_pegangan_bab,
                        'tema' => $tema_alokusi,
                        'pembaca' => $pembaca_alokusi,
                        'catatan' => $catatan_rapat
                    ]);
                    $new_id = $pdo->lastInsertId();
                    $message = "Pertemuan Ke-$nomor_pertemuan berhasil dibuat! Anda sekarang bisa mulai mengisi absensi.";
                    $message_type = 'success';
                } elseif ($action === 'update') {
                    $id = (int)$_POST['id'];
                    $stmt = $pdo->prepare("UPDATE pertemuan SET 
                        nomor_pertemuan = :no, 
                        tanggal = :tgl, 
                        buku_pegangan_bab = :bab, 
                        tema_alokusi = :tema, 
                        pembaca_alokusi = :pembaca, 
                        catatan_rapat = :catatan 
                        WHERE id = :id");
                    $stmt->execute([
                        'id' => $id,
                        'no' => $nomor_pertemuan,
                        'tgl' => $tanggal,
                        'bab' => $buku_pegangan_bab,
                        'tema' => $tema_alokusi,
                        'pembaca' => $pembaca_alokusi,
                        'catatan' => $catatan_rapat
                    ]);
                    $message = "Detail Pertemuan Ke-$nomor_pertemuan berhasil diperbarui!";
                    $message_type = 'success';
                }
            } catch (PDOException $e) {
                $message = 'Gagal menyimpan pertemuan: ' . $e->getMessage();
                $message_type = 'danger';
            }
        }
    }
}

// Handle Hapus Pertemuan
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($db_connected && $pdo && $delete_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM pertemuan WHERE id = :id");
            $stmt->execute(['id' => $delete_id]);
            $message = 'Pertemuan dan seluruh catatan absensinya berhasil dihapus.';
            $message_type = 'warning';
        } catch (PDOException $e) {
            $message = 'Gagal menghapus: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Hitung nomor pertemuan berikutnya otomatis
$next_nomor = 1;
$pertemuan_data = [];
if ($db_connected && $pdo) {
    try {
        $last_no = $pdo->query("SELECT MAX(nomor_pertemuan) FROM pertemuan")->fetchColumn();
        if ($last_no) {
            $next_nomor = (int)$last_no + 1;
        }

        $sql = "SELECT p.*, 
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Hadir') as hadir,
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Izin') as izin,
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Sakit') as sakit,
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Alpa') as alpa,
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Tugas Kerasulan') as tugas
            FROM pertemuan p 
            ORDER BY p.nomor_pertemuan DESC, p.tanggal DESC";
        $pertemuan_data = $pdo->query($sql)->fetchAll();
    } catch (PDOException $e) {}
}
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- Header & Aksi Buat Pertemuan -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-1">
                <i class="fas fa-calendar-week me-1"></i> Jadwal Rapat Presidium
            </span>
            <h1 class="font-cinzel h2 fw-bold text-dark mb-0">Rapat Mingguan Legio Maria</h1>
            <small class="text-muted">Kelola nomor pertemuan, tema alokusi rohani, dan arsip jalannya rapat.</small>
        </div>

        <div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalTambahPertemuan">
                <i class="fas fa-calendar-plus me-1"></i> Buat Pertemuan Baru
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

    <!-- Tabel Daftar Pertemuan -->
    <div class="table-card-marian">
        <div class="table-responsive">
            <table class="table table-hover table-marian align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width: 130px;">Rapat Ke-</th>
                        <th>Tanggal Pertemuan</th>
                        <th>Buku Pegangan / Alokusi</th>
                        <th>Rekap Kehadiran</th>
                        <th class="text-end" style="width: 180px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pertemuan_data)): ?>
                        <?php foreach ($pertemuan_data as $ptm): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-primary rounded-pill px-3 py-2 fw-bold" style="font-size: 0.85rem;">
                                        Ke - <?= $ptm['nomor_pertemuan'] ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= date('d F Y', strtotime($ptm['tanggal'])) ?></div>
                                    <small class="text-muted"><i class="fas fa-clock text-warning me-1"></i> <?= date('l', strtotime($ptm['tanggal'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary mb-1">
                                        <?= htmlspecialchars($ptm['tema_alokusi'] ?: 'Tidak ada tema alokusi khusus') ?>
                                    </div>
                                    <div class="small text-muted">
                                        <?php if ($ptm['buku_pegangan_bab']): ?>
                                            <span class="badge bg-light text-dark border me-1">
                                                <i class="fas fa-bookmark text-warning me-1"></i> <?= htmlspecialchars($ptm['buku_pegangan_bab']) ?>
                                            </span>
                                        <?php endif; ?>
                                        <i class="fas fa-user-pen me-1"></i> Pembaca: <?= htmlspecialchars($ptm['pembaca_alokusi'] ?: 'Pemimpin / Sp. Director') ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <span class="badge-status badge-hadir" title="Hadir"><?= $ptm['hadir'] ?> Hadir</span>
                                        <span class="badge-status badge-izin" title="Izin"><?= $ptm['izin'] ?> Izin</span>
                                        <span class="badge-status badge-sakit" title="Sakit"><?= $ptm['sakit'] ?> Sakit</span>
                                        <span class="badge-status badge-alpa" title="Alpa"><?= $ptm['alpa'] ?> Alpa</span>
                                        <span class="badge-status badge-tugas" title="Tugas Kerasulan"><?= $ptm['tugas'] ?> Tugas</span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="absensi.php?pertemuan_id=<?= $ptm['id'] ?>" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill px-3 shadow-sm" title="Catat Absensi">
                                        <i class="fas fa-clipboard-check me-1"></i> Absen
                                    </a>
                                    <button class="btn btn-sm btn-outline-primary rounded-circle ms-1" title="Edit Rapat" onclick='editPertemuan(<?= json_encode($ptm) ?>)'>
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a href="pertemuan.php?delete_id=<?= $ptm['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle ms-1" title="Hapus" 
                                       onclick="return confirm('Hapus Pertemuan Ke-<?= $ptm['nomor_pertemuan'] ?> beserta seluruh data absensinya?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="fas fa-calendar-xmark fa-3x mb-3 text-secondary d-block"></i>
                                <h5>Belum Ada Data Rapat Mingguan</h5>
                                <p class="small text-muted mb-3">Klik tombol di bawah untuk membuat jadwal rapat pertama!</p>
                                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#modalTambahPertemuan">
                                    <i class="fas fa-plus me-1"></i> Buat Pertemuan Pertama
                                </button>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Tambah Pertemuan Baru -->
<div class="modal fade" id="modalTambahPertemuan" tabindex="-1" aria-labelledby="modalTambahPtmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom bg-primary text-white rounded-top-4">
                <h5 class="modal-title font-cinzel fw-bold" id="modalTambahPtmLabel">
                    <i class="fas fa-calendar-plus text-warning me-2"></i> Jadwal Rapat Presidium Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Pertemuan Ke- <span class="text-danger">*</span></label>
                            <input type="number" name="nomor_pertemuan" class="form-control rounded-3 fw-bold" value="<?= $next_nomor ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Tanggal Rapat <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bab Buku Pegangan Legio Maria</label>
                            <input type="text" name="buku_pegangan_bab" class="form-control rounded-3" placeholder="Contoh: Bab 18 - Kerasulan Legio">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pembaca Alokusi</label>
                            <input type="text" name="pembaca_alokusi" class="form-control rounded-3" placeholder="Nama Romo Pembimbing / Perwira">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Tema Alokusi Rohani</label>
                            <input type="text" name="tema_alokusi" class="form-control rounded-3" placeholder="Contoh: Meneladani Kerendahan Hati dan Ketaatan Bunda Maria">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Catatan Jalannya Rapat (Opsional)</label>
                            <textarea name="catatan_rapat" class="form-control rounded-3" rows="3" placeholder="Catatan penting rapat, penugasan kerasulan, dll..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light rounded-bottom-4">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-check me-1"></i> Simpan Pertemuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Pertemuan -->
<div class="modal fade" id="modalEditPertemuan" tabindex="-1" aria-labelledby="modalEditPtmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom bg-warning text-dark rounded-top-4">
                <h5 class="modal-title font-cinzel fw-bold" id="modalEditPtmLabel">
                    <i class="fas fa-pen-to-square me-2"></i> Edit Pertemuan Rapat
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_ptm_id">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Nomor Pertemuan</label>
                            <input type="number" name="nomor_pertemuan" id="edit_ptm_no" class="form-control rounded-3 fw-bold" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Tanggal</label>
                            <input type="date" name="tanggal" id="edit_ptm_tgl" class="form-control rounded-3" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Bab Buku Pegangan</label>
                            <input type="text" name="buku_pegangan_bab" id="edit_ptm_bab" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Pembaca Alokusi</label>
                            <input type="text" name="pembaca_alokusi" id="edit_ptm_pembaca" class="form-control rounded-3">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Tema Alokusi</label>
                            <input type="text" name="tema_alokusi" id="edit_ptm_tema" class="form-control rounded-3">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-bold">Catatan Rapat</label>
                            <textarea name="catatan_rapat" id="edit_ptm_catatan" class="form-control rounded-3" rows="3"></textarea>
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
    function editPertemuan(data) {
        document.getElementById('edit_ptm_id').value = data.id;
        document.getElementById('edit_ptm_no').value = data.nomor_pertemuan;
        document.getElementById('edit_ptm_tgl').value = data.tanggal;
        document.getElementById('edit_ptm_bab').value = data.buku_pegangan_bab || '';
        document.getElementById('edit_ptm_pembaca').value = data.pembaca_alokusi || '';
        document.getElementById('edit_ptm_tema').value = data.tema_alokusi || '';
        document.getElementById('edit_ptm_catatan').value = data.catatan_rapat || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditPertemuan'));
        modal.show();
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

