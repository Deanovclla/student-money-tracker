<?php
require 'config.php';
require 'auth_check.php';

$userId = (int)$_SESSION['user_id'];
$feedbackMessage = '';
$feedbackType = '';

// Helper format mata uang Rupiah
function formatRupiah($nominal) {
    return 'Rp ' . number_format((float)$nominal, 0, ',', '.');
}

// ----------------------------------------------------
// 1. Handler Form Aksi (Tambah Transaksi & Update Budget)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah Transaksi
    if ($action === 'add_transaction') {
        $transDate   = trim($_POST['transaction_date'] ?? date('Y-m-d'));
        $description = trim($_POST['description'] ?? '');
        $type        = trim($_POST['type'] ?? 'Pengeluaran');
        $category    = trim($_POST['category'] ?? '-');
        $amount      = (float)str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $_POST['amount'] ?? '0');

        if ($description === '' || $amount <= 0) {
            $feedbackMessage = 'Deskripsi transaksi dan nominal harus valid (lebih dari 0).';
            $feedbackType = 'error';
        } else {
            $validTypes = ['Income', 'Pengeluaran', 'Tabungan'];
            if (!in_array($type, $validTypes, true)) {
                $type = 'Pengeluaran';
            }
            if ($type !== 'Pengeluaran') {
                $category = '-';
            }

            $stmt = $conn->prepare("INSERT INTO transactions (user_id, transaction_date, description, category, type, amount) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssd", $userId, $transDate, $description, $category, $type, $amount);

            if ($stmt->execute()) {
                $feedbackMessage = 'Transaksi baru berhasil dicatat!';
                $feedbackType = 'success';
            } else {
                $feedbackMessage = 'Terjadi kesalahan sistem saat menyimpan data.';
                $feedbackType = 'error';
            }
            $stmt->close();
        }
    }

    // Update Budget Bulanan
    if ($action === 'update_budget') {
        $budget = (float)str_replace(['Rp', '.', ' ', ','], ['', '', '', '.'], $_POST['monthly_budget'] ?? '0');
        if ($budget >= 0) {
            $stmt = $conn->prepare("UPDATE users SET monthly_budget = ? WHERE id = ?");
            $stmt->bind_param("di", $budget, $userId);
            $stmt->execute();
            $stmt->close();
            $feedbackMessage = 'Nominal bulanan berhasil diperbarui!';
            $feedbackType = 'success';
        }
    }

    // Hapus Transaksi
    if ($action === 'delete_transaction') {
        $transId = (int)($_POST['transaction_id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $transId, $userId);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $feedbackMessage = 'Transaksi berhasil dihapus.';
            $feedbackType = 'success';
        }
        $stmt->close();
    }
}

// ----------------------------------------------------
// 2. Query Data Pengguna & Ringkasan Keuangan
// ----------------------------------------------------
$userStmt = $conn->prepare("SELECT monthly_budget FROM users WHERE id = ?");
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userRes = $userStmt->get_result()->fetch_assoc();
$monthlyBudget = (float)($userRes['monthly_budget'] ?? 1000000.00);
$userStmt->close();

// Ringkasan Agregasi per Tipe
$totals = ['Income' => 0.0, 'Pengeluaran' => 0.0, 'Tabungan' => 0.0];
$sumStmt = $conn->prepare("SELECT type, SUM(amount) AS total FROM transactions WHERE user_id = ? GROUP BY type");
$sumStmt->bind_param("i", $userId);
$sumStmt->execute();
$sumRes = $sumStmt->get_result();
while ($row = $sumRes->fetch_assoc()) {
    $totals[$row['type']] = (float)$row['total'];
}
$sumStmt->close();

// Query Rincian Kategori Pengeluaran
$categoryExpenses = [
    'Kebutuhan Pokok' => 0.0,
    'Kesehatan'       => 0.0,
    'Pendidikan'      => 0.0,
    'Hiburan'         => 0.0,
    'Lainnya'         => 0.0
];
$catStmt = $conn->prepare("SELECT category, SUM(amount) AS total FROM transactions WHERE user_id = ? AND type = 'Pengeluaran' GROUP BY category");
$catStmt->bind_param("i", $userId);
$catStmt->execute();
$catRes = $catStmt->get_result();
while ($row = $catRes->fetch_assoc()) {
    $cName = $row['category'];
    if (array_key_exists($cName, $categoryExpenses)) {
        $categoryExpenses[$cName] = (float)$row['total'];
    } else {
        $categoryExpenses['Lainnya'] += (float)$row['total'];
    }
}
$catStmt->close();

// Kalkulasi Persentase & Sudut Gradient Donut Chart
$totalExp = $totals['Pengeluaran'];
$colorMap = [
    'Kebutuhan Pokok' => '#42a5f5', // Biru
    'Kesehatan'       => '#66bb6a', // Hijau
    'Pendidikan'      => '#facc15', // Kuning
    'Hiburan'         => '#fb7185', // Merah muda
    'Lainnya'         => '#c4b5fd'  // Ungu
];

