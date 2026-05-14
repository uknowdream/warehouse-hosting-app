<?php
require_perm('page_master');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = $editId ? one("SELECT * FROM items WHERE id=?", [$editId]) : null;
$items = all_rows("SELECT i.*, c.name category_name, s.name supplier_name, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty FROM items i LEFT JOIN categories c ON c.id=i.category_id LEFT JOIN suppliers s ON s.id=i.supplier_id LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id ORDER BY i.id DESC");
$categories = all_rows("SELECT * FROM categories ORDER BY name");
$suppliers = all_rows("SELECT * FROM suppliers ORDER BY name");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
?>
<div class="card">
  <div class="section-title"><h3><?= $edit ? 'Edit Barang' : 'Tambah Barang' ?></h3><span class="small muted">Master item, batch, expired, minimum stock</span></div>
  <?php if (has_perm('item_write')): ?>
  <?= post_form_open('item_save','master') ?>
    <input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>">
    <div class="form-grid-3">
      <div class="field"><label>SKU / Kode Barang</label><input class="input" name="sku" value="<?= h($edit['sku'] ?? '') ?>" required></div>
      <div class="field"><label>Nama Barang</label><input class="input" name="name" value="<?= h($edit['name'] ?? '') ?>" required></div>
      <div class="field"><label>Kategori</label><select class="select" name="category_id"><option value="">- Pilih -</option><?php foreach($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (($edit['category_id'] ?? '')==$c['id'])?'selected':'' ?>><?= h($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Supplier</label><select class="select" name="supplier_id"><option value="">- Pilih -</option><?php foreach($suppliers as $s): ?><option value="<?= $s['id'] ?>" <?= (($edit['supplier_id'] ?? '')==$s['id'])?'selected':'' ?>><?= h($s['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Satuan</label><input class="input" name="unit" value="<?= h($edit['unit'] ?? 'PCS') ?>"></div>
      <div class="field"><label>Minimum Stock</label><input class="input" type="number" step="0.01" name="min_stock" value="<?= h($edit['min_stock'] ?? 0) ?>"></div>
      <div class="field"><label>Safety Stock</label><input class="input" type="number" step="0.01" name="safety_stock" value="<?= h($edit['safety_stock'] ?? 0) ?>"></div>
      <div class="field"><label>Reorder Point</label><input class="input" type="number" step="0.01" name="reorder_point" value="<?= h($edit['reorder_point'] ?? 0) ?>"></div>
      <div class="field"><label>Max Stock</label><input class="input" type="number" step="0.01" name="max_stock" value="<?= h($edit['max_stock'] ?? 0) ?>"></div>
      <div class="field"><label>Lead Time Default</label><input class="input" type="number" name="default_lead_time_days" value="<?= h($edit['default_lead_time_days'] ?? 0) ?>"></div>
      <div class="field"><label>Harga Satuan</label><input class="input" type="number" step="0.01" name="price" value="<?= h($edit['price'] ?? 0) ?>"></div>
      <div class="field"><label>Barcode</label><input class="input" name="barcode" value="<?= h($edit['barcode'] ?? '') ?>"></div>
      <div class="field"><label>Batch Number</label><input class="input" name="batch_no" value="<?= h($edit['batch_no'] ?? '') ?>"></div>
      <div class="field"><label>Tanggal Produksi</label><input class="input" type="date" name="production_date" value="<?= h($edit['production_date'] ?? '') ?>"></div>
      <div class="field"><label>Expired Date</label><input class="input" type="date" name="expired_date" value="<?= h($edit['expired_date'] ?? '') ?>"></div>
      <div class="field"><label>Metode Keluar</label><select class="select" name="issue_method"><option <?= (($edit['issue_method'] ?? '')==='FIFO')?'selected':'' ?>>FIFO</option><option <?= (($edit['issue_method'] ?? '')==='FEFO')?'selected':'' ?>>FEFO</option></select></div>
      <?php if (!$edit): ?>
      <div class="field"><label>Lokasi Awal</label><select class="select" name="initial_location_id"><option value="">Tidak ada stock awal</option><?php foreach($locations as $l): ?><option value="<?= $l['id'] ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>Qty Awal</label><input class="input" type="number" step="0.01" name="initial_qty" value="0"></div>
      <?php endif; ?>
    </div>
    <button class="btn btn-primary"><?= $edit ? 'Update Barang' : 'Simpan Barang' ?></button>
    <?php if ($edit): ?><a class="btn" href="<?= h(url('master')) ?>">Batal Edit</a><?php endif; ?>
  </form>
  <?php else: ?><p class="muted">Role Anda hanya dapat melihat master barang.</p><?php endif; ?>
</div>
<div class="card">
  <div class="toolbar">
    <input class="input" id="searchMaster" data-search-input="tableMaster" placeholder="Cari SKU, nama, kategori, supplier, batch...">
    <span class="small muted"><span data-search-count="tableMaster"><?= h(count($items)) ?></span> barang</span>
    <a class="btn btn-outline" href="<?= h(url('qr')) ?>">Generate QR</a>
  </div>
  <div class="table-wrap"><table class="table" id="tableMaster"><thead><tr><th>SKU</th><th>Nama</th><th>Kategori</th><th>Supplier</th><th>Stock</th><th>Min</th><th>Batch/Exp</th><th>Harga</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($items as $i): ?>
    <tr data-search-row data-search="<?= h(implode(' ', [$i['sku'], $i['barcode'], $i['name'], $i['category_name'], $i['supplier_name'], $i['batch_no'], $i['expired_date'], $i['available_qty'], $i['unit']])) ?>">
      <td data-label="SKU"><strong><?= h($i['sku']) ?></strong><br><span class="small muted"><?= h($i['barcode']) ?></span></td>
      <td data-label="Nama"><?= h($i['name']) ?></td>
      <td data-label="Kategori"><?= h($i['category_name']) ?></td>
      <td data-label="Supplier"><?= h($i['supplier_name']) ?></td>
      <td data-label="Stock"><?= h($i['available_qty']) ?> <?= h($i['unit']) ?><br><?= ((float)$i['available_qty'] <= (float)$i['min_stock']) ? status_badge('Menipis') : status_badge('Aman') ?></td>
      <td data-label="Min"><?= h($i['min_stock']) ?></td>
      <td data-label="Batch/Exp"><?= h($i['batch_no']) ?><br><span class="small muted"><?= h($i['expired_date']) ?></span></td>
      <td data-label="Harga"><?= rupiah($i['price']) ?></td>
      <td data-label="Aksi" class="actions">
        <?php if (has_perm('item_write')): ?><a class="btn btn-sm" href="<?= h(url('master',['edit'=>$i['id']])) ?>">Edit</a><?php endif; ?>
        <?php if (has_perm('page_qr')): ?><a class="btn btn-sm" href="<?= h(url('qr',['item'=>$i['id']])) ?>">QR</a><?php endif; ?>
        <?php if (has_perm('item_delete')): ?><?= action_form('item_delete',['id'=>$i['id']],'Hapus','btn btn-sm btn-danger inline-form') ?><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="9">Tidak ada barang sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
