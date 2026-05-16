<?php
require_once __DIR__ . '/includes/bootstrap.php';
if (logged_in()) redirect('dashboard');
if (is_post()) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $u = one("SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id WHERE u.email=? AND u.is_active=1", [$email]);
    if ($u && password_verify($password, $u['password'])) {
        unset($u['password']);
        $_SESSION['user'] = $u;
        audit_log('Login', $email . ' masuk sistem');
        redirect('dashboard');
    }
    flash('danger', 'Email atau password salah.');
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Warehouse Inventory</title><link rel="stylesheet" href="assets/css/style.css?v=1.5">
</head>
<body class="login-body">
  <main class="login-shell">
    <section class="login-card">
      <div class="brand brand-login"><div class="brand-logo">WG</div><div><h1>Warehouse Pro</h1><small>Inventory System</small></div></div>
      <div class="login-heading">
        <p class="page-kicker">Masuk aplikasi</p>
        <h2>Selamat datang kembali.</h2>
      </div>
      <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div><?php endforeach; ?>
      <form method="post" class="login-form">
        <?= csrf_field() ?>
        <div class="field"><label>Email</label><input class="input" name="email" value="admin@warehouse.local" autocomplete="username" required></div>
        <div class="field"><label>Password</label><input class="input" name="password" type="password" value="admin123" autocomplete="current-password" required></div>
        <button class="btn btn-primary full">Masuk</button>
      </form>
      <div class="demo-box">
        <strong>Akun Demo</strong>
        <div class="demo-grid">
          <span>Admin</span><code>admin@warehouse.local / admin123</code>
          <span>Manager</span><code>manager@warehouse.local / manager123</code>
          <span>Staff</span><code>staff@warehouse.local / staff123</code>
          <span>Viewer</span><code>viewer@warehouse.local / viewer123</code>
        </div>
      </div>
    </section>
    <section class="login-aside" aria-label="Ringkasan sistem">
      <div class="login-aside-card">
        <div class="login-aside-logo">WG</div>
        <h2>Warehouse Pro</h2>
        <p>Ruang kerja tim gudang untuk aktivitas harian yang lebih tertata.</p>
        <div class="login-metrics">
          <div><strong>Rapi</strong><span>data</span></div>
          <div><strong>Cepat</strong><span>akses</span></div>
          <div><strong>Aman</strong><span>role</span></div>
        </div>
      </div>
    </section>
  </main>
</body>
</html>
