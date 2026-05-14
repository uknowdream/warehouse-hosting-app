<?php
require_perm('page_opname');
$items = all_rows("SELECT i.id, i.sku, i.name, i.unit FROM items i ORDER BY i.sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$counts = all_rows("SELECT sc.*, i.sku, i.name item_name, l.code, w.name warehouse_name, u.name counted_name FROM stock_counts sc JOIN items i ON i.id=sc.item_id JOIN locations l ON l.id=sc.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN users u ON u.id=sc.counted_by ORDER BY sc.id DESC LIMIT 30");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Scanner QR Stock Opname</h3><span class="small muted">Kamera HP + input manual</span></div>
    <?php if(has_perm('stock_opname')): ?>
    <div class="scan-box">
      <div id="reader"></div>
      <div class="toolbar" style="justify-content:center;margin-top:12px">
        <button type="button" class="btn btn-primary" onclick="startScanner()">Mulai Kamera</button>
        <button type="button" class="btn" onclick="stopScanner()">Stop</button>
      </div>
      <div class="form-grid">
        <div class="field"><label>Input Manual SKU / Payload QR</label><input id="manual_scan" class="input" placeholder="BRG-0001"></div>
        <div class="field"><label>&nbsp;</label><button type="button" class="btn btn-dark full" onclick="manualScan()">Cari Barang</button></div>
      </div>
    </div>
    <hr>
    <?= post_form_open('opname_save','opname') ?>
      <div class="form-grid">
        <div class="field"><label>Barang</label><select id="op_item_id" class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= $i['id'] ?>" data-sku="<?= h($i['sku']) ?>"><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi</label><select id="op_location_id" class="select" name="location_id" required><?php foreach($locations as $l): ?><option value="<?= $l['id'] ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty Fisik</label><input id="op_physical_qty" class="input" type="number" step="0.01" name="physical_qty" required></div>
        <div class="field"><label>Catatan</label><input class="input" name="note" placeholder="Catatan opname"></div>
      </div>
      <button class="btn btn-primary full">Simpan Hasil Opname</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat riwayat opname.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Riwayat Opname</h3><span class="small muted"><span data-search-count="tableOpname"><?= h(count($counts)) ?></span> opname</span><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'opname'])) ?>">Export</a><?php endif;?></div>
    <div class="toolbar"><input class="input" id="searchOpname" data-search-input="tableOpname" placeholder="Cari opname, barang, lokasi, status, catatan..."></div>
    <div class="table-wrap"><table class="table" id="tableOpname"><thead><tr><th>Tgl</th><th>Barang</th><th>Lokasi</th><th>Sistem</th><th>Fisik</th><th>Selisih</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($counts as $c): ?>
      <tr data-search-row data-search="<?= h(implode(' ', [$c['created_at'], $c['sku'], $c['item_name'], $c['note'], $c['warehouse_name'], $c['code'], $c['system_qty'], $c['physical_qty'], $c['variance'], $c['status'], $c['counted_name']])) ?>"><td data-label="Tgl"><?= h($c['created_at']) ?></td><td data-label="Barang"><?= h($c['sku'].' - '.$c['item_name']) ?><br><span class="small muted"><?= h($c['note']) ?></span></td><td data-label="Lokasi"><?= h($c['warehouse_name'].' / '.$c['code']) ?></td><td data-label="Sistem"><?= h($c['system_qty']) ?></td><td data-label="Fisik"><?= h($c['physical_qty']) ?></td><td data-label="Selisih"><?= h($c['variance']) ?></td><td data-label="Status"><?= status_badge($c['status']) ?></td><td data-label="Aksi" class="actions"><?php if($c['status'] !== 'approved' && has_perm('opname_delete')): ?><?= action_form('opname_delete',['id'=>$c['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr>
      <?php endforeach; ?>
      <tr data-search-empty hidden><td colspan="8">Tidak ada opname sesuai pencarian.</td></tr>
    </tbody></table></div>
  </div>
</div>
