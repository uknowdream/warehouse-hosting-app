<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/actions.php';
require_once __DIR__ . '/includes/layout.php';
require_login();
handle_post_actions();

$page = $_GET['p'] ?? 'dashboard';
$allowedPages = array_column(menu_items(), 0);
if (!in_array($page, $allowedPages, true)) $page = 'dashboard';
if (!page_allowed($page)) { flash('danger', 'Anda tidak memiliki akses ke halaman tersebut.'); $page = 'dashboard'; }

// Export CSV endpoint
if ($page === 'reports' && isset($_GET['export'])) {
    require_perm('report_export');
    require __DIR__ . '/pages/export.php';
    exit;
}

render_header($page);
$file = __DIR__ . '/pages/' . $page . '.php';
if (is_file($file)) require $file; else require __DIR__ . '/pages/dashboard.php';
render_footer();