$gradientSegments = [];
$currentDeg = 0;
if ($totalExp > 0) {
    foreach ($categoryExpenses as $catName => $amount) {
        if ($amount > 0) {
            $slice = ($amount / $totalExp) * 360;
            $nextDeg = $currentDeg + $slice;
            $col = $colorMap[$catName];
            $gradientSegments[] = "{$col} " . round($currentDeg, 1) . "deg " . round($nextDeg, 1) . "deg";
            $currentDeg = $nextDeg;
        }
    }
}
$donutGradient = !empty($gradientSegments) ? implode(', ', $gradientSegments) : '#cbd5e1 0deg 360deg';

// ----------------------------------------------------
// 3. Query Daftar Riwayat Transaksi
// ----------------------------------------------------
$transList = [];
$listStmt = $conn->prepare("SELECT id, transaction_date, description, category, type, amount FROM transactions WHERE user_id = ? ORDER BY transaction_date DESC, id DESC LIMIT 100");
$listStmt->bind_param("i", $userId);
$listStmt->execute();
$transRes = $listStmt->get_result();
while ($r = $transRes->fetch_assoc()) {
    $transList[] = $r;
}
$listStmt->close();

require 'header.php';
?>

<!-- Feedback Notifikasi -->
<?php if ($feedbackMessage): ?>
    <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 12px; font-size: 14px; <?php echo $feedbackType === 'success' ? 'background:#dcfce7; color:#15803d; border:1px solid #bbf7d0;' : 'background:#fee2e2; color:#b91c1c; border:1px solid #fecaca;'; ?>">
        <?php echo htmlspecialchars($feedbackMessage); ?>
    </div>
<?php endif; ?>

<!-- Nominal Utama / Monthly Budget Card -->
<div class="nominal-card">
    <div>
        <div class="nominal-label">Nominal Alokasi Bulanan</div>
        <div style="font-size: 12px; opacity: 0.85; margin-top: 2px;">
            Sisa Budget: <strong><?php echo formatRupiah(max(0, $monthlyBudget - $totals['Pengeluaran'])); ?></strong>
        </div>
    </div>
    <div style="display:flex; align-items:center; gap:14px;">
        <div class="nominal-value"><?php echo formatRupiah($monthlyBudget); ?></div>
        <button type="button" class="btn btn-secondary" style="padding:6px 12px; font-size:12px; border-radius:8px;" onclick="toggleBudgetModal()">Ubah</button>
    </div>
</div>

<!-- Modal Ubah Budget -->
<div id="budgetModal" style="display:none; background: #fff; padding: 18px 20px; border-radius: 14px; margin-bottom: 20px; box-shadow: var(--shadow-soft); border: 1px solid #e2e8f0;">
    <form method="post" style="display:flex; gap:12px; align-items:flex-end;">
        <input type="hidden" name="action" value="update_budget">
        <div class="form-row" style="flex:1; margin-bottom:0;">
            <label style="font-size:13px; font-weight:600;">Nominal Budget Baru (Rp):</label>
            <input type="number" name="monthly_budget" value="<?php echo (int)$monthlyBudget; ?>" required min="0" step="10000" style="padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px;">
        </div>
        <button type="submit" class="btn" style="padding:8px 16px;">Simpan</button>
        <button type="button" class="btn btn-secondary" style="padding:8px 14px;" onclick="toggleBudgetModal()">Batal</button>
    </form>
</div>

<!-- Ringkasan 3 Kartu (Income, Pengeluaran, Tabungan) -->
<div class="summary-row">
    <div class="summary-card income">
        <div class="summary-title">Income (Pemasukan)</div>
        <div class="summary-value" style="color:#16a34a;"><?php echo formatRupiah($totals['Income']); ?></div>
    </div>
    <div class="summary-card expense">
        <div class="summary-title">Pengeluaran</div>
        <div class="summary-value" style="color:#dc2626;"><?php echo formatRupiah($totals['Pengeluaran']); ?></div>
    </div>
    <div class="summary-card saving">
        <div class="summary-title">Tabungan</div>
        <div class="summary-value" style="color:#2563eb;"><?php echo formatRupiah($totals['Tabungan']); ?></div>
    </div>
</div>

<!-- Middle Section: Donut Chart & Kategori -->
<div class="middle-section">
    <!-- Panel Visualisasi Donut Chart -->
    <div class="panel">
        <div class="panel-title">Distribusi Pengeluaran</div>
        <div class="donut-wrapper">
            <div class="donut" style="background: conic-gradient(<?php echo $donutGradient; ?>);"></div>
        </div>
        <div style="text-align:center; margin-top:10px; font-size:13px; color:var(--text-muted);">
            <?php echo $totalExp > 0 ? 'Total Terpakai: ' . formatRupiah($totalExp) : 'Belum ada pengeluaran tercatat'; ?>
        </div>
    </div>

    <!-- Panel Rincian Kategori Pengeluaran -->
    <div class="panel">
        <div class="panel-title">Kategori Pengeluaran</div>
        <ul class="category-list">
            <?php foreach ($categoryExpenses as $cName => $amt): ?>
                <?php $pct = $totalExp > 0 ? round(($amt / $totalExp) * 100, 1) : 0; ?>
                <li class="category-item" style="justify-content:space-between; padding: 6px 0; border-bottom:1px dashed #f1f5f9;">
                    <div style="display:flex; align-items:center; gap:8px;">
                        <span style="display:inline-block; width:12px; height:12px; border-radius:3px; background-color:<?php echo $colorMap[$cName]; ?>;"></span>
                        <span style="font-weight:500;"><?php echo htmlspecialchars($cName); ?></span>
                    </div>
                    <div style="font-size:13px;">
                        <strong><?php echo formatRupiah($amt); ?></strong>
                        <span style="color:var(--text-muted); font-size:12px;">(<?php echo $pct; ?>%)</span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Panel Form Catat Transaksi Baru -->
