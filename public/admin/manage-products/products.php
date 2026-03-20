<?php

require_once '../../../app/helpers.php';

require_admin_auth();

$supportsShopeePricing = db_has_column('products', 'shopee_price')
    && db_has_column('products', 'markup')
    && db_has_column('products', 'shopee_link');

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
    'category_id' => (int) ($_GET['category_id'] ?? 0),
    'status' => (string) ($_GET['status'] ?? ''), // 'active' or 'inactive'
];

$sqlCount = "SELECT COUNT(*) FROM products p WHERE 1=1";
$sqlList = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
$params = [];

if ($filters['name'] !== '') {
    $sqlCount .= " AND p.name LIKE :name";
    $sqlList .= " AND p.name LIKE :name";
    $params['name'] = '%' . $filters['name'] . '%';
}

if ($filters['category_id'] > 0) {
    $catCond = " AND p.category_id = :category_id";
    $sqlCount .= $catCond;
    $sqlList .= $catCond;
    $params['category_id'] = $filters['category_id'];
}

if ($filters['status'] === 'active') {
    $statusCond = " AND p.is_active = 1";
    $sqlCount .= $statusCond;
    $sqlList .= $statusCond;
} elseif ($filters['status'] === 'inactive') {
    $statusCond = " AND p.is_active = 0";
    $sqlCount .= $statusCond;
    $sqlList .= $statusCond;
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

$sqlList .= " ORDER BY p.id DESC LIMIT :limit OFFSET :offset";

$stmt = db()->prepare($sqlList);
foreach ($params as $key => $val) {
    $stmt->bindValue(':' . $key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Get categories for filter dropdown
$categories = db()->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();

$totalProductsCount = (int) db()->query("SELECT COUNT(*) FROM products")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Produk - PasarKita Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php $active_menu = 'products'; $base_path = '../'; ?>
    <?php include '../partials/sidebar.php'; ?>

    <div class="main-content">
        <div class="content-header">
            <h1>Manage Produk</h1>
            <a href="add_product" class="btn-primary">Tambah Produk</a>
        </div>

        <?php if (isset($_GET['added'])): ?>
        <div class="message success">Produk berhasil ditambahkan.</div>
        <?php elseif (isset($_GET['updated'])): ?>
        <div class="message success">Produk berhasil diperbarui.</div>
        <?php elseif (isset($_GET['deleted'])): ?>
        <div class="message success">Produk berhasil dihapus.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalProductsCount; ?></div>
                <div class="stat-label">Total Produk</div>
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
                        <label for="category_id">Kategori</label>
                        <select name="category_id" id="category_id">
                            <option value="">-- Semua Kategori --</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-field">
                        <label for="status">Status</label>
                        <select name="status" id="status">
                            <option value="">-- Semua Status --</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Aktif</option>
                            <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                </div>
                <div class="filter-row filter-buttons">
                    <button type="submit" class="btn-primary">Terapkan Filter</button>
                    <button type="button" class="btn-outline" onclick="resetFilters()">Reset Filter</button>
                </div>
            </form>
        </div>

        <div class="user-list">
            <h2>Daftar Produk</h2>
            <br>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama Produk</th>
                        <th>Gambar</th>
                        <th>Kategori</th>
                        <?php if ($supportsShopeePricing): ?>
                            <th>Harga Shopee</th>
                            <th>Markup</th>
                            <th>Harga Jual</th>
                            <th>Shopee</th>
                        <?php else: ?>
                            <th>Harga</th>
                        <?php endif; ?>
                        <th>Stok</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$products): ?>
                    <tr>
                        <td colspan="<?php echo $supportsShopeePricing ? 12 : 9; ?>" style="text-align:center;">Belum ada produk.</td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo (int) $product['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <?php if ($product['badge']): ?>
                            <br><span style="background:#eee; padding:2px 6px; border-radius:4px; font-size:0.75rem; color:#666;"><?php echo htmlspecialchars($product['badge']); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $productDir = __DIR__ . '/../../products/' . $product['id'] . '/';
                            $imageCount = 0;
                            $firstImage = '';
                            if (is_dir($productDir)) {
                                $files = glob($productDir . '*');
                                if (is_array($files)) {
                                    $imageCount = count($files);
                                    if ($imageCount > 0) {
                                        sort($files, SORT_NATURAL | SORT_FLAG_CASE);
                                        $firstImage = basename($files[0]);
                                    }
                                }
                            }
                            if ($firstImage) {
                                echo '<img src="../../products/' . $product['id'] . '/' . htmlspecialchars($firstImage) . '" alt="Gambar" style="max-width: 60px; max-height: 60px; object-fit: cover; border: 1px solid #ddd; border-radius: 4px;"><br>';
                                echo '<small>' . $imageCount . ' gambar</small>';
                            } else {
                                echo '<span style="color: #999;">Tidak ada</span>';
                            }
                            ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                        <?php if ($supportsShopeePricing): ?>
                            <td>Rp <?php echo number_format((int) ($product['shopee_price'] ?? 0), 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format((int) ($product['markup'] ?? 0), 0, ',', '.'); ?></td>
                            <td><strong>Rp <?php echo number_format((int) ($product['price'] ?? 0), 0, ',', '.'); ?></strong></td>
                            <td>
                                <?php if (!empty($product['shopee_link'])): ?>
                                    <a class="btn-outline" href="<?php echo htmlspecialchars((string) $product['shopee_link']); ?>" target="_blank" rel="noopener" style="padding: 6px 10px;">Buka</a>
                                <?php else: ?>
                                    <span style="color:#999;">-</span>
                                <?php endif; ?>
                            </td>
                        <?php else: ?>
                            <td>Rp <?php echo number_format((int)$product['price'], 0, ',', '.'); ?></td>
                        <?php endif; ?>
                        <td><?php echo (int)$product['stock']; ?></td>
                        <td>
                            <span class="status status-<?php echo $product['is_active'] ? 'paid' : 'cancelled'; ?>">
                                <?php echo $product['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="edit_product?id=<?php echo (int) $product['id']; ?>" class="icon-btn icon-edit" title="Edit Produk">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                </svg>
                            </a>
                            <a href="#" onclick="showDeleteProductModal(<?php echo (int) $product['id']; ?>); return false;" class="icon-btn icon-delete" title="Hapus Produk">
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

    <div id="confirmProductModal" class="modal">
        <div class="modal-content">
            <h3>Konfirmasi Hapus</h3>
            <p>Apakah Anda yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="modal-buttons">
                <button id="confirmProductBtn" class="btn-modal btn-confirm">Hapus</button>
                <button id="cancelProductBtn" class="btn-modal btn-cancel">Batal</button>
            </div>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
    let deleteProductId = null;

    function showDeleteProductModal(id) {
        deleteProductId = id;
        document.getElementById('confirmProductModal').style.display = 'block';
    }

    function hideDeleteProductModal() {
        document.getElementById('confirmProductModal').style.display = 'none';
        deleteProductId = null;
    }

    document.getElementById('cancelProductBtn').addEventListener('click', hideDeleteProductModal);
    
    document.getElementById('confirmProductBtn').addEventListener('click', function() {
        if (deleteProductId) {
            window.location.href = 'delete_product.php?id=' + deleteProductId;
        }
    });

    window.addEventListener('click', function(event) {
        const modal = document.getElementById('confirmProductModal');
        if (event.target === modal) {
            hideDeleteProductModal();
        }
    });

    // Handle escape key
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideDeleteProductModal();
        }
    });
    </script>
</body>
</html>
