<?php
$page_title = 'Rekapitulasi Absensi';
require_once __DIR__ . '/includes/header.php';

$bulan_filter = $_GET['bulan'] ?? date('m');
$tahun_filter = $_GET['tahun'] ?? date('Y');

// Ambil info presidium
$presidium_info = [
    'nama_presidium' => 'Presidium Maria Bintang Samudera',
    'paroki' => 'Paroki Hati Kudus Yesus',
    'nama_kuria' => 'Kuria Maria Ratu Rosari'
];

if ($db_connected && $pdo) {
    try {
        $p_info = $pdo->query("SELECT * FROM presidium LIMIT 1")->fetch();
        if ($p_info) {
            $presidium_info = $p_info;
        }
    } catch (PDOException $e) {}
}

// Rekapitulasi Data Kehadiran per Anggota
$rekap_data = [];
$total_pertemuan_periode = 0;

if ($db_connected && $pdo) {
    try {
        // Hitung berapa kali pertemuan di bulan & tahun ini
        $stmt_ptm_count = $pdo->prepare("SELECT COUNT(*) FROM pertemuan 
            WHERE MONTH(tanggal) = :bln AND YEAR(tanggal) = :thn");
        $stmt_ptm_count->execute(['bln' => $bulan_filter, 'thn' => $tahun_filter]);
        $total_pertemuan_periode = (int)$stmt_ptm_count->fetchColumn();

        // Query rekap absensi per anggota
        $sql = "SELECT a.id, a.nomor_anggota, a.nama_lengkap, a.jabatan, a.jenis_anggota,
            SUM(CASE WHEN ab.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
            SUM(CASE WHEN ab.status_kehadiran = 'Izin' THEN 1 ELSE 0 END) AS izin,
            SUM(CASE WHEN ab.status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
            SUM(CASE WHEN ab.status_kehadiran = 'Alpa' THEN 1 ELSE 0 END) AS alpa,
            SUM(CASE WHEN ab.status_kehadiran = 'Tugas Kerasulan' THEN 1 ELSE 0 END) AS tugas,
            COUNT(ab.id) as total_rekam
            FROM anggota a
            LEFT JOIN absensi ab ON ab.anggota_id = a.id
            LEFT JOIN pertemuan p ON ab.pertemuan_id = p.id AND MONTH(p.tanggal) = :bln AND YEAR(p.tanggal) = :thn
            WHERE a.status = 'Aktif'
            GROUP BY a.id
            ORDER BY 
                CASE a.jabatan 
                    WHEN 'Pemimpin' THEN 1 
                    WHEN 'Wakil Pemimpin' THEN 2 
                    WHEN 'Sekretaris' THEN 3 
                    WHEN 'Bendahara' THEN 4 
                    ELSE 5 
                END, a.nama_lengkap ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['bln' => $bulan_filter, 'thn' => $tahun_filter]);
        $rekap_data = $stmt->fetchAll();
    } catch (PDOException $e) {}
}

$nama_bulan = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
?>

<style>
    @media print {
        .navbar, .no-print, footer, .btn, .card-filter {
            display: none !important;
        }
        body {
            background-color: #fff !important;
            color: #000 !important;
        }
        .table-card-marian {
            box-shadow: none !important;
            border: none !important;
        }
        .table-marian th {
            background-color: #f1f5f9 !important;
            color: #000 !important;
        }
        .print-header {
            display: block !important;
        }
    }
    .print-header {
        display: none;
    }
</style>

<div class="container-fluid px-3 px-lg-4 py-4">

    <!-- Kop Surat Resmi (Tampil Saat Cetak / PDF) -->
    <div class="print-header text-center mb-4 pb-3 border-bottom border-2 border-dark">
        <h3 class="font-cinzel fw-bold mb-0 text-uppercase">LEGIO MARIAE INDONESIA</h3>
        <h5 class="fw-bold mb-1"><?= htmlspecialchars($presidium_info['nama_presidium']) ?> - <?= htmlspecialchars($presidium_info['nama_kuria']) ?></h5>
        <div class="small"><?= htmlspecialchars($presidium_info['paroki']) ?></div>
        <div class="fw-bold mt-2 text-decoration-underline text-uppercase">
            REKAPITULASI ABSENSI RAPAT MINGGUAN - PERIODE <?= strtoupper($nama_bulan[$bulan_filter]) ?> <?= $tahun_filter ?>
        </div>
    </div>

    <!-- Header & Aksi Cetak -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 no-print">
        <div>
            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-1">
                <i class="fas fa-file-invoice me-1"></i> Rekapitulasi Presensi
            </span>
            <h1 class="font-cinzel h2 fw-bold text-dark mb-0">Laporan Kehadiran Presidium</h1>
            <small class="text-muted">Pantau persentase kehadiran dan ketekunan anggota legio maria.</small>
        </div>

        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-print me-1"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- Filter Bulan & Tahun -->
    <div class="card border-0 rounded-4 shadow-sm bg-white p-3 mb-4 no-print card-filter">
        <form method="GET" action="" class="row g-2 align-items-center">
            <div class="col-sm-4 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Bulan:</label>
                <select name="bulan" class="form-select rounded-3">
                    <?php foreach ($nama_bulan as $code => $name): ?>
                        <option value="<?= $code ?>" <?= $code === $bulan_filter ? 'selected' : '' ?>>
                            <?= $name ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-sm-4 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Tahun:</label>
                <select name="tahun" class="form-select rounded-3">
                    <?php 
                    $current_year = (int)date('Y');
                    for ($y = $current_year; $y >= $current_year - 3; $y--): 
                    ?>
                        <option value="<?= $y ?>" <?= $y == $tahun_filter ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-sm-4 col-md-2 mt-auto">
                <button type="submit" class="btn btn-primary rounded-3 w-100 fw-bold">
                    <i class="fas fa-filter me-1"></i> Tampilkan
                </button>
            </div>
        </form>
    </div>

    <!-- Ringkasan Periode -->
    <div class="row g-3 mb-4 no-print">
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 border-start border-4 border-primary">
                <span class="text-muted small fw-bold text-uppercase">Periode Laporan</span>
                <h5 class="fw-bold mb-0 text-dark"><?= $nama_bulan[$bulan_filter] ?> <?= $tahun_filter ?></h5>
                <small class="text-primary fw-semibold"><i class="fas fa-calendar-check me-1"></i> Total <?= $total_pertemuan_periode ?> Rapat Mingguan</small>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card border-0 rounded-4 shadow-sm bg-white p-3 border-start border-4 border-warning">
                <span class="text-muted small fw-bold text-uppercase">Total Anggota Terdata</span>
                <h5 class="fw-bold mb-0 text-dark"><?= count($rekap_data) ?> Anggota</h5>
                <small class="text-muted">Legioner Aktif & Auksiler</small>
            </div>
        </div>
    </div>

    <!-- Tabel Rekapitulasi Presensi -->
    <div class="table-card-marian">
        <div class="table-responsive">
            <table class="table table-bordered table-marian align-middle mb-0">
                <thead>
                    <tr class="text-center">
                        <th style="width: 40px;">No</th>
                        <th>No. Anggota</th>
                        <th class="text-start">Nama Anggota</th>
                        <th>Jabatan</th>
                        <th>Jenis</th>
                        <th style="width: 65px;" class="text-success">Hadir</th>
                        <th style="width: 65px;" class="text-warning">Izin</th>
                        <th style="width: 65px;" class="text-danger">Sakit</th>
                        <th style="width: 65px;" class="text-secondary">Alpa</th>
                        <th style="width: 65px;" class="text-info">Tugas</th>
                        <th style="width: 95px;">% Hadir</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rekap_data)): ?>
                        <?php 
                        $no = 1;
                        foreach ($rekap_data as $row): 
                            $hadir_total = (int)$row['hadir'] + (int)$row['tugas'];
                            $persen = 0;
                            if ($total_pertemuan_periode > 0) {
                                $persen = round(($hadir_total / $total_pertemuan_periode) * 100);
                            }
                        ?>
                            <tr class="text-center">
                                <td class="fw-bold text-muted"><?= $no++ ?></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['nomor_anggota']) ?></td>
                                <td class="text-start fw-bold text-dark"><?= htmlspecialchars($row['nama_lengkap']) ?></td>
                                <td>
                                    <?php if ($row['jabatan'] !== 'Anggota'): ?>
                                        <span class="badge bg-warning text-dark rounded-pill fw-bold px-2 py-1">
                                            <?= $row['jabatan'] ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted small">Anggota</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small badge <?= $row['jenis_anggota'] === 'Aktif' ? 'bg-primary' : 'bg-success' ?>">
                                        <?= $row['jenis_anggota'] ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-success fs-6"><?= $row['hadir'] ?></td>
                                <td class="fw-bold text-warning fs-6"><?= $row['izin'] ?></td>
                                <td class="fw-bold text-danger fs-6"><?= $row['sakit'] ?></td>
                                <td class="fw-bold text-secondary fs-6"><?= $row['alpa'] ?></td>
                                <td class="fw-bold text-info fs-6"><?= $row['tugas'] ?></td>
                                <td>
                                    <div class="fw-bold <?= $persen >= 75 ? 'text-success' : ($persen >= 50 ? 'text-warning' : 'text-danger') ?>">
                                        <?= $persen ?>%
                                    </div>
                                    <div class="progress no-print" style="height: 4px;">
                                        <div class="progress-bar <?= $persen >= 75 ? 'bg-success' : ($persen >= 50 ? 'bg-warning' : 'bg-danger') ?>" style="width: <?= $persen ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                Tidak ada data rekapitulasi pada periode <?= $nama_bulan[$bulan_filter] ?> <?= $tahun_filter ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tanda Tangan Saat Dicetak -->
    <div class="print-header mt-5">
        <div class="row text-center">
            <div class="col-6">
                <div>Mengetahui,</div>
                <div class="fw-bold mt-1">Pemimpin Presidium</div>
                <div style="height: 70px;"></div>
                <div class="fw-bold text-decoration-underline">( ......................................... )</div>
            </div>
            <div class="col-6">
                <div>Dibuat oleh,</div>
                <div class="fw-bold mt-1">Sekretaris Presidium</div>
                <div style="height: 70px;"></div>
                <div class="fw-bold text-decoration-underline">( <?= htmlspecialchars($_SESSION['user_name'] ?? 'Sekretaris') ?> )</div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

