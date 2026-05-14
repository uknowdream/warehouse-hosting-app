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
  <title>Login Warehouse Inventory</title><link rel="stylesheet" href="assets/css/style.css?v=1.1">
</head>
<body class="login-body">
  <div class="login-card">
    <div class="brand brand-login"><div class="brand-logo">WG</div><div><h1>Warehouse Pro</h1><small>Login Inventory System</small></div></div>
    <?php foreach (get_flash() as $f): ?><div class="alert alert-<?= h($f['type']) ?>"><?= h($f['message']) ?></div><?php endforeach; ?>
    <form method="post">
      <?= csrf_field() ?>
      <label>Email</label><input class="input" name="email" value="admin@warehouse.local" required>
      <label>Password</label><input class="input" name="password" type="password" value="admin123" required>
      <button class="btn btn-primary full">Masuk</button>
    </form>
    <div class="demo-box">
      <strong>Akun Demo</strong><br>
      Admin: admin@warehouse.local / admin123<br>
      Manager: manager@warehouse.local / manager123<br>
      Staff: staff@warehouse.local / staff123<br>
      Viewer: viewer@warehouse.local / viewer123
    </div>
  </div>
</body>
</html>
