<?php
require_perm('page_quality');
$items = all_rows("SELECT id, sku, name FROM items ORDER BY sku");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$rows = all_rows("SELECT qr.*, i.sku, i.name item_name, l.code, w.name warehouse_name, u.name user_name FROM quality_records qr JOIN items i ON i.id=qr.item_id JOIN locations l ON l.id=qr.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN users u ON u.id=qr.created_by ORDER BY qr.id DESC LIMIT 50");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Retur / Karantina / Damaged</h3></div>
    <?php if(has_perm('quality_write')): ?>
    <?= post_form_open('quality_save','quality') ?>
      <div class="form-grid">
        <div class="field"><label>Jenis Aksi</label><select class="select" name="type"><option value="quarantine">Karantina</option><option value="damaged">Damaged</option><option value="return_supplier">Retur Supplier Keluar</option><option value="return_customer">Retur Customer Masuk</option><option value="disposal">Disposal</option></select></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi</label><select class="select" name="location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty</label><input class="input" type="number" step="0.01" name="qty" required></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Simpan</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat riwayat quality.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Riwayat Quality</h3><span class="small muted"><span data-search-count="tableQuality"><?= h(count($rows)) ?></span> data</span></div>
    <div class="toolbar"><input class="input" id="searchQuality" data-search-input="tableQuality" placeholder="Cari quality, barang, lokasi, tipe, user..."></div>
    <div class="table-wrap"><table class="table" id="tableQuality"><thead><tr><th>Tgl</th><th>Tipe</th><th>Barang</th><th>Lokasi</th><th>Qty</th><th>User</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $r): ?><tr data-search-row data-search="<?= h(implode(' ', [$r['created_at'], $r['type'], $r['sku'], $r['item_name'], $r['note'], $r['warehouse_name'], $r['code'], $r['qty'], $r['user_name']])) ?>">
      <td data-label="Tgl"><?= h($r['created_at']) ?></td>
      <td data-label="Tipe"><?= h($r['type']) ?></td>
      <td data-label="Barang"><?= h($r['sku'].' - '.$r['item_name']) ?><br><span class="small muted"><?= h($r['note']) ?></span></td>
      <td data-label="Lokasi"><?= h($r['warehouse_name'].'/'.$r['code']) ?></td>
      <td data-label="Qty"><?= h($r['qty']) ?></td>
      <td data-label="User"><?= h($r['user_name']) ?></td>
      <td data-label="Aksi" class="actions"><?php if(has_perm('quality_delete')): ?><?= action_form('quality_delete',['id'=>$r['id']],'Hapus/Reverse','btn btn-sm btn-danger') ?><?php endif; ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada data quality sesuai pencarian.</td></tr>
  </tbody></table></div></div>
</div>
