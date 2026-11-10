<?php
$page_title = 'Pencatatan Absensi';
require_once __DIR__ . '/includes/header.php';

$message = '';
$message_type = 'success';

// Ambil list pertemuan
$pertemuan_list = [];
if ($db_connected && $pdo) {
    try {
        $pertemuan_list = $pdo->query("SELECT * FROM pertemuan ORDER BY nomor_pertemuan DESC, tanggal DESC")->fetchAll();
    } catch (PDOException $e) {}
}

// Tentukan pertemuan_id aktif
$selected_pertemuan_id = isset($_GET['pertemuan_id']) ? (int)$_GET['pertemuan_id'] : 0;
if ($selected_pertemuan_id === 0 && !empty($pertemuan_list)) {
    $selected_pertemuan_id = (int)$pertemuan_list[0]['id'];
}

// Handle Form Simpan Absensi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_absensi'])) {
    $pertemuan_id = (int)$_POST['pertemuan_id'];
    $kehadiran_data = $_POST['kehadiran'] ?? []; // [anggota_id => status]
    $tugas_data = $_POST['tugas'] ?? []; // [anggota_id => laporan_tugas]
    $keterangan_data = $_POST['keterangan'] ?? [];

    if ($db_connected && $pdo && $pertemuan_id > 0) {
        try {
            $pdo->beginTransaction();

            $stmt_upsert = $pdo->prepare("INSERT INTO absensi 
                (pertemuan_id, anggota_id, status_kehadiran, laporan_tugas, keterangan) 
                VALUES (:p_id, :a_id, :status, :tugas, :ket)
                ON DUPLICATE KEY UPDATE 
                status_kehadiran = VALUES(status_kehadiran),
                laporan_tugas = VALUES(laporan_tugas),
                keterangan = VALUES(keterangan)");

            foreach ($kehadiran_data as $anggota_id => $status) {
                $tugas = $tugas_data[$anggota_id] ?? '';
                $ket = $keterangan_data[$anggota_id] ?? '';

                $stmt_upsert->execute([
                    'p_id' => $pertemuan_id,
                    'a_id' => (int)$anggota_id,
                    'status' => $status,
                    'tugas' => $tugas,
                    'ket' => $ket
                ]);
            }

            $pdo->commit();
            $message = 'Data absensi presidium berhasil disimpan ke database!';
            $message_type = 'success';
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = 'Gagal menyimpan absensi: ' . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

// Ambil detail pertemuan aktif
$detail_pertemuan = null;
$anggota_absensi = [];
if ($db_connected && $pdo && $selected_pertemuan_id > 0) {
    try {
        $stmt_p = $pdo->prepare("SELECT * FROM pertemuan WHERE id = :id");
        $stmt_p->execute(['id' => $selected_pertemuan_id]);
        $detail_pertemuan = $stmt_p->fetch();

        // Ambil semua anggota dengan data absensi pada pertemuan ini (jika sudah ada)
        $sql_anggota = "SELECT a.*, ab.status_kehadiran, ab.laporan_tugas, ab.keterangan 
            FROM anggota a 
            LEFT JOIN absensi ab ON ab.anggota_id = a.id AND ab.pertemuan_id = :p_id 
            WHERE a.status = 'Aktif'
            ORDER BY 
                CASE a.jabatan 
                    WHEN 'Pemimpin' THEN 1 
                    WHEN 'Wakil Pemimpin' THEN 2 
                    WHEN 'Sekretaris' THEN 3 
                    WHEN 'Bendahara' THEN 4 
                    ELSE 5 
                END, a.nama_lengkap ASC";
        $stmt_a = $pdo->prepare($sql_anggota);
        $stmt_a->execute(['p_id' => $selected_pertemuan_id]);
        $anggota_absensi = $stmt_a->fetchAll();
    } catch (PDOException $e) {}
}
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- Header & Alert Notifikasi -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-1">
                <i class="fas fa-clipboard-check me-1"></i> Absensi Rapat Mingguan
            </span>
            <h1 class="font-cinzel h2 fw-bold text-dark mb-0">Presensi Presidium Legio Maria</h1>
        </div>

        <div class="d-flex gap-2">
            <a href="pertemuan.php?action=create" class="btn btn-outline-primary rounded-pill px-3 shadow-sm">
                <i class="fas fa-plus me-1"></i> Rapat Baru
            </a>
            <a href="rekap.php" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm">
                <i class="fas fa-file-excel me-1"></i> Rekap Nilai
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show rounded-4 shadow-sm" role="alert">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-triangle-exclamation' ?> me-2"></i>
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Card Pilihan Pertemuan Rapat -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 bg-white p-3 p-md-4">
        <div class="row g-3 align-items-center">
            <div class="col-md-5">
                <label class="form-label small fw-bold text-secondary">
                    <i class="fas fa-calendar-day text-primary me-1"></i> Pilih Pertemuan / Rapat:
                </label>
                <form method="GET" action="" id="formPilihPertemuan">
                    <select name="pertemuan_id" class="form-select border-2 border-primary rounded-3 fw-bold" onchange="document.getElementById('formPilihPertemuan').submit()">
                        <?php if (empty($pertemuan_list)): ?>
                            <option value="">-- Belum ada pertemuan tercatat --</option>
                        <?php else: ?>
                            <?php foreach ($pertemuan_list as $ptm): ?>
                                <option value="<?= $ptm['id'] ?>" <?= $ptm['id'] == $selected_pertemuan_id ? 'selected' : '' ?>>
                                    Pertemuan Ke-<?= $ptm['nomor_pertemuan'] ?> (<?= date('d M Y', strtotime($ptm['tanggal'])) ?>) - <?= htmlspecialchars($ptm['tema_alokusi'] ?: 'Rapat Rutin') ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </form>
            </div>

            <?php if ($detail_pertemuan): ?>
                <div class="col-md-7 border-start-md ps-md-4">
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <span class="small text-muted d-block">Tanggal Rapat:</span>
                            <span class="fw-bold text-dark"><i class="fas fa-calendar text-warning me-1"></i> <?= date('d F Y', strtotime($detail_pertemuan['tanggal'])) ?></span>
                        </div>
                        <div class="col-sm-6">
                            <span class="small text-muted d-block">Alokusi Rohani:</span>
                            <span class="fw-bold text-dark text-truncate d-block" title="<?= htmlspecialchars($detail_pertemuan['tema_alokusi']) ?>">
                                <i class="fas fa-book-bible text-primary me-1"></i> <?= htmlspecialchars($detail_pertemuan['tema_alokusi'] ?: 'Tidak ada tema') ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($detail_pertemuan && !empty($anggota_absensi)): ?>
        <!-- Form Utama Absensi Anggota -->
        <form method="POST" action="">
            <input type="hidden" name="simpan_absensi" value="1">
            <input type="hidden" name="pertemuan_id" value="<?= $selected_pertemuan_id ?>">

            <!-- Filter & Bar Aksi Cepat -->
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 bg-white p-3 rounded-4 shadow-sm">
                <div class="d-flex align-items-center gap-2 flex-grow-1" style="max-width: 400px;">
                    <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" id="searchInput" class="form-control form-control-sm border-0 bg-light rounded-3" placeholder="Cari nama anggota atau nomor...">
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="setAllAttendance('Hadir')">
                        <i class="fas fa-check-double me-1"></i> Set Semua Hadir
                    </button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-floppy-disk me-1"></i> Simpan Absensi
                    </button>
                </div>
            </div>

            <!-- Tabel Daftar Anggota yang Diabsen -->
            <div class="table-card-marian mb-4">
                <div class="table-responsive">
                    <table class="table table-hover table-marian align-middle mb-0" id="absensiTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;">No</th>
                                <th>Anggota Presidium</th>
                                <th>Jenis / Jabatan</th>
                                <th style="min-width: 330px;">Status Kehadiran</th>
                                <th style="min-width: 250px;">Laporan Tugas Kerasulan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($anggota_absensi as $ang): 
                                $status_saat_ini = $ang['status_kehadiran'] ?? 'Hadir';
                            ?>
                                <tr class="member-row" data-name="<?= strtolower(htmlspecialchars($ang['nama_lengkap'])) ?>" data-no="<?= strtolower(htmlspecialchars($ang['nomor_anggota'])) ?>">
                                    <td class="text-muted fw-bold"><?= $no++ ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar-circle-sm bg-primary bg-opacity-10 text-primary fw-bold rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                                                <?= strtoupper(substr($ang['nama_lengkap'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($ang['nama_lengkap']) ?></div>
                                                <small class="text-muted"><i class="fas fa-id-card-clip me-1"></i> <?= htmlspecialchars($ang['nomor_anggota']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($ang['jabatan'] !== 'Anggota'): ?>
                                            <span class="badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold d-block mb-1" style="font-size: 0.72rem;">
                                                <i class="fas fa-star text-danger me-1"></i> <?= $ang['jabatan'] ?>
                                            </span>
                                        <?php endif; ?>
                                        <span class="badge <?= $ang['jenis_anggota'] === 'Aktif' ? 'bg-primary' : 'bg-success' ?> rounded-pill px-2 py-1" style="font-size: 0.72rem;">
                                            <?= $ang['jenis_anggota'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <!-- Radio Buttons Kehadiran (Mobile Friendly Touch Buttons) -->
                                        <div class="attendance-picker">
                                            <!-- Hadir -->
                                            <input type="radio" class="attendance-opt opt-radio-hadir" 
                                                   id="h_<?= $ang['id'] ?>" 
                                                   name="kehadiran[<?= $ang['id'] ?>]" 
                                                   value="Hadir" 
                                                   <?= $status_saat_ini === 'Hadir' ? 'checked' : '' ?>>
                                            <label class="attendance-label opt-hadir" for="h_<?= $ang['id'] ?>">Hadir</label>

                                            <!-- Izin -->
                                            <input type="radio" class="attendance-opt opt-radio-izin" 
                                                   id="i_<?= $ang['id'] ?>" 
                                                   name="kehadiran[<?= $ang['id'] ?>]" 
                                                   value="Izin" 
                                                   <?= $status_saat_ini === 'Izin' ? 'checked' : '' ?>>
                                            <label class="attendance-label opt-izin" for="i_<?= $ang['id'] ?>">Izin</label>

                                            <!-- Sakit -->
                                            <input type="radio" class="attendance-opt opt-radio-sakit" 
                                                   id="s_<?= $ang['id'] ?>" 
                                                   name="kehadiran[<?= $ang['id'] ?>]" 
                                                   value="Sakit" 
                                                   <?= $status_saat_ini === 'Sakit' ? 'checked' : '' ?>>
                                            <label class="attendance-label opt-sakit" for="s_<?= $ang['id'] ?>">Sakit</label>

                                            <!-- Alpa -->
                                            <input type="radio" class="attendance-opt opt-radio-alpa" 
                                                   id="a_<?= $ang['id'] ?>" 
                                                   name="kehadiran[<?= $ang['id'] ?>]" 
                                                   value="Alpa" 
                                                   <?= $status_saat_ini === 'Alpa' ? 'checked' : '' ?>>
                                            <label class="attendance-label opt-alpa" for="a_<?= $ang['id'] ?>">Alpa</label>

                                            <!-- Tugas Kerasulan -->
                                            <input type="radio" class="attendance-opt opt-radio-tugas" 
                                                   id="t_<?= $ang['id'] ?>" 
                                                   name="kehadiran[<?= $ang['id'] ?>]" 
                                                   value="Tugas Kerasulan" 
                                                   <?= $status_saat_ini === 'Tugas Kerasulan' ? 'checked' : '' ?>>
                                            <label class="attendance-label opt-tugas" for="t_<?= $ang['id'] ?>">Tugas</label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="tugas[<?= $ang['id'] ?>]" class="form-control form-control-sm rounded-3" 
                                               placeholder="Catatan tugas kerasulan / kunjungan..." 
                                               value="<?= htmlspecialchars($ang['laporan_tugas'] ?? '') ?>">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center">
                    <span class="small text-muted">
                        Total Anggota Terdaftar: <strong><?= count($anggota_absensi) ?></strong>
                    </span>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-floppy-disk me-1"></i> Simpan Semua Absensi
                    </button>
                </div>
            </div>
        </form>
    <?php else: ?>
        <div class="card border-0 rounded-4 shadow-sm bg-white p-5 text-center">
            <i class="fas fa-calendar-plus text-warning fa-3x mb-3"></i>
            <h4 class="font-cinzel text-dark mb-2">Belum Ada Rapat Mingguan</h4>
            <p class="text-muted mb-3">Buat jadwal rapat mingguan presidium terlebih dahulu untuk mulai mencatat absensi.</p>
            <div>
                <a href="pertemuan.php?action=create" class="btn btn-primary rounded-pill px-4 fw-bold">
                    <i class="fas fa-plus me-1"></i> Buat Pertemuan Rapat Sekarang
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Live Search Anggota Instan
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        const rows = document.querySelectorAll('.member-row');
        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const no = row.getAttribute('data-no');
            if (name.includes(query) || no.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Set Semua Hadir Sekali Klik
    function setAllAttendance(status) {
        if (status === 'Hadir') {
            document.querySelectorAll('.opt-radio-hadir').forEach(radio => {
                radio.checked = true;
            });
        }
    }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