<div class="panel" style="margin-bottom: 24px;">
    <div class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>➕ Catat Transaksi Baru</span>
    </div>
    <form method="post" action="index.php">
        <input type="hidden" name="action" value="add_transaction">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-bottom:12px;">
            <div class="form-row" style="margin-bottom:0;">
                <label>Tanggal</label>
                <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div class="form-row" style="margin-bottom:0;">
                <label>Deskripsi</label>
                <input type="text" name="description" placeholder="Contoh: Makan Siang, Kiriman" required>
            </div>
            <div class="form-row" style="margin-bottom:0;">
                <label>Tipe Transaksi</label>
                <select name="type" id="transTypeSelect" onchange="toggleCategorySelect()">
                    <option value="Pengeluaran">Pengeluaran</option>
                    <option value="Income">Income (Pemasukan)</option>
                    <option value="Tabungan">Tabungan</option>
                </select>
            </div>
            <div class="form-row" id="categoryGroup" style="margin-bottom:0;">
                <label>Kategori</label>
                <select name="category">
                    <option value="Kebutuhan Pokok">Kebutuhan Pokok</option>
                    <option value="Kesehatan">Kesehatan</option>
                    <option value="Pendidikan">Pendidikan</option>
                    <option value="Hiburan">Hiburan</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div class="form-row" style="margin-bottom:0;">
                <label>Nominal (Rp)</label>
                <input type="number" name="amount" min="100" step="500" placeholder="Contoh: 25000" required>
            </div>
        </div>
        <button type="submit" class="btn" style="padding:9px 20px; font-weight:600;">Simpan Transaksi</button>
    </form>
</div>

<!-- Panel Tabel Riwayat Transaksi -->
<div class="panel transactions-panel">
    <div class="panel-title" style="display:flex; justify-content:space-between; align-items:center;">
        <span>📑 Catatan Transaksi Terkini</span>
        <span style="font-size:12px; color:var(--text-muted); font-weight:normal;">Menampilkan <?php echo count($transList); ?> transaksi</span>
    </div>
    
    <div style="overflow-x:auto;">
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Deskripsi</th>
                    <th>Kategori</th>
                    <th>Tipe</th>
                    <th>Nominal</th>
                    <th style="text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transList)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center; padding:24px; color:var(--text-muted);">
                            Belum ada catatan transaksi. Mulai tambahkan transaksi pertama Anda di atas!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transList as $t): ?>
                        <tr>
                            <td><?php echo date('d-m-Y', strtotime($t['transaction_date'])); ?></td>
                            <td style="font-weight:500;"><?php echo htmlspecialchars($t['description']); ?></td>
                            <td>
                                <?php if ($t['category'] !== '-'): ?>
                                    <span style="background:#f1f5f9; padding:3px 8px; border-radius:6px; font-size:12px;">
                                        <?php echo htmlspecialchars($t['category']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:#94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($t['type'] === 'Income'): ?>
                                    <span style="color:#16a34a; font-weight:600;">Income</span>
                                <?php elseif ($t['type'] === 'Pengeluaran'): ?>
                                    <span style="color:#dc2626; font-weight:600;">Pengeluaran</span>
                                <?php else: ?>
                                    <span style="color:#2563eb; font-weight:600;">Tabungan</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;">
                                <?php echo formatRupiah($t['amount']); ?>
                            </td>
                            <td style="text-align:center;">
                                <form method="post" action="index.php" style="display:inline;" onsubmit="return confirm('Hapus transaksi ini?')">
                                    <input type="hidden" name="action" value="delete_transaction">
                                    <input type="hidden" name="transaction_id" value="<?php echo $t['id']; ?>">
                                    <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:13px; text-decoration:underline;">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleBudgetModal() {
    const modal = document.getElementById('budgetModal');
    modal.style.display = modal.style.display === 'none' ? 'block' : 'none';
}

function toggleCategorySelect() {
    const type = document.getElementById('transTypeSelect').value;
    const catGroup = document.getElementById('categoryGroup');
    if (type === 'Pengeluaran') {
        catGroup.style.display = 'block';
    } else {
        catGroup.style.display = 'none';
    }
}
</script>

<?php require 'footer.php'; ?>
