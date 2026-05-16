<?php
function render_header(string $page): void {
    $user = current_user();
    $titles = [
        'dashboard'=>'Dashboard','master'=>'Master Barang','stock'=>'Data Stock','qr'=>'Generate QR','movement'=>'Stock Masuk/Keluar','opname'=>'Stock Opname QR','transfer'=>'Transfer Gudang','quality'=>'Retur & Karantina','purchase'=>'Purchase Request','approval'=>'Approval','supplier'=>'Supplier','reports'=>'Laporan','audit'=>'Audit Trail','roles'=>'Role Management','settings'=>'Setting Data'
    ];
    if (function_exists('enterprise_titles')) $titles = array_merge($titles, enterprise_titles());
    $pageTitle = $titles[$page] ?? 'Warehouse';
    $pageGroup = 'Workspace';
    foreach (menu_items() as $item) {
        if ($item[0] === $page) {
            $pageGroup = $item[4] ?? 'Workspace';
            break;
        }
    }
    $quickLinks = [
        ['dashboard', 'Dashboard', 'DB'],
        ['stock', 'Stock', 'SK'],
        ['qr', 'QR', 'QR'],
        ['reports', 'Laporan', 'RP'],
    ];
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?> - Warehouse Inventory</title>
  <script>
    (function () {
      try {
        if (localStorage.getItem('warehouse.sidebar.hidden') === '1') {
          document.documentElement.classList.add('pref-sidebar-hidden');
        }
      } catch (error) {}
    })();
  </script>
  <link rel="stylesheet" href="assets/css/style.css?v=1.5">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
  <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<a class="skip-link" href="#mainContent">Lewati ke konten</a>
<button class="mobile-menu-btn" type="button" onclick="toggleSidebar()" aria-label="Buka menu"><span class="mobile-menu-icon" aria-hidden="true"></span></button>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="brand">
      <div class="brand-logo">WG</div>
      <div class="brand-copy"><h1>Warehouse Pro</h1><small><?= h($user['role_name'] ?? '') ?></small></div>
    </div>
    <div class="sidebar-search">
      <input class="nav-search" type="search" placeholder="Cari menu..." aria-label="Cari menu" data-nav-search>
    </div>
    <nav class="nav">
      <?php
        $menuGroups = [];
        foreach (menu_items() as $m) {
            if (!has_perm($m[3])) continue;
            $group = $m[4] ?? 'Menu';
            if (!isset($menuGroups[$group])) $menuGroups[$group] = [];
            $menuGroups[$group][] = $m;
        }
      ?>
      <?php foreach ($menuGroups as $group => $items): $groupKey = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $group)); $hasActive = false; foreach ($items as $item) { if ($page === $item[0]) { $hasActive = true; break; } } ?>
        <div class="nav-group <?= $hasActive ? 'has-active' : '' ?>" data-nav-group="<?= h($groupKey) ?>">
          <button class="nav-group-toggle" type="button" aria-expanded="true">
            <span class="nav-group-title"><?= h($group) ?></span>
            <span class="nav-group-meta"><?= h(count($items)) ?></span>
            <span class="nav-caret" aria-hidden="true">v</span>
          </button>
          <div class="nav-group-items">
            <?php foreach ($items as $m): ?>
              <a class="nav-btn <?= $page === $m[0] ? 'active' : '' ?>" href="<?= h(url($m[0])) ?>" title="<?= h($m[1]) ?>"><span class="nav-icon"><?= h($m[2]) ?></span><span class="nav-label"><?= h($m[1]) ?></span></a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
      <div class="nav-empty" data-nav-empty hidden>Menu tidak ditemukan.</div>
    </nav>
  </aside>
  <main class="main">
    <header class="topbar">
      <div class="topbar-title">
        <button class="sidebar-toggle-btn" type="button" data-sidebar-toggle data-hide-label="Sembunyikan menu" data-show-label="Tampilkan menu" aria-label="Sembunyikan menu" title="Sembunyikan menu">
          <span class="sidebar-toggle-icon" aria-hidden="true"></span>
        </button>
        <div>
          <div class="page-kicker"><?= h($pageGroup) ?></div>
          <h2><?= h($pageTitle) ?></h2>
        </div>
      </div>
      <div class="top-actions">
        <div class="quick-actions" aria-label="Akses cepat">
          <?php foreach ($quickLinks as $link): if (!page_allowed($link[0])) continue; ?>
            <a class="quick-action <?= $page === $link[0] ? 'active' : '' ?>" href="<?= h(url($link[0])) ?>" aria-label="<?= h($link[1]) ?>" title="<?= h($link[1]) ?>"><?= h($link[2]) ?></a>
          <?php endforeach; ?>
        </div>
        <span class="user-pill"><?= h($user['name'] ?? '') ?> &middot; <?= h($user['role_name'] ?? '') ?></span>
        <a class="btn btn-outline" href="logout.php">Logout</a>
      </div>
    </header>
    <section class="content" id="mainContent">
      <?php foreach (get_flash() as $f): ?>
        <div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div>
      <?php endforeach; ?>
    <?php
}
function render_footer(): void { ?>
    </section>
  </main>
</div>
<div class="sidebar-backdrop" data-sidebar-backdrop></div>
<div class="confirm-modal" id="confirmModal" aria-hidden="true">
  <div class="confirm-backdrop" data-confirm-cancel></div>
  <div class="confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-icon" id="confirmIcon">!</div>
    <div class="confirm-copy">
      <h3 id="confirmTitle">Konfirmasi aksi</h3>
      <p id="confirmMessage">Pastikan data sudah benar sebelum melanjutkan.</p>
    </div>
    <div class="confirm-actions">
      <button class="btn btn-outline" type="button" data-confirm-cancel>Batal</button>
      <button class="btn btn-primary" type="button" id="confirmContinue">Lanjutkan</button>
    </div>
  </div>
</div>
<script src="assets/js/app.js?v=1.5"></script>
</body>
</html>
<?php }

