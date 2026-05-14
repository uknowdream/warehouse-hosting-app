<?php
require_perm('page_settings');
$warehouseEditId = isset($_GET['edit_warehouse']) ? (int)$_GET['edit_warehouse'] : 0;
$categoryEditId = isset($_GET['edit_category']) ? (int)$_GET['edit_category'] : 0;
$locationEditId = isset($_GET['edit_location']) ? (int)$_GET['edit_location'] : 0;
$departmentEditId = isset($_GET['edit_department']) ? (int)$_GET['edit_department'] : 0;
$costCenterEditId = isset($_GET['edit_cost_center']) ? (int)$_GET['edit_cost_center'] : 0;
$customerEditId = isset($_GET['edit_customer']) ? (int)$_GET['edit_customer'] : 0;
$projectEditId = isset($_GET['edit_project']) ? (int)$_GET['edit_project'] : 0;
$workOrderEditId = isset($_GET['edit_work_order']) ? (int)$_GET['edit_work_order'] : 0;
$warehouses = all_rows("SELECT * FROM warehouses ORDER BY name");
$locations = all_rows("SELECT l.*, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$categories = all_rows("SELECT * FROM categories ORDER BY name");
$departments = all_rows("SELECT * FROM departments ORDER BY name");
$costCenters = all_rows("SELECT cc.*, d.name department_name FROM cost_centers cc LEFT JOIN departments d ON d.id=cc.department_id ORDER BY cc.code");
$customers = all_rows("SELECT * FROM customers ORDER BY name");
$projects = all_rows("SELECT p.*, c.name customer_name FROM projects p LEFT JOIN customers c ON c.id=p.customer_id ORDER BY p.code");
$workOrders = all_rows("SELECT wo.*, p.code project_code, c.name customer_name, d.name department_name FROM work_orders wo LEFT JOIN projects p ON p.id=wo.project_id LEFT JOIN customers c ON c.id=wo.customer_id LEFT JOIN departments d ON d.id=wo.department_id ORDER BY wo.wo_no");
$warehouseEdit = $warehouseEditId ? one("SELECT * FROM warehouses WHERE id=?", [$warehouseEditId]) : null;
$categoryEdit = $categoryEditId ? one("SELECT * FROM categories WHERE id=?", [$categoryEditId]) : null;
$locationEdit = $locationEditId ? one("SELECT * FROM locations WHERE id=?", [$locationEditId]) : null;
$departmentEdit = $departmentEditId ? one("SELECT * FROM departments WHERE id=?", [$departmentEditId]) : null;
$costCenterEdit = $costCenterEditId ? one("SELECT * FROM cost_centers WHERE id=?", [$costCenterEditId]) : null;
$customerEdit = $customerEditId ? one("SELECT * FROM customers WHERE id=?", [$customerEditId]) : null;
$projectEdit = $projectEditId ? one("SELECT * FROM projects WHERE id=?", [$projectEditId]) : null;
$workOrderEdit = $workOrderEditId ? one("SELECT * FROM work_orders WHERE id=?", [$workOrderEditId]) : null;
?>
<div class="grid grid-3">
  <div class="card">
    <div class="section-title"><h3><?= $warehouseEdit ? 'Edit Gudang' : 'Tambah Gudang' ?></h3></div>
    <?php if(has_perm('settings_write')): ?>
    <?= post_form_open('warehouse_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($warehouseEdit['id'] ?? '') ?>">
      <div class="field"><label>Nama Gudang</label><input class="input" name="name" value="<?= h($warehouseEdit['name'] ?? '') ?>" required></div>
      <div class="field"><label>Alamat / Keterangan</label><textarea class="textarea" name="address"><?= h($warehouseEdit['address'] ?? '') ?></textarea></div>
      <button class="btn btn-primary"><?= $warehouseEdit ? 'Update Gudang' : 'Tambah Gudang' ?></button>
      <?php if($warehouseEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="toolbar" style="margin-top:12px"><input class="input" id="searchWarehouse" data-search-input="tableWarehouse" placeholder="Cari gudang..."><span class="small muted"><span data-search-count="tableWarehouse"><?= h(count($warehouses)) ?></span> gudang</span></div>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableWarehouse"><thead><tr><th>Gudang</th><th>Alamat</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($warehouses as $w): ?><tr data-search-row data-search="<?= h(implode(' ', [$w['name'], $w['address']])) ?>"><td data-label="Gudang"><strong><?= h($w['name']) ?></strong></td><td data-label="Alamat"><?= h($w['address']) ?></td><td data-label="Aksi" class="actions"><?php if(has_perm('settings_write')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_warehouse'=>$w['id']])) ?>">Edit</a><?php endif; ?><?php if(has_perm('settings_delete')): ?><?= action_form('warehouse_delete',['id'=>$w['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="3">Tidak ada gudang sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3><?= $categoryEdit ? 'Edit Kategori' : 'Tambah Kategori' ?></h3></div>
    <?php if(has_perm('settings_write')): ?>
    <?= post_form_open('category_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($categoryEdit['id'] ?? '') ?>">
      <div class="field"><label>Nama Kategori</label><input class="input" name="name" value="<?= h($categoryEdit['name'] ?? '') ?>" required></div>
      <button class="btn btn-primary"><?= $categoryEdit ? 'Update Kategori' : 'Tambah Kategori' ?></button>
      <?php if($categoryEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="toolbar" style="margin-top:12px"><input class="input" id="searchCategory" data-search-input="tableCategory" placeholder="Cari kategori..."><span class="small muted"><span data-search-count="tableCategory"><?= h(count($categories)) ?></span> kategori</span></div>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableCategory"><thead><tr><th>Kategori</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($categories as $c): ?><tr data-search-row data-search="<?= h($c['name']) ?>"><td data-label="Kategori"><strong><?= h($c['name']) ?></strong></td><td data-label="Aksi" class="actions"><?php if(has_perm('settings_write')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_category'=>$c['id']])) ?>">Edit</a><?php endif; ?><?php if(has_perm('settings_delete')): ?><?= action_form('category_delete',['id'=>$c['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="2">Tidak ada kategori sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3><?= $locationEdit ? 'Edit Lokasi Rak' : 'Tambah Lokasi Rak' ?></h3></div>
    <?php if(has_perm('settings_write')): ?>
    <?= post_form_open('location_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($locationEdit['id'] ?? '') ?>">
      <div class="field"><label>Gudang</label><select class="select" name="warehouse_id" required><?php foreach($warehouses as $w): ?><option value="<?= h($w['id']) ?>" <?= (($locationEdit['warehouse_id'] ?? '')==$w['id'])?'selected':'' ?>><?= h($w['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Kode Rak / Bin</label><input class="input" name="code" value="<?= h($locationEdit['code'] ?? '') ?>" placeholder="A-01" required></div>
      <div class="field"><label>Deskripsi</label><input class="input" name="description" value="<?= h($locationEdit['description'] ?? '') ?>"></div>
      <button class="btn btn-primary"><?= $locationEdit ? 'Update Lokasi' : 'Tambah Lokasi' ?></button>
      <?php if($locationEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="toolbar" style="margin-top:12px"><input class="input" id="searchLocation" data-search-input="tableLocation" placeholder="Cari gudang, rak, deskripsi..."><span class="small muted"><span data-search-count="tableLocation"><?= h(count($locations)) ?></span> lokasi</span></div>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableLocation"><thead><tr><th>Lokasi</th><th>Deskripsi</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($locations as $l): ?><tr data-search-row data-search="<?= h(implode(' ', [$l['warehouse_name'], $l['code'], $l['description']])) ?>"><td data-label="Lokasi"><strong><?= h($l['warehouse_name'].' / '.$l['code']) ?></strong></td><td data-label="Deskripsi"><?= h($l['description']) ?></td><td data-label="Aksi" class="actions"><?php if(has_perm('settings_write')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_location'=>$l['id']])) ?>">Edit</a><?php endif; ?><?php if(has_perm('settings_delete')): ?><?= action_form('location_delete',['id'=>$l['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="3">Tidak ada lokasi sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3><?= $departmentEdit ? 'Edit Department' : 'Master Department' ?></h3></div>
    <?php if(has_perm('master_org_manage')): ?>
    <?= post_form_open('department_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($departmentEdit['id'] ?? '') ?>">
      <div class="form-grid">
        <div class="field"><label>Kode</label><input class="input" name="code" value="<?= h($departmentEdit['code'] ?? '') ?>" placeholder="PRD"></div>
        <div class="field"><label>Nama Department</label><input class="input" name="name" value="<?= h($departmentEdit['name'] ?? '') ?>" required></div>
      </div>
      <div class="field"><label>Deskripsi</label><input class="input" name="description" value="<?= h($departmentEdit['description'] ?? '') ?>"></div>
      <label class="checkline"><input type="checkbox" name="is_active" <?= ($departmentEdit['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
      <button class="btn btn-primary"><?= $departmentEdit ? 'Update Department' : 'Tambah Department' ?></button>
      <?php if($departmentEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="toolbar" style="margin-top:12px"><input class="input" id="searchDepartment" data-search-input="tableDepartment" placeholder="Cari department..."></div>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableDepartment"><thead><tr><th>Department</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($departments as $d): ?><tr data-search-row data-search="<?= h(implode(' ', [$d['code'], $d['name'], $d['description'], $d['is_active']])) ?>"><td data-label="Department"><strong><?= h(trim(($d['code'] ? $d['code'].' - ' : '').$d['name'])) ?></strong><br><span class="small muted"><?= h($d['description']) ?></span></td><td data-label="Status"><?= status_badge($d['is_active'] ? 'active' : 'inactive') ?></td><td data-label="Aksi" class="actions"><?php if(has_perm('master_org_manage')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_department'=>$d['id']])) ?>">Edit</a><?= action_form('department_delete',['id'=>$d['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="3">Tidak ada department sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3><?= $costCenterEdit ? 'Edit Cost Center' : 'Master Cost Center' ?></h3></div>
    <?php if(has_perm('master_org_manage')): ?>
    <?= post_form_open('cost_center_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($costCenterEdit['id'] ?? '') ?>">
      <div class="form-grid">
        <div class="field"><label>Kode</label><input class="input" name="code" value="<?= h($costCenterEdit['code'] ?? '') ?>" required></div>
        <div class="field"><label>Nama</label><input class="input" name="name" value="<?= h($costCenterEdit['name'] ?? '') ?>" required></div>
        <div class="field"><label>Department</label><select class="select" name="department_id"><option value="">- Tidak ada -</option><?php foreach($departments as $d): ?><option value="<?= h($d['id']) ?>" <?= (($costCenterEdit['department_id'] ?? '')==$d['id'])?'selected':'' ?>><?= h($d['name']) ?></option><?php endforeach; ?></select></div>
      </div>
      <label class="checkline"><input type="checkbox" name="is_active" <?= ($costCenterEdit['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
      <button class="btn btn-primary"><?= $costCenterEdit ? 'Update Cost Center' : 'Tambah Cost Center' ?></button>
      <?php if($costCenterEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="toolbar" style="margin-top:12px"><input class="input" id="searchCostCenter" data-search-input="tableCostCenter" placeholder="Cari cost center..."></div>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableCostCenter"><thead><tr><th>Cost Center</th><th>Dept</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($costCenters as $cc): ?><tr data-search-row data-search="<?= h(implode(' ', [$cc['code'], $cc['name'], $cc['department_name'], $cc['is_active']])) ?>"><td data-label="Cost Center"><strong><?= h($cc['code']) ?></strong><br><span class="small muted"><?= h($cc['name']) ?></span></td><td data-label="Dept"><?= h($cc['department_name']) ?></td><td data-label="Aksi" class="actions"><?php if(has_perm('master_org_manage')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_cost_center'=>$cc['id']])) ?>">Edit</a><?= action_form('cost_center_delete',['id'=>$cc['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="3">Tidak ada cost center sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>
</div>

<div class="grid grid-3">
  <div class="card">
    <div class="section-title"><h3><?= $customerEdit ? 'Edit Customer' : 'Master Customer' ?></h3></div>
    <?php if(has_perm('master_org_manage')): ?>
    <?= post_form_open('customer_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($customerEdit['id'] ?? '') ?>">
      <div class="field"><label>Nama Customer</label><input class="input" name="name" value="<?= h($customerEdit['name'] ?? '') ?>" required></div>
      <div class="form-grid">
        <div class="field"><label>Kontak</label><input class="input" name="contact_name" value="<?= h($customerEdit['contact_name'] ?? '') ?>"></div>
        <div class="field"><label>Telepon</label><input class="input" name="phone" value="<?= h($customerEdit['phone'] ?? '') ?>"></div>
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= h($customerEdit['email'] ?? '') ?>"></div>
      </div>
      <div class="field"><label>Alamat</label><textarea class="textarea" name="address"><?= h($customerEdit['address'] ?? '') ?></textarea></div>
      <button class="btn btn-primary"><?= $customerEdit ? 'Update Customer' : 'Tambah Customer' ?></button>
      <?php if($customerEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableCustomer"><thead><tr><th>Customer</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($customers as $c): ?><tr data-search-row data-search="<?= h(implode(' ', [$c['name'], $c['contact_name'], $c['phone'], $c['email']])) ?>"><td data-label="Customer"><strong><?= h($c['name']) ?></strong><br><span class="small muted"><?= h(trim($c['contact_name'].' '.$c['phone'].' '.$c['email'])) ?></span></td><td data-label="Aksi" class="actions"><?php if(has_perm('master_org_manage')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_customer'=>$c['id']])) ?>">Edit</a><?= action_form('customer_delete',['id'=>$c['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3><?= $projectEdit ? 'Edit Project' : 'Master Project' ?></h3></div>
    <?php if(has_perm('master_org_manage')): ?>
    <?= post_form_open('project_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($projectEdit['id'] ?? '') ?>">
      <div class="field"><label>Kode Project</label><input class="input" name="code" value="<?= h($projectEdit['code'] ?? '') ?>" required></div>
      <div class="field"><label>Nama Project</label><input class="input" name="name" value="<?= h($projectEdit['name'] ?? '') ?>" required></div>
      <div class="field"><label>Customer</label><select class="select" name="customer_id"><option value="">- Tidak ada -</option><?php foreach($customers as $c): ?><option value="<?= h($c['id']) ?>" <?= (($projectEdit['customer_id'] ?? '')==$c['id'])?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Status</label><select class="select" name="status"><?php foreach(['active','hold','closed'] as $st): ?><option <?= (($projectEdit['status'] ?? 'active')===$st)?'selected':'' ?>><?= h($st) ?></option><?php endforeach; ?></select></div>
      <button class="btn btn-primary"><?= $projectEdit ? 'Update Project' : 'Tambah Project' ?></button>
      <?php if($projectEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableProject"><thead><tr><th>Project</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($projects as $p): ?><tr data-search-row data-search="<?= h(implode(' ', [$p['code'], $p['name'], $p['customer_name'], $p['status']])) ?>"><td data-label="Project"><strong><?= h($p['code'].' - '.$p['name']) ?></strong><br><span class="small muted"><?= h(($p['customer_name'] ?: '-') . ' / ' . $p['status']) ?></span></td><td data-label="Aksi" class="actions"><?php if(has_perm('master_org_manage')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_project'=>$p['id']])) ?>">Edit</a><?= action_form('project_delete',['id'=>$p['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3><?= $workOrderEdit ? 'Edit Work Order' : 'Master Work Order' ?></h3></div>
    <?php if(has_perm('master_org_manage')): ?>
    <?= post_form_open('work_order_save','settings') ?>
      <input type="hidden" name="id" value="<?= h($workOrderEdit['id'] ?? '') ?>">
      <div class="field"><label>No WO</label><input class="input" name="wo_no" value="<?= h($workOrderEdit['wo_no'] ?? '') ?>" required></div>
      <div class="field"><label>Project</label><select class="select" name="project_id"><option value="">- Tidak ada -</option><?php foreach($projects as $p): ?><option value="<?= h($p['id']) ?>" <?= (($workOrderEdit['project_id'] ?? '')==$p['id'])?'selected':'' ?>><?= h($p['code'].' - '.$p['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Customer</label><select class="select" name="customer_id"><option value="">- Tidak ada -</option><?php foreach($customers as $c): ?><option value="<?= h($c['id']) ?>" <?= (($workOrderEdit['customer_id'] ?? '')==$c['id'])?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Department</label><select class="select" name="department_id"><option value="">- Tidak ada -</option><?php foreach($departments as $d): ?><option value="<?= h($d['id']) ?>" <?= (($workOrderEdit['department_id'] ?? '')==$d['id'])?'selected':'' ?>><?= h($d['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-grid">
        <div class="field"><label>Status</label><select class="select" name="status"><?php foreach(['open','progress','closed'] as $st): ?><option <?= (($workOrderEdit['status'] ?? 'open')===$st)?'selected':'' ?>><?= h($st) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Due Date</label><input class="input" type="date" name="due_date" value="<?= h($workOrderEdit['due_date'] ?? '') ?>"></div>
      </div>
      <div class="field"><label>Deskripsi</label><textarea class="textarea" name="description"><?= h($workOrderEdit['description'] ?? '') ?></textarea></div>
      <button class="btn btn-primary"><?= $workOrderEdit ? 'Update WO' : 'Tambah WO' ?></button>
      <?php if($workOrderEdit): ?><a class="btn btn-outline" href="<?= h(url('settings')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php endif; ?>
    <div class="table-wrap" style="margin-top:12px"><table class="table" id="tableWorkOrder"><thead><tr><th>WO</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($workOrders as $wo): ?><tr data-search-row data-search="<?= h(implode(' ', [$wo['wo_no'], $wo['project_code'], $wo['customer_name'], $wo['department_name'], $wo['status']])) ?>"><td data-label="WO"><strong><?= h($wo['wo_no']) ?></strong><br><span class="small muted"><?= h(trim(($wo['project_code'] ?: '-') . ' / ' . ($wo['department_name'] ?: '-') . ' / ' . $wo['status'])) ?></span></td><td data-label="Aksi" class="actions"><?php if(has_perm('master_org_manage')):?><a class="btn btn-sm" href="<?= h(url('settings',['edit_work_order'=>$wo['id']])) ?>">Edit</a><?= action_form('work_order_delete',['id'=>$wo['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Ganti Password Saya</h3></div>
  <?= post_form_open('password_change','settings') ?>
    <div class="form-grid">
      <div class="field"><label>Password Baru</label><input class="input" type="password" name="new_password" placeholder="Minimal 6 karakter" required></div>
      <div class="field"><label>&nbsp;</label><button class="btn btn-primary">Ganti Password</button></div>
    </div>
  </form>
</div>
