<?php
require_perm('page_cycle');
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$categories = all_rows("SELECT id, name FROM categories ORDER BY name");
$users = all_rows("SELECT id, name FROM users WHERE is_active=1 ORDER BY name");
$plans = all_rows("SELECT cp.*, l.code location_code, w.name warehouse_name, c.name category_name, au.name assigned_name, cu.name created_name FROM cycle_count_plans cp LEFT JOIN locations l ON l.id=cp.location_id LEFT JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN categories c ON c.id=cp.category_id LEFT JOIN users au ON au.id=cp.assigned_to LEFT JOIN users cu ON cu.id=cp.created_by ORDER BY FIELD(cp.status,'open','closed','cancelled'), cp.due_date IS NULL, cp.due_date, cp.id DESC LIMIT 120");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Jadwal Cycle Count</h3><span class="small muted">Opname bertahap per lokasi/kategori</span></div>
    <?php if(has_perm('cycle_manage')): ?>
    <?= post_form_open('cycle_save','cycle') ?>
      <div class="form-grid">
        <div class="field"><label>No Plan</label><input class="input" name="plan_no" placeholder="Auto jika kosong"></div>
        <div class="field"><label>Lokasi</label><select class="select" name="location_id"><option value="">Semua lokasi</option><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Kategori</label><select class="select" name="category_id"><option value="">Semua kategori</option><?php foreach($categories as $c): ?><option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Assign Ke</label><select class="select" name="assigned_to"><option value="">- Belum assign -</option><?php foreach($users as $u): ?><option value="<?= h($u['id']) ?>"><?= h($u['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Due Date</label><input class="input" type="date" name="due_date"></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Buat Jadwal</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat jadwal cycle count.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Alur</h3></div>
    <p class="muted">Buat plan, petugas melakukan opname di menu Stock Opname QR sesuai lokasi/kategori, lalu plan ditutup setelah selisih diselesaikan di approval.</p>
    <a class="btn btn-outline" href="<?= h(url('opname')) ?>">Buka Stock Opname</a>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Daftar Cycle Count</h3><span class="small muted"><span data-search-count="tableCycle"><?= h(count($plans)) ?></span> plan</span><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'cycle'])) ?>">Export</a><?php endif;?></div>
  <div class="toolbar"><input class="input" id="searchCycle" data-search-input="tableCycle" placeholder="Cari plan, lokasi, kategori, user, status..."></div>
  <div class="table-wrap"><table class="table" id="tableCycle"><thead><tr><th>Plan</th><th>Scope</th><th>Assign</th><th>Due</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($plans as $p): ?><tr data-search-row data-search="<?= h(implode(' ', [$p['plan_no'], $p['warehouse_name'], $p['location_code'], $p['category_name'], $p['assigned_name'], $p['status'], $p['due_date'], $p['note']])) ?>">
      <td data-label="Plan"><strong><?= h($p['plan_no']) ?></strong><br><span class="small muted"><?= h($p['created_at']) ?></span></td>
      <td data-label="Scope"><?= h(($p['warehouse_name'] ? $p['warehouse_name'].' / '.$p['location_code'] : 'Semua lokasi')) ?><br><span class="small muted"><?= h($p['category_name'] ?: 'Semua kategori') ?></span></td>
      <td data-label="Assign"><?= h($p['assigned_name'] ?: '-') ?></td>
      <td data-label="Due"><?= h($p['due_date'] ?: '-') ?></td>
      <td data-label="Status"><?= status_badge($p['status']) ?></td>
      <td data-label="Catatan"><?= h($p['note']) ?></td>
      <td data-label="Aksi" class="actions">
        <?php if(has_perm('cycle_manage') && $p['status']==='open'): ?>
          <?= action_form('cycle_status',['id'=>$p['id'],'status'=>'closed'],'Close','btn btn-sm btn-primary') ?>
          <?= action_form('cycle_status',['id'=>$p['id'],'status'=>'cancelled'],'Batalkan','btn btn-sm btn-warning') ?>
        <?php endif; ?>
      </td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada cycle count sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