function post_form_open(string $action, string $back = ''): string {
    return '<form method="post">' . csrf_field() . '<input type="hidden" name="action" value="' . h($action) . '">' . '<input type="hidden" name="_back" value="' . h($back ?: ($_GET['p'] ?? 'dashboard')) . '">';
}
function action_form(string $action, array $hidden, string $label, string $class = 'btn btn-sm'): string {
    $buttonClass = trim(str_replace('inline-form', '', $class));
    $formClass = str_contains($class, 'inline-form') ? 'inline-form action-form' : 'action-form';
    $title = 'Konfirmasi aksi';
    $message = 'Pastikan data sudah benar sebelum melanjutkan.';
    $tone = 'primary';
    $lowerLabel = strtolower($label);
    if (str_contains($lowerLabel, 'hapus')) {
        $title = 'Hapus data?';
        $message = 'Data akan dihapus dari sistem.';
        $tone = 'danger';
    } elseif (str_contains($lowerLabel, 'reject') || str_contains($lowerLabel, 'tolak')) {
        $title = 'Tolak approval?';
        $message = 'Permintaan akan ditandai sebagai ditolak.';
        $tone = 'danger';
    } elseif (str_contains($lowerLabel, 'batal') || str_contains($lowerLabel, 'cancel')) {
        $title = 'Batalkan data?';
        $message = 'Data pending akan ditandai sebagai cancelled.';
        $tone = 'danger';
    } elseif (str_contains($lowerLabel, 'reset')) {
        $title = 'Reset password?';
        $message = 'Password user akan diganti sesuai nilai yang dikirim.';
        $tone = 'primary';
    } elseif (str_contains($lowerLabel, 'nonaktif')) {
        $title = 'Nonaktifkan user?';
        $message = 'User tidak dapat login sampai diaktifkan kembali.';
        $tone = 'danger';
    } elseif (str_contains($lowerLabel, 'approve') || str_contains($lowerLabel, 'setujui')) {
        $title = 'Setujui approval?';
        $message = 'Efek transaksi akan diterapkan setelah disetujui.';
        $tone = 'success';
    }
    $html = '<form method="post" class="' . h($formClass) . '" data-confirm="true" data-confirm-title="' . h($title) . '" data-confirm-message="' . h($message) . '" data-confirm-tone="' . h($tone) . '">' . csrf_field() . '<input type="hidden" name="action" value="' . h($action) . '">' . '<input type="hidden" name="_back" value="' . h($_GET['p'] ?? 'dashboard') . '">';
    foreach ($hidden as $k=>$v) $html .= '<input type="hidden" name="' . h($k) . '" value="' . h($v) . '">';
    $html .= '<button type="submit" class="' . h($buttonClass) . '">' . h($label) . '</button></form>';
    return $html;
}
