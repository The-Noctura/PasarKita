<?php
// Default active menu
$active_menu = $active_menu ?? 'dashboard';
// Base path for links, default empty (from admin root)
$base_path = $base_path ?? '';
?>
<div class="sidebar">
    <div class="sidebar-header">
        <h2>PasarKita Admin</h2>
        <button type="button" class="nav-toggle" aria-label="Toggle menu" aria-controls="adminNav" aria-expanded="false">
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>
    </div>
    <nav class="sidebar-nav" id="adminNav">
        <div class="nav-section">
            <h3>MENU UTAMA</h3>
            <a href="<?php echo e(url('/admin/')); ?>" class="nav-link <?php echo $active_menu === 'dashboard' ? 'active' : ''; ?>">Beranda</a>
        </div>
        <div class="nav-section">
            <h3>TRANSAKSI</h3>
            <a href="<?php echo e(url('/admin/manage-transactions/transactions')); ?>" class="nav-link <?php echo $active_menu === 'transactions' ? 'active' : ''; ?>">Transaksi</a>
        </div>
        <div class="nav-section">
            <h3>MANAGE PENGGUNA</h3>
            <a href="<?php echo e(url('/admin/manage-account/users')); ?>" class="nav-link <?php echo in_array($active_menu, ['users','admins']) ? 'active' : ''; ?>">Pengguna</a>
            <a href="<?php echo e(url('/admin/manage-account/user_logs')); ?>" class="nav-link <?php echo $active_menu === 'user_logs' ? 'active' : ''; ?>">User Logs</a>
        </div>
        <div class="nav-section">
            <h3>MANAGE PRODUK</h3>
            <a href="<?php echo e(url('/admin/manage-categories/categories')); ?>" class="nav-link <?php echo $active_menu === 'categories' ? 'active' : ''; ?>">Kategori</a>
            <a href="<?php echo e(url('/admin/manage-products/products')); ?>" class="nav-link <?php echo $active_menu === 'products' ? 'active' : ''; ?>">Produk</a>
        </div>
        <div class="nav-section">
            <h3>SETTINGS</h3>
            <a href="<?php echo e(url('/admin/manage-settings')); ?>" class="nav-link <?php echo $active_menu === 'settings' ? 'active' : ''; ?>">Website Settings</a>
        </div>

        

        <div class="nav-section">
            <h3>AKUN</h3>
            <a href="<?php echo e(url('/admin/manage-account/admin_logs')); ?>" class="nav-link <?php echo $active_menu === 'admin_logs' ? 'active' : ''; ?>">Admin Logs</a>
            <form method="POST" action="<?php echo e(url('/logout')); ?>" style="margin: 0;">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="redirect" value="<?php echo e(url('/admin/login')); ?>" />
                <button type="submit" class="nav-link nav-link--button">Logout</button>
            </form>
        </div>
    </nav>
</div>