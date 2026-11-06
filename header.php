<?php
// header.php - Template bagian atas dashboard
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Student Money Tracker</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="dashboard-wrapper">
    <div class="dashboard-card">
        <div class="top-header">
            <div>
                <div class="title">Student Money Tracker</div>
                <div class="nav-links" style="margin-top:6px; margin-bottom:0;">
                    <a href="index.php">📊 Dashboard</a>
                    <a href="logout.php" style="color:#ef4444;" onclick="return confirm('Apakah Anda yakin ingin keluar?')">🚪 Logout</a>
                </div>
            </div>
            <div class="hello-text">
                Hello, <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?>!</strong>
            </div>
        </div>
