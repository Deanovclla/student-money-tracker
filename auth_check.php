<?php
// auth_check.php
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
