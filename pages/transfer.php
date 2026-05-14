<?php
require_perm('page_transfer');
$items = all_rows("SELECT i.id, i.sku, i.name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id ORDER BY i.sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$transfers = all_rows("SELECT sm.*, i.sku, i.name item_name, fl.code from_code, fw.name from_wh, tl.code to_code, tw.name to_wh, u.name user_name FROM stock_movements sm JOIN items i ON i.id=sm.item_id LEFT JOIN locations fl ON fl.id=sm.from_location_id LEFT JOIN warehouses fw ON fw.id=fl.warehouse_id LEFT JOIN locations tl ON tl.id=sm.to_location_id LEFT JOIN warehouses tw ON tw.id=tl.warehouse_id LEFT JOIN users u ON u.id=sm.created_by WHERE sm.movement_type='transfer' ORDER BY sm.id DESC LIMIT 50");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Transfer Gudang / Lokasi</h3><span class="small muted">Transfer masuk approval sebelum stock berpindah.</span></div>
    <?php if(has_perm('stock_movement')): ?>
    <?= post_form_open('movement_save','transfer') ?>
      <input type="hidden" name="movement_type" value="transfer">
      <div class="form-grid">
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name'].' ('.$i['available_qty'].' '.$i['unit'].')') ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty Transfer</label><input class="input" type="number" step="0.01" name="qty" required></div>
        <div class="field"><label>Lokasi Asal</label><select class="select" name="from_location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi Tujuan</label><select class="select" name="to_location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lot Number</label><input class="input" name="lot_no" placeholder="Opsional"></div>
        <div class="field"><label>Serial Number</label><input class="input" name="serial_no" placeholder="Opsional"></div>
        <div class="field"><label>Department</label><input class="input" name="department" placeholder="Warehouse"></div>
        <div class="field"><label>No Referensi</label><input class="input" name="reference_no" placeholder="TRF-001"></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Ajukan Transfer</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat transfer.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Riwayat Transfer</h3><span class="small muted"><span data-search-count="tableTransfer"><?= h(count($transfers)) ?></span> transfer</span></div>
    <div class="toolbar"><input class="input" id="searchTransfer" data-search-input="tableTransfer" placeholder="Cari transfer, barang, lokasi, status..."></div>
    <div class="table-wrap"><table class="table" id="tableTransfer"><thead><tr><th>Tgl</th><th>Barang</th><th>Lokasi</th><th>Qty</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($transfers as $m): ?><tr data-search-row data-search="<?= h(implode(' ', [$m['created_at'], $m['sku'], $m['item_name'], $m['from_wh'], $m['from_code'], $m['to_wh'], $m['to_code'], $m['qty'], $m['status'], $m['reference_no'], $m['department'], $m['note'], $m['user_name']])) ?>">
        <td data-label="Tgl"><?= h($m['created_at']) ?></td>
        <td data-label="Barang"><?= h($m['sku'].' - '.$m['item_name']) ?></td>
        <td data-label="Lokasi"><?= h(($m['from_wh'] ? $m['from_wh'].'/'.$m['from_code'] : '-') . ' -> ' . ($m['to_wh'] ? $m['to_wh'].'/'.$m['to_code'] : '-')) ?></td>
        <td data-label="Qty"><?= h($m['qty']) ?></td>
        <td data-label="Status"><?= status_badge($m['status']) ?></td>
        <td data-label="Aksi" class="actions"><?php if($m['status']==='pending' && has_perm('movement_cancel')): ?><?= action_form('movement_cancel',['id'=>$m['id']],'Batalkan','btn btn-sm btn-warning') ?><?php endif; ?></td>
      </tr><?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="6">Tidak ada transfer sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>
</div>
