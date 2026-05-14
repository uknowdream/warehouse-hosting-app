<?php
require_perm('page_roles');

$addingRole = isset($_GET['add_role']);
$roleEditId = isset($_GET['edit_role']) ? (int)$_GET['edit_role'] : 0;
$userEditId = isset($_GET['edit_user']) ? (int)$_GET['edit_user'] : 0;
$showRoleForm = $addingRole || $roleEditId > 0;

$roles = all_rows("SELECT * FROM roles ORDER BY id");
$perms = all_rows("SELECT * FROM permissions ORDER BY group_name, label");
$rolePerms = all_rows("SELECT role_id, permission_id FROM role_permissions");
$users = all_rows("SELECT u.*, r.name role_name FROM users u JOIN roles r ON r.id=u.role_id ORDER BY u.id DESC");
$roleUsage = all_rows("SELECT role_id, COUNT(*) user_count FROM users GROUP BY role_id");

$permMap = [];
foreach ($rolePerms as $rp) $permMap[(int)$rp['role_id']][(int)$rp['permission_id']] = true;
$userCountMap = [];
foreach ($roleUsage as $usage) $userCountMap[(int)$usage['role_id']] = (int)$usage['user_count'];

$roleEdit = $roleEditId ? one("SELECT * FROM roles WHERE id=?", [$roleEditId]) : null;
if ($roleEditId && !$roleEdit) {
    flash('warning', 'Role tidak ditemukan.');
    $showRoleForm = false;
}
$userEdit = $userEditId ? one("SELECT * FROM users WHERE id=?", [$userEditId]) : null;

$visiblePerms = $perms;
if ((current_user()['role_name'] ?? '') !== 'Admin') {
    $allowedRows = all_rows("SELECT permission_id FROM role_permissions WHERE role_id=?", [current_user()['role_id']]);
    $allowedIds = array_map('intval', array_column($allowedRows, 'permission_id'));
    $visiblePerms = array_values(array_filter($perms, fn($p) => in_array((int)$p['id'], $allowedIds, true)));
}

$menuPerms = [];
$featureGroups = [];
foreach ($visiblePerms as $p) {
    if ($p['group_name'] === 'Halaman') $menuPerms[] = $p;
    else $featureGroups[$p['group_name']][] = $p;
}
ksort($featureGroups);

