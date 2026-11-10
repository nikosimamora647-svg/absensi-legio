<?php
require_once __DIR__ . '/config/database.php';

$error = '';
$success = '';

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'need_login') {
        $error = 'Silakan login terlebih dahulu untuk mengakses sistem absensi.';
    } elseif ($_GET['msg'] === 'logout') {
        $success = 'Anda telah berhasil logout. Ave Maria!';
    }
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan Password wajib diisi.';
    } else {
        if ($db_connected && $pdo) {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
                $stmt->execute(['u' => $username]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nama_lengkap'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];

                    header("Location: dashboard.php");
                    exit;
                } else if ($user && $password === 'admin123') { // Fallback password default
                    $_SESSION['user_logged_in'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['nama_lengkap'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['username'] = $user['username'];

                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = 'Username atau Password salah. Periksa kembali.';
                }
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan sistem: ' . $e->getMessage();
            }
        } else {
            // Mode Demo / Fallback jika database belum dihidupkan di Laragon
            if ($username === 'admin' && ($password === 'admin123' || $password === 'admin')) {
                $_SESSION['user_logged_in'] = true;
                $_SESSION['user_id'] = 1;
                $_SESSION['user_name'] = 'Sekretaris Presidium (Demo)';
                $_SESSION['user_role'] = 'admin';
                $_SESSION['username'] = 'admin';
                header("Location: dashboard.php");
                exit;
            } else {
                $error = 'MySQL Laragon belum aktif. Gunakan username: <strong>admin</strong> dan password: <strong>admin123</strong> untuk masuk mode mandiri.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Petugas Absensi - Legio Mariae</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">

    <div class="login-halo-overlay"></div>

    <div class="login-wrapper">
        <div class="row g-0">
            <!-- Kolom Kiri: Foto Bunda Maria yang diunggah -->
            <div class="col-lg-6 login-media-side">
                <img src="assets/img/bunda-maria.jpg" alt="Bunda Maria Yang Tak Bernoda" class="login-maria-img">
                <div class="login-media-caption">
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2">
                        <i class="fas fa-cross me-1"></i> LEGIO MARIAE
                    </span>
                    <h2 class="font-cinzel h3 mb-2" style="color: #ffd166; text-shadow: 0 2px 8px rgba(0,0,0,0.8);">
                        Presidium Maria
                    </h2>
                    <p class="small mb-0 fst-italic" style="color: #e2e8f0; line-height: 1.6;">
                        "Siapakah dia yang muncul laksana fajar, indah bak bulan, gemilang laksana surya, dahsyat laksana bala tentara yang siap tempur?"
                    </p>
                    <span class="d-block mt-2 small text-warning fw-semibold">— Catena Legionis</span>
                </div>
            </div>

            <!-- Kolom Kanan: Form Login Admin / Petugas -->
            <div class="col-lg-6 login-form-side">
                <div class="text-center text-lg-start mb-4">
                    <div class="legio-badge-gold">
                        <i class="fas fa-shield-halved"></i> Portal Perwira Presidium
                    </div>
                    <h1 class="font-cinzel h2 fw-bold text-dark mb-1">Masuk Sistem</h1>
                    <p class="text-muted small mb-0">Sistem Pencatatan & Rekapitulasi Absensi Rapat Mingguan</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger d-flex align-items-center py-2 px-3 rounded-3 small mb-3 shadow-sm" role="alert">
                        <i class="fas fa-circle-exclamation me-2 fa-lg"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success d-flex align-items-center py-2 px-3 rounded-3 small mb-3 shadow-sm" role="alert">
                        <i class="fas fa-circle-check me-2 fa-lg"></i>
                        <div><?= $success ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">
                            <i class="fas fa-user text-primary me-1"></i> Username Admin / Sekretaris
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-id-badge"></i>
                            </span>
                            <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? 'admin') ?>">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">
                            <i class="fas fa-lock text-primary me-1"></i> Kata Sandi
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted">
                                <i class="fas fa-key"></i>
                            </span>
                            <input type="password" name="password" id="passwordInput" class="form-control border-start-0 border-end-0 ps-0" placeholder="Masukkan kata sandi" required value="admin123">
                            <button class="btn btn-outline-secondary border-start-0 bg-white text-muted" type="button" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-marian-primary w-100 mb-3 shadow">
                        <i class="fas fa-right-to-bracket me-2"></i> Buka Buku Absensi
                    </button>
                </form>

                <div class="card bg-light border-0 rounded-3 p-3 text-center small text-muted">
                    <div class="fw-bold text-dark mb-1">
                        <i class="fas fa-info-circle text-primary me-1"></i> Kredensial Login Default:
                    </div>
                    <div>Username: <code class="text-primary fw-bold">admin</code> | Sandi: <code class="text-primary fw-bold">admin123</code></div>
                    <div class="mt-1" style="font-size: 0.78rem;">Database: <strong>absensi_legio_maria</strong> (Laragon MySQL)</div>
                </div>

                <div class="text-center mt-4">
                    <span class="small text-muted">
                        <i class="fas fa-dove text-warning me-1"></i> Legio Mariae • Berdoa & Berkarya
                    </span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>

</html>

