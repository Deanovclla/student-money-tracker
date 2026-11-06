<?php
require 'config.php';

// Jika sudah login, langsung alihkan ke dashboard utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $err = "Username dan password wajib diisi.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Regenerasi ID sesi demi mencegah Session Fixation Attack
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: index.php");
                exit;
            } else {
                $err = "Password yang Anda masukkan salah.";
            }
        } else {
            $err = "Username tidak terdaftar.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Student Money Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper auth-wrapper">
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px; color:#0f172a;">Login Akun</h2>
        <p style="font-size:14px; color:var(--text-muted); margin-bottom:20px;">
            Masuk ke Student Money Tracker untuk mengelola keuangan harian Anda.
        </p>

        <?php if ($err): ?>
            <p style="color:#b91c1c; background:#fee2e2; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:14px;">
                <?php echo htmlspecialchars($err); ?>
            </p>
        <?php endif; ?>

        <form method="post" action="login.php">
            <div class="form-row">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus placeholder="Masukkan username">
            </div>
            <div class="form-row">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Masukkan password">
            </div>
            <button class="btn" type="submit" style="width:100%; margin-top:8px;">Masuk</button>
        </form>

        <p style="margin-top:18px; font-size:14px; text-align:center;">
            Belum punya akun? <a href="register.php" style="color:#2563eb; font-weight:600; text-decoration:none;">Daftar Sekarang</a>
        </p>
    </div>
</div>
</body>
</html>
