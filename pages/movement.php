<?php
require_perm('page_movement');
$items = all_rows("SELECT i.id, i.sku, i.name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id ORDER BY i.sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$departments = all_rows("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name");
$costCenters = all_rows("SELECT id, code, name FROM cost_centers WHERE is_active=1 ORDER BY code");
$customers = all_rows("SELECT id, name FROM customers ORDER BY name");
$projects = all_rows("SELECT id, code, name FROM projects ORDER BY code");
$workOrders = all_rows("SELECT id, wo_no FROM work_orders WHERE status <> 'closed' ORDER BY wo_no");
$movements = all_rows("SELECT sm.*, i.sku, i.name item_name, fl.code from_code, fw.name from_wh, tl.code to_code, tw.name to_wh, u.name user_name FROM stock_movements sm JOIN items i ON i.id=sm.item_id LEFT JOIN locations fl ON fl.id=sm.from_location_id LEFT JOIN warehouses fw ON fw.id=fl.warehouse_id LEFT JOIN locations tl ON tl.id=sm.to_location_id LEFT JOIN warehouses tw ON tw.id=tl.warehouse_id LEFT JOIN users u ON u.id=sm.created_by ORDER BY sm.id DESC LIMIT 50");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Input Transaksi Stock</h3><span class="small muted">Keluar, reservasi, koreksi, transfer perlu approval</span></div>
    <?php if(has_perm('stock_movement')): ?>
    <?= post_form_open('movement_save','movement') ?>
      <div class="form-grid">
        <div class="field"><label>Tipe</label><select class="select" name="movement_type"><option value="in">Stock Masuk</option><option value="out">Stock Keluar</option><option value="reserve">Reservasi</option><option value="adjust">Koreksi ke Qty Baru</option><option value="transfer">Transfer Lokasi</option></select></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name'].' ('.$i['available_qty'].' '.$i['unit'].')') ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi Asal</label><select class="select" name="from_location_id"><option value="">- Tidak perlu -</option><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi Tujuan</label><select class="select" name="to_location_id"><option value="">- Tidak perlu -</option><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty</label><input class="input" type="number" step="0.01" name="qty" required></div>
        <div class="field"><label>Department</label><select class="select" name="department_id"><option value="">- Manual -</option><?php foreach($departments as $d): ?><option value="<?= h($d['id']) ?>"><?= h($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Cost Center</label><select class="select" name="cost_center_id"><option value="">- Manual -</option><?php foreach($costCenters as $cc): ?><option value="<?= h($cc['id']) ?>"><?= h($cc['code'].' - '.$cc['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Customer</label><select class="select" name="customer_id"><option value="">- Tidak ada -</option><?php foreach($customers as $c): ?><option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Project</label><select class="select" name="project_id"><option value="">- Tidak ada -</option><?php foreach($projects as $p): ?><option value="<?= h($p['id']) ?>"><?= h($p['code'].' - '.$p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Work Order</label><select class="select" name="work_order_id"><option value="">- Tidak ada -</option><?php foreach($workOrders as $wo): ?><option value="<?= h($wo['id']) ?>"><?= h($wo['wo_no']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lot Number</label><input class="input" name="lot_no" placeholder="Opsional"></div>
        <div class="field"><label>Serial Number</label><input class="input" name="serial_no" placeholder="Opsional"></div>
        <div class="field"><label>No Referensi</label><input class="input" name="reference_no" placeholder="PO / WO / SJ"></div>
      </div>
      <div class="form-grid">
        <div class="field"><label>Department Manual</label><input class="input" name="department" placeholder="Jika belum ada master"></div>
        <div class="field"><label>Cost Center Manual</label><input class="input" name="cost_center" placeholder="Jika belum ada master"></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Simpan Transaksi</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat riwayat transaksi.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Riwayat Transaksi</h3><span class="small muted"><span data-search-count="tableMovement"><?= h(count($movements)) ?></span> transaksi</span><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'movements'])) ?>">Export</a><?php endif;?></div>
    <div class="toolbar"><input class="input" id="searchMovement" data-search-input="tableMovement" placeholder="Cari transaksi, barang, lokasi, status, referensi..."></div>
    <div class="table-wrap"><table class="table" id="tableMovement"><thead><tr><th>Tgl</th><th>Tipe</th><th>Barang</th><th>Lokasi</th><th>Qty</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($movements as $m): ?>
      <tr data-search-row data-search="<?= h(implode(' ', [$m['created_at'], $m['movement_type'], $m['reference_no'], $m['sku'], $m['item_name'], $m['from_wh'], $m['from_code'], $m['to_wh'], $m['to_code'], $m['qty'], $m['status'], $m['user_name'], $m['department'], $m['cost_center'], $m['note']])) ?>">
        <td data-label="Tgl"><?= h($m['created_at']) ?></td>
        <td data-label="Tipe"><?= h($m['movement_type']) ?><br><span class="small muted"><?= h(trim(($m['reference_no'] ?? '') . ' ' . ($m['lot_no'] ?? '') . ' ' . ($m['serial_no'] ?? ''))) ?></span></td>
        <td data-label="Barang"><?= h($m['sku'].' - '.$m['item_name']) ?></td>
        <td data-label="Lokasi"><?= h(($m['from_wh'] ? $m['from_wh'].'/'.$m['from_code'] : '-') . ' -> ' . ($m['to_wh'] ? $m['to_wh'].'/'.$m['to_code'] : '-')) ?></td>
        <td data-label="Qty"><?= h($m['qty']) ?></td>
        <td data-label="Status"><?= status_badge($m['status']) ?></td>
        <td data-label="Aksi" class="actions">
          <?php if($m['status']==='pending' && has_perm('movement_cancel')): ?><?= action_form('movement_cancel',['id'=>$m['id']],'Batalkan','btn btn-sm btn-warning') ?><?php endif; ?>
          <?php if(in_array($m['status'], ['rejected','cancelled'], true) && has_perm('movement_delete')): ?><?= action_form('movement_delete',['id'=>$m['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="7">Tidak ada transaksi sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>
</div>
