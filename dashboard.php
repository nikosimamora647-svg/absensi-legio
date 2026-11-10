<?php
$page_title = 'Dashboard Presidium';
require_once __DIR__ . '/includes/header.php';

// Inisialisasi statistik default
$total_anggota = 0;
$anggota_aktif = 0;
$anggota_auksiler = 0;
$total_pertemuan = 0;
$pertemuan_terbaru = null;
$riwayat_pertemuan = [];

if ($db_connected && $pdo) {
    try {
        $total_anggota = $pdo->query("SELECT COUNT(*) FROM anggota")->fetchColumn() ?: 0;
        $anggota_aktif = $pdo->query("SELECT COUNT(*) FROM anggota WHERE jenis_anggota = 'Aktif' AND status = 'Aktif'")->fetchColumn() ?: 0;
        $anggota_auksiler = $pdo->query("SELECT COUNT(*) FROM anggota WHERE jenis_anggota = 'Auksiler' AND status = 'Aktif'")->fetchColumn() ?: 0;
        $total_pertemuan = $pdo->query("SELECT COUNT(*) FROM pertemuan")->fetchColumn() ?: 0;

        // Ambil pertemuan terakhir
        $stmt = $pdo->query("SELECT * FROM pertemuan ORDER BY nomor_pertemuan DESC, tanggal DESC LIMIT 1");
        $pertemuan_terbaru = $stmt->fetch();

        // 5 Pertemuan Terakhir
        $stmt_list = $pdo->query("SELECT p.*, 
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id AND a.status_kehadiran = 'Hadir') AS jml_hadir,
            (SELECT COUNT(*) FROM absensi a WHERE a.pertemuan_id = p.id) AS total_absen
            FROM pertemuan p ORDER BY p.nomor_pertemuan DESC, p.tanggal DESC LIMIT 5");
        $riwayat_pertemuan = $stmt_list->fetchAll();
    } catch (PDOException $e) {
        // error handling
    }
}
?>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- Banner Sambutan -->
    <div class="card border-0 rounded-4 shadow-sm mb-4 text-white overflow-hidden" style="background: linear-gradient(135deg, #0f284e 0%, #1d4e89 100%); border-left: 6px solid #d4af37 !important;">
        <div class="card-body p-4 p-lg-5 position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                        <i class="fas fa-church me-1"></i> Presidium Maria Bintang Samudera
                    </span>
                    <h1 class="font-cinzel display-6 fw-bold mb-2">Ave Maria, Gratia Plena!</h1>
                    <p class="mb-3 lead" style="font-size: 1.05rem; opacity: 0.9;">
                        Selamat datang di Sistem Presensi & Rekapitulasi Rapat Mingguan Legio Mariae. Berdoa, bekerja, dan melayani di bawah panji Bunda Maria.
                    </p>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="absensi.php" class="btn btn-warning fw-bold px-4 py-2 rounded-pill shadow-sm">
                            <i class="fas fa-clipboard-user me-1"></i> Catat Absensi Rapat
                        </a>
                        <a href="anggota.php" class="btn btn-outline-light px-4 py-2 rounded-pill">
                            <i class="fas fa-user-plus me-1"></i> Tambah Anggota
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 text-center text-lg-end mt-4 mt-lg-0">
                    <img src="assets/img/bunda-maria.jpg" alt="Bunda Maria" class="rounded-4 shadow-lg border border-3 border-warning" style="width: 140px; height: 140px; object-fit: cover;">
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Kartu Statistik Cepat -->
    <div class="row g-3 mb-4">
        <!-- 1. Total Anggota -->
        <div class="col-6 col-lg-3">
            <div class="stat-card-marian">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Total Anggota</span>
                        <h2 class="h3 fw-bold mb-0 text-dark"><?= number_format($total_anggota) ?></h2>
                        <small class="text-muted">Kapasitas Tak Terbatas</small>
                    </div>
                    <div class="stat-icon-wrap bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Anggota Aktif -->
        <div class="col-6 col-lg-3">
            <div class="stat-card-marian stat-card-gold">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Legioner Aktif</span>
                        <h2 class="h3 fw-bold mb-0 text-warning"><?= number_format($anggota_aktif) ?></h2>
                        <small class="text-success fw-semibold"><i class="fas fa-circle-check"></i> Tugas Kerasulan</small>
                    </div>
                    <div class="stat-icon-wrap bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Anggota Auksiler -->
        <div class="col-6 col-lg-3">
            <div class="stat-card-marian stat-card-green">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Anggota Auksiler</span>
                        <h2 class="h3 fw-bold mb-0 text-success"><?= number_format($anggota_auksiler) ?></h2>
                        <small class="text-muted">Pendoa Rosario Harian</small>
                    </div>
                    <div class="stat-icon-wrap bg-success bg-opacity-10 text-success">
                        <i class="fas fa-heart"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Total Pertemuan -->
        <div class="col-6 col-lg-3">
            <div class="stat-card-marian stat-card-red">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Rapat Terlaksana</span>
                        <h2 class="h3 fw-bold mb-0 text-danger"><?= number_format($total_pertemuan) ?></h2>
                        <small class="text-muted">Pertemuan Mingguan</small>
                    </div>
                    <div class="stat-icon-wrap bg-danger bg-opacity-10 text-danger">
                        <i class="fas fa-book-bookmark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bagian Rapat Terkini & Kutipan Doa -->
    <div class="row g-4">
        <!-- Kolom Kiri: Riwayat Pertemuan Terkini -->
        <div class="col-lg-8">
            <div class="table-card-marian">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                    <h5 class="font-cinzel fw-bold mb-0 text-dark">
                        <i class="fas fa-clock-rotate-left text-warning me-2"></i> Rapat Mingguan Terkini
                    </h5>
                    <a href="pertemuan.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        Lihat Semua <i class="fas fa-chevron-right ms-1"></i>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-marian mb-0">
                        <thead>
                            <tr>
                                <th>Rapat Ke-</th>
                                <th>Tanggal</th>
                                <th>Tema Alokusi</th>
                                <th>Kehadiran</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($riwayat_pertemuan)): ?>
                                <?php foreach ($riwayat_pertemuan as $ptm): ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-primary rounded-pill px-3 py-1 fw-bold">
                                                Pertemuan <?= $ptm['nomor_pertemuan'] ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($ptm['tanggal'])) ?></div>
                                            <small class="text-muted"><?= date('l', strtotime($ptm['tanggal'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 250px;">
                                                <?= htmlspecialchars($ptm['tema_alokusi'] ?: 'Tidak ada tema alokusi tercatat') ?>
                                            </div>
                                            <small class="text-muted"><i class="fas fa-user-pen me-1"></i> <?= htmlspecialchars($ptm['pembaca_alokusi'] ?: 'Spiritual Director') ?></small>
                                        </td>
                                        <td>
                                            <span class="badge-status badge-hadir">
                                                <i class="fas fa-check-double me-1"></i> <?= $ptm['jml_hadir'] ?> Hadir
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="absensi.php?pertemuan_id=<?= $ptm['id'] ?>" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill shadow-sm" title="Edit / Buka Absensi">
                                                <i class="fas fa-pen-to-square me-1"></i> Absensi
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        <i class="fas fa-calendar-xmark fa-2x mb-2 text-secondary d-block"></i>
                                        Belum ada data rapat mingguan. Silakan buat rapat pertama di menu <strong>Rapat Mingguan</strong>!
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Kotak Doa & Info Presidium -->
        <div class="col-lg-4">
            <!-- Kartu Doa Catena Legionis -->
            <div class="card border-0 rounded-4 shadow-sm mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #fdfbf7 100%); border: 2px solid #ffd166 !important;">
                <div class="card-body p-4 text-center">
                    <div class="mb-3">
                        <i class="fas fa-dove text-warning fa-2x"></i>
                    </div>
                    <h5 class="font-cinzel fw-bold text-dark mb-2">Catena Legionis</h5>
                    <p class="fst-italic text-secondary small mb-3" style="line-height: 1.7;">
                        "Jiwaku memuliakan Tuhan, dan hatiku bergembira karena Allah, Juruselamatku. Sebab Ia telah memperhatikan kerendahan hamba-Nya..."
                    </p>
                    <div class="border-top pt-2 small text-muted">
                        <i class="fas fa-heart text-danger me-1"></i> Rantai Doa Pemersatu Seluruh Legioner Dunia
                    </div>
                </div>
            </div>

            <!-- Kartu Rapat Berikutnya -->
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3">
                <h6 class="font-cinzel fw-bold text-dark border-bottom pb-2 mb-3">
                    <i class="fas fa-calendar-check text-primary me-2"></i> Jadwal Rapat Presidium
                </h6>
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="p-2 bg-primary bg-opacity-10 text-primary rounded-3 text-center" style="min-width: 50px;">
                        <i class="fas fa-church fa-lg"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-dark">Lokasi Pertemuan</div>
                        <small class="text-muted">Ruang Rapat Paroki / Karya Pastoral</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="p-2 bg-warning bg-opacity-10 text-warning rounded-3 text-center" style="min-width: 50px;">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                    <div>
                        <div class="small fw-bold text-dark">Waktu Rutin</div>
                        <small class="text-muted">Setiap Hari Minggu, Pukul 10.00 WIB</small>
                    </div>
                </div>
                <a href="pertemuan.php?action=create" class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-bold">
                    <i class="fas fa-plus me-1"></i> Buat Pertemuan Baru
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

