<?php

require_once '../../../app/helpers.php';

require_admin_auth();

// Pagination setup
$allowedPerPage = [25, 50, 100, 250];
$perPage = (int) ($_GET['per_page'] ?? 25);
if (!in_array($perPage, $allowedPerPage, true)) {
    $perPage = 25;
}

$page = (int) ($_GET['page'] ?? 1);
if ($page < 1) {
    $page = 1;
}

// Build Filter Query
$filters = [
    'name' => trim((string) ($_GET['name'] ?? '')),
    'slug' => trim((string) ($_GET['slug'] ?? '')),
    'description' => trim((string) ($_GET['description'] ?? '')),
];

$sqlCount = "SELECT COUNT(*) FROM categories WHERE 1=1";
$sqlList = "SELECT * FROM categories WHERE 1=1";
$params = [];

if ($filters['name'] !== '') {
    $sqlCount .= " AND name LIKE :name";
    $sqlList .= " AND name LIKE :name";
    $params['name'] = '%' . $filters['name'] . '%';
}
if ($filters['slug'] !== '') {
    $sqlCount .= " AND slug LIKE :slug";
    $sqlList .= " AND slug LIKE :slug";
    $params['slug'] = '%' . $filters['slug'] . '%';
}
if ($filters['description'] !== '') {
    $sqlCount .= " AND description LIKE :description";
    $sqlList .= " AND description LIKE :description";
    $params['description'] = '%' . $filters['description'] . '%';
}

// Get total count for pagination
$stmtCount = db()->prepare($sqlCount);
$stmtCount->execute($params);
$totalFiltered = (int) $stmtCount->fetchColumn();

$totalPages = max(1, (int) ceil($totalFiltered / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
}

$offset = ($page - 1) * $perPage;

$sqlList .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

$stmt = db()->prepare($sqlList);
// bind filter params (prepend colon)
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll();

$totalCategories = (int) db()->query("SELECT COUNT(*) FROM categories")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Kategori - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'categories'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Manage Kategori</h1>
            <a href="add_category" class="btn-primary">Tambah Kategori</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
        <div class="message success">Kategori berhasil ditambahkan.</div>
        <?php elseif (isset($_GET['updated'])): ?>
        <div class="message success">Kategori berhasil diperbarui.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="message success">Kategori berhasil dihapus.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalCategories; ?></div>
                <div class="stat-label">Total Kategori</div>
            </div>
        </div>

        <div class="filter-panel">
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="per_page" value="<?php echo $perPage; ?>">
                <div class="filter-row">
                    <div class="filter-field">
                        <label for="name">Nama</label>
                        <input type="text" name="name" id="name" placeholder="Masukkan nama..." value="<?php echo htmlspecialchars($filters['name']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="slug">Slug</label>
                        <input type="text" name="slug" id="slug" placeholder="Masukkan slug..." value="<?php echo htmlspecialchars($filters['slug']); ?>">
                    </div>
                    <div class="filter-field">
                        <label for="description">Deskripsi</label>
                        <input type="text" name="description" id="description" placeholder="Masukkan kata kunci..." value="<?php echo htmlspecialchars($filters['description']); ?>">
                    </div>
                </div>
                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>

        <div class="user-list">
            <h2>Daftar Kategori</h2>
            <br>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Kategori</th>
                        <th>Slug</th>
                        <th>Deskripsi</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$categories): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">Belum ada kategori.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo (int) $category['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($category['name']); ?></strong></td>
                        <td><code><?php echo htmlspecialchars($category['slug']); ?></code></td>
                        <td><?php echo htmlspecialchars(mb_strimwidth($category['description'] ?? '-', 0, 50, '...')); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                        <td>
                            <a href="edit_category?id=<?php echo (int) $category['id']; ?>" class="icon-btn icon-edit" title="Edit Kategori">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <a href="#" onclick="showDeleteCategoryModal(<?php echo (int) $category['id']; ?>); return false;" class="icon-btn icon-delete" title="Hapus Kategori">
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

    <div id="confirmCategoryModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus kategori ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button id="confirmCategoryBtn" class="btn-modal btn-confirm">Hapus</button>
                <button id="cancelCategoryBtn" class="btn-modal btn-cancel">Batal</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
    let deleteCategoryId = null;

    function showDeleteCategoryModal(id) {
        deleteCategoryId = id;
        document.getElementById('confirmCategoryModal').style.display = 'block';
    }

    function hideDeleteCategoryModal() {
        document.getElementById('confirmCategoryModal').style.display = 'none';
        deleteCategoryId = null;
    }

    document.getElementById('cancelCategoryBtn').addEventListener('click', hideDeleteCategoryModal);
    
    document.getElementById('confirmCategoryBtn').addEventListener('click', function() {
        if (deleteCategoryId) {
            window.location.href = 'delete_category.php?id=' + deleteCategoryId;
        }
    });

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmCategoryModal');
        if (event.target === modal) {
            hideDeleteCategoryModal();
        }
    });

    // Handle escape key
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideDeleteCategoryModal();
        }
    });
    </script>
</body>
</html>
