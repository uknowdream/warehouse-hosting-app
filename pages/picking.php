<?php
require_perm('page_picking');
$printId = isset($_GET['print']) ? (int)$_GET['print'] : 0;
$items = all_rows("SELECT i.id, i.sku, i.name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id ORDER BY i.sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$departments = all_rows("SELECT id, name FROM departments WHERE is_active=1 ORDER BY name");
$costCenters = all_rows("SELECT id, code, name FROM cost_centers WHERE is_active=1 ORDER BY code");
$customers = all_rows("SELECT id, name FROM customers ORDER BY name");
$projects = all_rows("SELECT id, code, name FROM projects ORDER BY code");
$workOrders = all_rows("SELECT id, wo_no FROM work_orders WHERE status <> 'closed' ORDER BY wo_no");
$slips = all_rows("SELECT ps.*, i.sku, i.name item_name, i.unit, l.code location_code, w.name warehouse_name, d.name department_name, cc.code cost_code, c.name customer_name, p.code project_code, wo.wo_no, rq.name requester_name, pk.name picker_name, isr.name issuer_name FROM picking_slips ps JOIN items i ON i.id=ps.item_id JOIN locations l ON l.id=ps.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN departments d ON d.id=ps.department_id LEFT JOIN cost_centers cc ON cc.id=ps.cost_center_id LEFT JOIN customers c ON c.id=ps.customer_id LEFT JOIN projects p ON p.id=ps.project_id LEFT JOIN work_orders wo ON wo.id=ps.work_order_id LEFT JOIN users rq ON rq.id=ps.requested_by LEFT JOIN users pk ON pk.id=ps.picked_by LEFT JOIN users isr ON isr.id=ps.issued_by ORDER BY FIELD(ps.status,'requested','picked','issued','cancelled'), ps.id DESC LIMIT 120");
$print = $printId ? one("SELECT ps.*, i.sku, i.name item_name, i.unit, l.code location_code, w.name warehouse_name, d.name department_name, cc.code cost_code, c.name customer_name, p.code project_code, wo.wo_no, rq.name requester_name, pk.name picker_name, isr.name issuer_name FROM picking_slips ps JOIN items i ON i.id=ps.item_id JOIN locations l ON l.id=ps.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN departments d ON d.id=ps.department_id LEFT JOIN cost_centers cc ON cc.id=ps.cost_center_id LEFT JOIN customers c ON c.id=ps.customer_id LEFT JOIN projects p ON p.id=ps.project_id LEFT JOIN work_orders wo ON wo.id=ps.work_order_id LEFT JOIN users rq ON rq.id=ps.requested_by LEFT JOIN users pk ON pk.id=ps.picked_by LEFT JOIN users isr ON isr.id=ps.issued_by WHERE ps.id=?", [$printId]) : null;
?>
<?php if($print): ?>
<div class="card print-slip">
  <div class="section-title"><h3>Issue Slip <?= h($print['slip_no']) ?></h3><button class="btn btn-primary" onclick="window.print()">Print</button></div>
  <div class="grid grid-3">
    <div><strong>Barang</strong><br><?= h($print['sku'].' - '.$print['item_name']) ?></div>
    <div><strong>Qty</strong><br><?= h($print['qty'].' '.$print['unit']) ?></div>
    <div><strong>Status</strong><br><?= status_badge($print['status']) ?></div>
    <div><strong>Lokasi</strong><br><?= h($print['warehouse_name'].' / '.$print['location_code']) ?></div>
    <div><strong>Lot/Serial</strong><br><?= h(trim(($print['lot_no'] ?: '-') . ' / ' . ($print['serial_no'] ?: '-'))) ?></div>
    <div><strong>WO/Project</strong><br><?= h(trim(($print['wo_no'] ?: '-') . ' / ' . ($print['project_code'] ?: '-'))) ?></div>
    <div><strong>Department</strong><br><?= h($print['department_name'] ?: '-') ?></div>
    <div><strong>Cost Center</strong><br><?= h($print['cost_code'] ?: '-') ?></div>
    <div><strong>Customer</strong><br><?= h($print['customer_name'] ?: '-') ?></div>
  </div>
  <hr>
  <p><strong>Catatan:</strong> <?= h($print['note']) ?></p>
  <div class="grid grid-3">
    <div><br><br><hr>Requester<br><?= h($print['requester_name'] ?: '-') ?></div>
    <div><br><br><hr>Picker<br><?= h($print['picker_name'] ?: '-') ?></div>
    <div><br><br><hr>Penerima/Issuer<br><?= h($print['issuer_name'] ?: '-') ?></div>
  </div>
</div>
<?php endif; ?>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Buat Picking Slip</h3><span class="small muted">Requested -> Picked -> Issued</span></div>
    <?php if(has_perm('picking_manage')): ?>
    <?= post_form_open('picking_save','picking') ?>
      <div class="form-grid">
        <div class="field"><label>No Slip</label><input class="input" name="slip_no" placeholder="Auto jika kosong"></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name'].' | Av '.$i['available_qty'].' '.$i['unit']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi Ambil</label><select class="select" name="location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty</label><input class="input" type="number" step="0.01" name="qty" required></div>
        <div class="field"><label>Lot</label><input class="input" name="lot_no"></div>
        <div class="field"><label>Serial</label><input class="input" name="serial_no"></div>
        <div class="field"><label>Department</label><select class="select" name="department_id"><option value="">- Tidak ada -</option><?php foreach($departments as $d): ?><option value="<?= h($d['id']) ?>"><?= h($d['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Cost Center</label><select class="select" name="cost_center_id"><option value="">- Tidak ada -</option><?php foreach($costCenters as $cc): ?><option value="<?= h($cc['id']) ?>"><?= h($cc['code'].' - '.$cc['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Customer</label><select class="select" name="customer_id"><option value="">- Tidak ada -</option><?php foreach($customers as $c): ?><option value="<?= h($c['id']) ?>"><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Project</label><select class="select" name="project_id"><option value="">- Tidak ada -</option><?php foreach($projects as $p): ?><option value="<?= h($p['id']) ?>"><?= h($p['code'].' - '.$p['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Work Order</label><select class="select" name="work_order_id"><option value="">- Tidak ada -</option><?php foreach($workOrders as $wo): ?><option value="<?= h($wo['id']) ?>"><?= h($wo['wo_no']) ?></option><?php endforeach; ?></select></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Buat Slip</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat picking slip.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title"><h3>Kontrol Proses</h3></div>
    <p class="muted">Tahap <strong>Picked</strong> memindahkan stock available ke reserved. Tahap <strong>Issued</strong> mengeluarkan stock reserved dan membuat transaksi keluar completed.</p>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Daftar Picking Slip</h3><span class="small muted"><span data-search-count="tablePicking"><?= h(count($slips)) ?></span> slip</span><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'picking'])) ?>">Export</a><?php endif;?></div>
  <div class="toolbar"><input class="input" id="searchPicking" data-search-input="tablePicking" placeholder="Cari slip, barang, WO, customer, status..."></div>
  <div class="table-wrap"><table class="table" id="tablePicking"><thead><tr><th>Slip</th><th>Barang</th><th>Lokasi</th><th>Tujuan</th><th>Qty</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($slips as $s): ?><tr data-search-row data-search="<?= h(implode(' ', [$s['slip_no'], $s['sku'], $s['item_name'], $s['warehouse_name'], $s['location_code'], $s['department_name'], $s['cost_code'], $s['customer_name'], $s['project_code'], $s['wo_no'], $s['status'], $s['lot_no'], $s['serial_no'], $s['note']])) ?>">
      <td data-label="Slip"><strong><?= h($s['slip_no']) ?></strong><br><span class="small muted"><?= h($s['created_at']) ?></span></td>
      <td data-label="Barang"><?= h($s['sku'].' - '.$s['item_name']) ?><br><span class="small muted"><?= h(trim(($s['lot_no'] ?: '-') . ' / ' . ($s['serial_no'] ?: '-'))) ?></span></td>
      <td data-label="Lokasi"><?= h($s['warehouse_name'].' / '.$s['location_code']) ?></td>
      <td data-label="Tujuan"><?= h(trim(($s['department_name'] ?: '-') . ' / ' . ($s['wo_no'] ?: '-') . ' / ' . ($s['customer_name'] ?: '-'))) ?></td>
      <td data-label="Qty"><?= h($s['qty'].' '.$s['unit']) ?></td>
      <td data-label="Status"><?= status_badge($s['status']) ?></td>
      <td data-label="Aksi" class="actions">
        <a class="btn btn-sm" href="<?= h(url('picking',['print'=>$s['id']])) ?>">Print</a>
        <?php if(has_perm('picking_manage') && $s['status']==='requested'): ?><?= action_form('picking_pick',['id'=>$s['id']],'Picked','btn btn-sm btn-primary') ?><?= action_form('picking_cancel',['id'=>$s['id']],'Batalkan','btn btn-sm btn-warning') ?><?php endif; ?>
        <?php if(has_perm('picking_manage') && $s['status']==='picked'): ?><?= action_form('picking_issue',['id'=>$s['id']],'Issued','btn btn-sm btn-primary') ?><?= action_form('picking_cancel',['id'=>$s['id']],'Batalkan','btn btn-sm btn-warning') ?><?php endif; ?>
      </td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada picking slip sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
