<?php
require_perm('page_lots');
$items = all_rows("SELECT id, sku, name FROM items ORDER BY sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$rows = all_rows("SELECT il.*, i.sku, i.name item_name, l.code location_code, w.name warehouse_name FROM item_lots il JOIN items i ON i.id=il.item_id JOIN locations l ON l.id=il.location_id JOIN warehouses w ON w.id=l.warehouse_id ORDER BY il.updated_at DESC, il.id DESC LIMIT 300");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Input Lot / Serial</h3><span class="small muted">Menjaga saldo detail per batch/unit</span></div>
    <?php if(has_perm('lot_manage')): ?>
    <?= post_form_open('lot_save','lots') ?>
      <div class="form-grid">
        <div class="field"><label>Mode</label><select class="select" name="mode"><option value="add">Tambah Qty</option><option value="set">Set Qty Lot</option></select></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi</label><select class="select" name="location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Status Stock</label><select class="select" name="stock_status"><option value="available">available</option><option value="reserved">reserved</option><option value="quarantine">quarantine</option><option value="damaged">damaged</option></select></div>
        <div class="field"><label>Lot Number</label><input class="input" name="lot_no" placeholder="LOT-001"></div>
        <div class="field"><label>Serial Number</label><input class="input" name="serial_no" placeholder="SN-001"></div>
        <div class="field"><label>Qty</label><input class="input" type="number" step="0.01" name="qty" required></div>
        <div class="field"><label>Produksi</label><input class="input" type="date" name="production_date"></div>
        <div class="field"><label>Expired</label><input class="input" type="date" name="expired_date"></div>
      </div>
      <button class="btn btn-primary">Simpan Lot/Serial</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat lot dan serial.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Ringkasan</h3></div>
    <div class="grid grid-3">
      <div class="stat"><div><h3>Total Lot</h3><strong><?= h(count($rows)) ?></strong><p class="small muted">baris aktif</p></div><div class="icon">Lot</div></div>
      <div class="stat"><div><h3>Qty Available</h3><strong><?= h(array_sum(array_map(fn($r) => $r['stock_status']==='available' ? (float)$r['qty'] : 0, $rows))) ?></strong><p class="small muted">detail lot</p></div><div class="icon">Av</div></div>
      <div class="stat"><div><h3>Serial</h3><strong><?= h(count(array_filter($rows, fn($r) => $r['serial_no'] !== ''))) ?></strong><p class="small muted">unit terlacak</p></div><div class="icon">SN</div></div>
    </div>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Saldo Lot & Serial</h3><span class="small muted"><span data-search-count="tableLots"><?= h(count($rows)) ?></span> data</span><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'lots'])) ?>">Export</a><?php endif;?></div>
  <div class="toolbar"><input class="input" id="searchLots" data-search-input="tableLots" placeholder="Cari SKU, lot, serial, lokasi, status..."></div>
  <div class="table-wrap"><table class="table" id="tableLots"><thead><tr><th>Barang</th><th>Lokasi</th><th>Status</th><th>Lot</th><th>Serial</th><th>Qty</th><th>Expired</th><th>Sumber</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr data-search-row data-search="<?= h(implode(' ', [$r['sku'], $r['item_name'], $r['warehouse_name'], $r['location_code'], $r['stock_status'], $r['lot_no'], $r['serial_no'], $r['expired_date'], $r['source_table'], $r['source_id']])) ?>">
      <td data-label="Barang"><strong><?= h($r['sku']) ?></strong><br><span class="small muted"><?= h($r['item_name']) ?></span></td>
      <td data-label="Lokasi"><?= h($r['warehouse_name'].' / '.$r['location_code']) ?></td>
      <td data-label="Status"><?= status_badge($r['stock_status']) ?></td>
      <td data-label="Lot"><?= h($r['lot_no'] ?: '-') ?></td>
      <td data-label="Serial"><?= h($r['serial_no'] ?: '-') ?></td>
      <td data-label="Qty"><?= h($r['qty']) ?></td>
      <td data-label="Expired"><?= h($r['expired_date'] ?: '-') ?></td>
      <td data-label="Sumber"><?= h(trim(($r['source_table'] ?: '-') . ' #' . ($r['source_id'] ?: '-'))) ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="8">Tidak ada lot/serial sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
