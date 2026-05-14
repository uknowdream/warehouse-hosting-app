<?php
require_perm('page_purchase');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$items = all_rows("SELECT i.id, i.sku, i.name, i.unit, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty, i.min_stock FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id ORDER BY i.sku");
$suppliers = all_rows("SELECT id, name FROM suppliers ORDER BY name");
$edit = $editId ? one("SELECT * FROM purchase_requests WHERE id=?", [$editId]) : null;
$prs = all_rows("SELECT pr.*, i.sku, i.name item_name, s.name supplier_name, u.name requester FROM purchase_requests pr JOIN items i ON i.id=pr.item_id LEFT JOIN suppliers s ON s.id=pr.supplier_id LEFT JOIN users u ON u.id=pr.requested_by ORDER BY pr.id DESC LIMIT 50");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3><?= $edit ? 'Edit Purchase Request' : 'Buat Purchase Request' ?></h3><span class="small muted">PR pending masuk ke approval pembelian</span></div>
    <?php if(has_perm($edit ? 'purchase_update' : 'purchase_write')): ?>
    <?= post_form_open('purchase_save','purchase') ?>
      <input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>">
      <div class="form-grid">
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>" <?= (($edit['item_id'] ?? '')==$i['id'])?'selected':'' ?>><?= h($i['sku'].' - '.$i['name'].' | Stock '.$i['available_qty'].' | Min '.$i['min_stock']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Supplier Rekomendasi</label><select class="select" name="supplier_id"><option value="">- Belum ditentukan -</option><?php foreach($suppliers as $s): ?><option value="<?= h($s['id']) ?>" <?= (($edit['supplier_id'] ?? '')==$s['id'])?'selected':'' ?>><?= h($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty Diminta</label><input class="input" type="number" step="0.01" name="qty" value="<?= h($edit['qty'] ?? '') ?>" required></div>
        <div class="field"><label>Department</label><input class="input" name="department" value="<?= h($edit['department'] ?? '') ?>" placeholder="Produksi"></div>
        <div class="field"><label>Prioritas</label><select class="select" name="priority"><?php foreach(['Normal','Urgent','Critical'] as $priority): ?><option <?= (($edit['priority'] ?? 'Normal')===$priority)?'selected':'' ?>><?= h($priority) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Target Tanggal</label><input class="input" type="date" name="expected_date" value="<?= h($edit['expected_date'] ?? '') ?>"></div>
      </div>
      <div class="field"><label>Alasan</label><textarea class="textarea" name="reason"><?= h($edit['reason'] ?? '') ?></textarea></div>
      <button class="btn btn-primary"><?= $edit ? 'Update PR' : 'Kirim Approval PR' ?></button>
      <?php if($edit): ?><a class="btn btn-outline" href="<?= h(url('purchase')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php else: ?><p class="muted">Role Anda tidak memiliki izin input/update purchase request.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Daftar PR</h3><span class="small muted"><span data-search-count="tablePurchase"><?= h(count($prs)) ?></span> request</span></div>
    <div class="toolbar"><input class="input" id="searchPurchase" data-search-input="tablePurchase" placeholder="Cari PR, barang, department, prioritas, status..."></div>
    <div class="table-wrap"><table class="table" id="tablePurchase"><thead><tr><th>Tgl</th><th>Barang</th><th>Supplier</th><th>Qty</th><th>Dept</th><th>Prioritas</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($prs as $p): ?><tr data-search-row data-search="<?= h(implode(' ', [$p['created_at'], $p['sku'], $p['item_name'], $p['supplier_name'], $p['reason'], $p['qty'], $p['department'], $p['priority'], $p['expected_date'], $p['status'], $p['requester']])) ?>">
      <td data-label="Tgl"><?= h($p['created_at']) ?></td>
      <td data-label="Barang"><?= h($p['sku'].' - '.$p['item_name']) ?><br><span class="small muted"><?= h($p['reason']) ?></span></td>
      <td data-label="Supplier"><?= h($p['supplier_name'] ?: '-') ?><br><span class="small muted"><?= h($p['expected_date'] ?: '') ?></span></td>
      <td data-label="Qty"><?= h($p['qty']) ?></td>
      <td data-label="Dept"><?= h($p['department']) ?></td>
      <td data-label="Prioritas"><?= h($p['priority']) ?></td>
      <td data-label="Status"><?= status_badge($p['status']) ?></td>
      <td data-label="Aksi" class="actions">
        <?php if($p['status']==='pending' && has_perm('purchase_update')): ?><a class="btn btn-sm" href="<?= h(url('purchase',['edit'=>$p['id']])) ?>">Edit</a><?= action_form('purchase_cancel',['id'=>$p['id']],'Batalkan','btn btn-sm btn-warning') ?><?php endif; ?>
        <?php if(in_array($p['status'], ['pending','rejected','cancelled'], true) && has_perm('purchase_delete')): ?><?= action_form('purchase_delete',['id'=>$p['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?>
      </td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="8">Tidak ada purchase request sesuai pencarian.</td></tr>
  </tbody></table></div></div>
</div>
