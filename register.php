<?php
require 'config.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    if ($username === '' || $password === '') {
        $err = "Username dan password wajib diisi.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hash);
        if ($stmt->execute()) {
            header("Location: login.php");
            exit;
        } else {
            $err = "Gagal register (mungkin username sudah dipakai).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - Student Money Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper auth-wrapper">
    <div class="dashboard-card">
        <h2 style="margin-bottom:16px;">Register</h2>
        <?php if ($err): ?>
            <p style="color:#b91c1c; font-size:13px; margin-bottom:10px;"><?php echo htmlspecialchars($err); ?></p>
        <?php endif; ?>

        <form method="post">
            <div class="form-row">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-row">
                <label>Email (opsional)</label>
                <input type="email" name="email">
            </div>
            <div class="form-row">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button class="btn" type="submit">Daftar</button>
        </form>

        <p style="margin-top:14px; font-size:14px;">
            Sudah punya akun? <a href="login.php" style="color:#2563eb;">Login</a>
        </p>
    </div>
</div>
</body>
</html>