$formRoleId = (int)($roleEdit['id'] ?? 0);
$formPermMap = $formRoleId ? ($permMap[$formRoleId] ?? []) : [];
$totalRoles = count($roles);
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => (int)$u['is_active'] === 1));
$totalPerms = count($perms);
?>
<div class="role-shell">
  <div class="role-kpis">
    <div class="card dashboard-mini"><span>Total Role</span><strong><?= h($totalRoles) ?></strong></div>
    <div class="card dashboard-mini"><span>User Aktif</span><strong><?= h($activeUsers) ?>/<?= h($totalUsers) ?></strong></div>
    <div class="card dashboard-mini"><span>Total Permission</span><strong><?= h($totalPerms) ?></strong></div>
    <div class="card dashboard-mini"><span>CRUD Flow</span><strong>Role</strong></div>
  </div>

  <div class="card">
    <div class="section-title">
      <div>
        <h3>Daftar Role</h3>
        <span class="small muted">CRUD role dilakukan dari tombol tambah/edit. <span data-search-count="tableRoles"><?= h(count($roles)) ?></span> role tampil.</span>
      </div>
      <?php if(has_perm('role_manage')): ?><a class="btn btn-primary" href="<?= h(url('roles',['add_role'=>1])) ?>">Tambah Role</a><?php endif; ?>
    </div>
    <div class="toolbar"><input class="input" id="searchRoles" data-search-input="tableRoles" placeholder="Cari role, deskripsi, struktur..."></div>
    <div class="table-wrap">
      <table class="table" id="tableRoles">
        <thead><tr><th>Role</th><th>Deskripsi</th><th>User</th><th>Permission</th><th>Struktur</th><th>Aksi</th></tr></thead>
        <tbody>
          <?php foreach($roles as $role): $rid=(int)$role['id']; $isSystem=$rid===1; ?>
            <tr data-search-row data-search="<?= h(implode(' ', [$role['name'], $role['description'], $userCountMap[$rid] ?? 0, $isSystem ? 'system role full access' : 'custom role', count($permMap[$rid] ?? [])])) ?>">
              <td data-label="Role"><strong><?= h($role['name']) ?></strong></td>
              <td data-label="Deskripsi"><?= h($role['description'] ?: '-') ?></td>
              <td data-label="User"><?= h($userCountMap[$rid] ?? 0) ?> user</td>
              <td data-label="Permission"><?= $isSystem ? status_badge('Full Access') : h(count($permMap[$rid] ?? [])) . ' izin' ?></td>
              <td data-label="Struktur"><?= $isSystem ? 'System role' : 'Custom role' ?></td>
              <td data-label="Aksi" class="actions">
                <?php if($isSystem): ?>
                  <span class="small muted">Tidak dapat diedit</span>
                <?php elseif(has_perm('role_manage')): ?>
                  <a class="btn btn-sm" href="<?= h(url('roles',['edit_role'=>$rid])) ?>">Edit Role & Permission</a>
                  <?= action_form('role_delete',['id'=>$rid],'Hapus','btn btn-sm btn-danger') ?>
                <?php elseif(user_can_edit_role($rid)): ?>
                  <a class="btn btn-sm" href="<?= h(url('roles',['edit_role'=>$rid])) ?>">Edit Permission</a>
                <?php else: ?>
                  <span class="small muted">Tidak ada akses</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          <tr data-search-empty hidden><td colspan="6">Tidak ada role sesuai pencarian.</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <?php if($showRoleForm): $canEditRoleMeta = has_perm('role_manage'); ?>
  <div class="card role-form-card">
    <div class="section-title">
      <div>
        <h3><?= $roleEdit ? 'Edit Role & Permission' : 'Tambah Role & Permission' ?></h3>
        <span class="small muted"><?= $canEditRoleMeta ? 'Nama role, deskripsi, dan permission disimpan dalam satu submit.' : 'Permission role disimpan dari form ini. Nama dan deskripsi hanya tampilan.' ?></span>
      </div>
      <a class="btn btn-outline" href="<?= h(url('roles')) ?>">Tutup Form</a>
    </div>

    <?php if(!$canEditRoleMeta && !$roleEdit): ?>
      <p class="muted">Role Anda tidak memiliki izin menambah role.</p>
    <?php elseif($roleEdit && !user_can_edit_role((int)$roleEdit['id'])): ?>
      <p class="muted">Role ini tidak dapat diubah oleh akun Anda.</p>
    <?php else: ?>
      <?= post_form_open('role_save','roles') ?>
        <input type="hidden" name="id" value="<?= h($roleEdit['id'] ?? '') ?>">
        <input type="hidden" name="sync_permissions" value="1">
        <div class="form-grid">
          <div class="field"><label>Nama Role</label><input class="input" name="name" value="<?= h($roleEdit['name'] ?? '') ?>" <?= $canEditRoleMeta ? '' : 'readonly' ?> required></div>
          <div class="field"><label>Deskripsi</label><input class="input" name="description" value="<?= h($roleEdit['description'] ?? '') ?>" <?= $canEditRoleMeta ? '' : 'readonly' ?>></div>
        </div>

        <div class="permission-toolbar">
          <input class="input" id="permissionSearch" placeholder="Cari permission..." type="search">
          <button class="btn btn-outline" type="button" data-permission-toggle="all-on">Pilih Semua</button>
          <button class="btn btn-outline" type="button" data-permission-toggle="all-off">Kosongkan</button>
        </div>

        <div class="permission-group" data-permission-group>
          <div class="permission-group-head">
            <h4>Akses Menu</h4>
            <div class="actions">
              <button class="btn btn-sm btn-outline" type="button" data-permission-toggle="group-on">Pilih Grup</button>
              <button class="btn btn-sm btn-outline" type="button" data-permission-toggle="group-off">Kosongkan</button>
            </div>
          </div>
          <div class="permission-grid">
            <?php foreach($menuPerms as $p): ?>
              <label class="permission-item" data-permission-item>
                <input type="checkbox" name="permissions[]" value="<?= h($p['id']) ?>" <?= isset($formPermMap[$p['id']]) ? 'checked' : '' ?>>
                <strong><?= h($p['label']) ?></strong><br>
                <span class="small muted"><?= h($p['perm_key']) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <?php foreach($featureGroups as $groupName => $groupPerms): ?>
          <div class="permission-group" data-permission-group>
            <div class="permission-group-head">
              <h4><?= h($groupName) ?></h4>
              <div class="actions">
                <button class="btn btn-sm btn-outline" type="button" data-permission-toggle="group-on">Pilih Grup</button>
                <button class="btn btn-sm btn-outline" type="button" data-permission-toggle="group-off">Kosongkan</button>
              </div>
            </div>
            <div class="permission-grid">
              <?php foreach($groupPerms as $p): ?>
                <label class="permission-item" data-permission-item>
                  <input type="checkbox" name="permissions[]" value="<?= h($p['id']) ?>" <?= isset($formPermMap[$p['id']]) ? 'checked' : '' ?>>
                  <strong><?= h($p['label']) ?></strong><br>
                  <span class="small muted"><?= h($p['perm_key']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>

        <button class="btn btn-primary" style="margin-top:12px"><?= !$canEditRoleMeta ? 'Simpan Permission' : ($roleEdit ? 'Update Role & Permission' : 'Simpan Role & Permission') ?></button>
      </form>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if(has_perm('user_manage')): ?>
  <div class="grid grid-2">
    <div class="card">
      <div class="section-title"><h3><?= $userEdit ? 'Edit User' : 'Tambah User' ?></h3></div>
      <?= post_form_open('user_save','roles') ?>
        <input type="hidden" name="id" value="<?= h($userEdit['id'] ?? '') ?>">
        <div class="form-grid">
          <div class="field"><label>Nama</label><input class="input" name="name" value="<?= h($userEdit['name'] ?? '') ?>" required></div>
          <div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= h($userEdit['email'] ?? '') ?>" required></div>
          <div class="field"><label><?= $userEdit ? 'Password Baru (opsional)' : 'Password Awal' ?></label><input class="input" name="password" placeholder="<?= $userEdit ? 'Kosongkan jika tidak diganti' : 'default password123' ?>"></div>
          <div class="field"><label>Role</label><select class="select" name="role_id"><?php foreach($roles as $r): ?><option value="<?= h($r['id']) ?>" <?= (($userEdit['role_id'] ?? '')==$r['id'])?'selected':'' ?>><?= h($r['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <button class="btn btn-primary"><?= $userEdit ? 'Update User' : 'Tambah User' ?></button>
        <?php if($userEdit): ?><a class="btn btn-outline" href="<?= h(url('roles')) ?>">Batal</a><?php endif; ?>
      </form>
    </div>
    <div class="card">
      <div class="section-title"><h3>Daftar User</h3><span class="small muted"><span data-search-count="tableUsers"><?= h(count($users)) ?></span> user tampil.</span></div>
      <div class="toolbar"><input class="input" id="searchUsers" data-search-input="tableUsers" placeholder="Cari nama, email, role, status..."></div>
      <div class="table-wrap"><table class="table" id="tableUsers"><thead><tr><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach($users as $u): ?>
          <tr data-search-row data-search="<?= h(implode(' ', [$u['name'], $u['email'], $u['role_name'], $u['is_active'] ? 'active aktif' : 'inactive nonaktif'])) ?>">
            <td data-label="Nama"><?= h($u['name']) ?></td>
            <td data-label="Email"><?= h($u['email']) ?></td>
            <td data-label="Role"><?= status_badge($u['role_name']) ?></td>
            <td data-label="Status"><?= status_badge($u['is_active']?'Active':'Inactive') ?></td>
            <td data-label="Aksi" class="actions">
              <a class="btn btn-sm" href="<?= h(url('roles',['edit_user'=>$u['id']])) ?>">Edit</a>
              <?= action_form('user_toggle',['id'=>$u['id']], $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan', $u['is_active'] ? 'btn btn-sm btn-warning' : 'btn btn-sm btn-success') ?>
              <?php if(has_perm('user_delete')): ?><?= action_form('user_delete',['id'=>$u['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr data-search-empty hidden><td colspan="5">Tidak ada user sesuai pencarian.</td></tr>
      </tbody></table></div>
    </div>
  </div>
  <?php endif; ?>
</div>
