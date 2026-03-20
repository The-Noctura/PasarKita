<?php

require_once '../../../app/helpers.php';

require_admin_auth();

// Filter inputs
$filters = [
    'date_range' => trim($_GET['date_range'] ?? ''),
    'account_id' => trim($_GET['account_id'] ?? ''),
    'search' => trim($_GET['search'] ?? ''), // name or phone
    'username' => trim($_GET['username'] ?? ''),
    'email' => trim($_GET['email'] ?? ''),
    'status' => $_GET['status'] ?? '',
];

$allowedPerPage = [25, 50, 100, 250];
$perPage = (int) ($_GET['per_page'] ?? 25);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}

$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

try {
    $pdo = db();

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];

    $where = [];
    $params = [];

    if ($filters['account_id']) {
        $where[] = 'id = :account_id';
        $params['account_id'] = $filters['account_id'];
    }

    if ($filters['search']) {
        $where[] = '(full_name LIKE :search OR phone LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }
    if ($filters['username']) {
        $where[] = 'username LIKE :username';
        $params['username'] = '%' . $filters['username'] . '%';
    }
    if ($filters['email']) {
        $where[] = 'email LIKE :email';
        $params['email'] = '%' . $filters['email'] . '%';
    }

    if ($filters['status'] === 'active') {
        $where[] = 'last_login_at IS NOT NULL';
    } elseif ($filters['status'] === 'inactive') {
        $where[] = 'last_login_at IS NULL';
    }

    if ($filters['date_range']) {
        preg_match_all('/\d{2}\/\d{2}\/\d{4}/', $filters['date_range'], $matches);
        $dates = $matches[0] ?? [];

        if (count($dates) >= 1) {
            $startObj = DateTime::createFromFormat('d/m/Y', $dates[0]);
            $endObj = isset($dates[1])
                ? DateTime::createFromFormat('d/m/Y', $dates[1])
                : $startObj;

            if ($startObj && $endObj) {
                $startDate = $startObj->format('Y-m-d');
                $endDate = $endObj->format('Y-m-d');

                if ($startDate > $endDate) {
                    [$startDate, $endDate] = [$endDate, $startDate];
                }

                $where[] = 'DATE(created_at) BETWEEN :start_date AND :end_date';
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }
        }
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users $whereSql");
    $countStmt->execute($params);
    $totalFiltered = (int) ($countStmt->fetch()['total'] ?? 0);

    $totalPages = max(1, (int) ceil($totalFiltered / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;

    $stmt = $pdo->prepare("SELECT id, full_name, username, email, phone, last_login_at, created_at FROM users $whereSql ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $key => $value) {
        $stmt->bindValue(':' . $key, $value);
    }
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $users = $stmt->fetchAll();

} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pengguna - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
</head>
<body>
    <?php $active_menu = 'users'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>
    <div class="main-content">
        <div class="content-header">
            <h1>Manage Pengguna</h1>
            <a href="add_user" class="btn-primary">Tambah Pengguna</a>
        </div>
        <?php if (isset($_GET['updated'])): ?>
        <div class="message success">Pengguna berhasil diperbarui.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="message success">Pengguna berhasil dihapus.</div>
        <?php elseif (isset($_GET['added'])): ?>
        <div class="message success">Pengguna berhasil ditambahkan.</div>
        <?php endif; ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Total Pengguna</div>
            </div>
        </div>
        <div class="filter-panel">
            <form id="filterForm" method="get">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="date_range">Tgl. Daftar</label>
                        <input type="text" name="date_range" id="date_range" class="date-range-input" placeholder="-- Pilih tanggal --" readonly value="<?php echo htmlspecialchars($filters['date_range']); ?>" data-initial-range="<?php echo htmlspecialchars($filters['date_range']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="account_id">ID Akun</label>
                        <input type="text" name="account_id" id="account_id" value="<?php echo htmlspecialchars($filters['account_id']); ?>" >
                    </div>
                    <div class="filter-field">
                        <label for="search">Nama / No. HP</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($filters['username']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="email">Email</label>
                        <input type="text" name="email" id="email" value="<?php echo htmlspecialchars($filters['email']); ?>">
                    </div>
                </div>
                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>
        <div class="user-list">
            <h2>Daftar Pengguna</h2>
            <br>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Lengkap</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Terakhir Login</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$users): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Tidak ada data pengguna yang sesuai filter.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo $user['last_login_at'] ? date('d/m/Y H:i', strtotime($user['last_login_at'])) : 'Belum pernah'; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <a href="view_user.php?id=<?php echo $user['id']; ?>" class="icon-btn icon-view" title="Lihat Detail">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </a>
                            <a href="edit_user?id=<?php echo $user['id']; ?>" class="icon-btn icon-edit" title="Edit Pengguna">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <a href="#" onclick="showDeleteModal('user', <?php echo $user['id']; ?>)" class="icon-btn icon-delete" title="Hapus Pengguna">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3,6 5,6 21,6"></polyline>
                                    <path d="M19,6v14a2,2 0 0,1-2,2H7a2,2 0 0,1-2-2V6m3,0V4a2,2 0 0,1,2-2h4a2,2 0 0,1,2,2v2"></path>
                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php
                $baseParams = $_GET;
                unset($baseParams['page']);
            ?>
            <div class="table-footer">
                <div class="pagination-info">
                    Halaman <?php echo $page; ?> dari <?php echo $totalPages; ?>
                </div>
                
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                    <?php $prevParams = $baseParams; $prevParams['page'] = $page - 1; ?>
                    <a class="btn-outline" href="?<?php echo htmlspecialchars(http_build_query($prevParams)); ?>">Sebelumnya</a>
                    <?php endif; ?>

                    <?php if ($page < $totalPages): ?>
                    <?php $nextParams = $baseParams; $nextParams['page'] = $page + 1; ?>
                    <a class="btn-primary" href="?<?php echo htmlspecialchars(http_build_query($nextParams)); ?>">Selanjutnya</a>
                    <?php endif; ?>
                </div>

                <div class="per-page-footer">
                    <label for="per_page_footer">Tampilkan:</label>
                    <select id="per_page_footer" onchange="updatePerPage(this.value)">
                        <?php foreach ($allowedPerPage as $limitOption): ?>
                        <option value="<?php echo $limitOption; ?>" <?php echo $perPage === $limitOption ? 'selected' : ''; ?>>
                            <?php echo $limitOption; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus pengguna ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button id="confirmBtn" class="btn-modal btn-confirm">Hapus</button>
                <button id="cancelBtn" class="btn-modal btn-cancel">Batal</button>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="../js/script.js"></script>
</body>
</html>