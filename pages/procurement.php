<?php
require_perm('page_procurement');
$items = all_rows("SELECT id, sku, name, unit FROM items ORDER BY sku");
$suppliers = all_rows("SELECT id, name FROM suppliers ORDER BY name");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$prs = all_rows("SELECT pr.*, i.sku, i.name item_name, s.name supplier_name FROM purchase_requests pr JOIN items i ON i.id=pr.item_id LEFT JOIN suppliers s ON s.id=pr.supplier_id WHERE pr.status IN ('approved','ordered') ORDER BY pr.id DESC LIMIT 80");
$pos = all_rows("SELECT po.*, i.sku, i.name item_name, i.unit, s.name supplier_name, pr.id pr_no FROM purchase_orders po JOIN items i ON i.id=po.item_id JOIN suppliers s ON s.id=po.supplier_id LEFT JOIN purchase_requests pr ON pr.id=po.pr_id ORDER BY FIELD(po.status,'ordered','partial','received','closed'), po.id DESC LIMIT 80");
$receipts = all_rows("SELECT gr.*, po.po_no, i.sku, i.name item_name, s.name supplier_name, l.code location_code, w.name warehouse_name, u.name user_name FROM goods_receipts gr LEFT JOIN purchase_orders po ON po.id=gr.po_id JOIN items i ON i.id=gr.item_id LEFT JOIN suppliers s ON s.id=gr.supplier_id JOIN locations l ON l.id=gr.location_id JOIN warehouses w ON w.id=l.warehouse_id LEFT JOIN users u ON u.id=gr.created_by ORDER BY gr.id DESC LIMIT 80");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Buat Purchase Order</h3><span class="small muted">Dari PR approved atau input langsung</span></div>
    <?php if(has_perm('po_manage')): ?>
    <?= post_form_open('po_save','procurement') ?>
      <div class="form-grid">
        <div class="field"><label>No PO</label><input class="input" name="po_no" placeholder="Auto jika kosong"></div>
        <div class="field"><label>PR Approved</label><select class="select" name="pr_id"><option value="">- Tanpa PR -</option><?php foreach($prs as $pr): ?><option value="<?= h($pr['id']) ?>"><?= h('PR#'.$pr['id'].' / '.$pr['sku'].' / '.$pr['qty'].' / '.$pr['status']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Supplier</label><select class="select" name="supplier_id" required><option value="">- Pilih -</option><?php foreach($suppliers as $s): ?><option value="<?= h($s['id']) ?>"><?= h($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id" required><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty Order</label><input class="input" type="number" step="0.01" name="qty_ordered" required></div>
        <div class="field"><label>Harga</label><input class="input" type="number" step="0.01" name="unit_price"></div>
        <div class="field"><label>ETA</label><input class="input" type="date" name="expected_date"></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="notes"></textarea></div>
      <button class="btn btn-primary">Simpan PO</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat PO.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title"><h3>Goods Receipt</h3><span class="small muted">Penerimaan menambah stock dan lot</span></div>
    <?php if(has_perm('receipt_manage')): ?>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="receipt_save"><input type="hidden" name="_back" value="procurement">
      <div class="form-grid">
        <div class="field"><label>No Receipt</label><input class="input" name="receipt_no" placeholder="Auto jika kosong"></div>
        <div class="field"><label>PO</label><select class="select" name="po_id"><option value="">- Tanpa PO -</option><?php foreach($pos as $po): if($po['status']==='closed') continue; ?><option value="<?= h($po['id']) ?>"><?= h($po['po_no'].' / '.$po['sku'].' / sisa '.max(0,(float)$po['qty_ordered']-(float)$po['qty_received'])) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Supplier</label><select class="select" name="supplier_id"><option value="">- Dari PO / Pilih -</option><?php foreach($suppliers as $s): ?><option value="<?= h($s['id']) ?>"><?= h($s['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Barang</label><select class="select" name="item_id"><option value="">- Dari PO / Pilih -</option><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>"><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Lokasi Terima</label><select class="select" name="location_id" required><?php foreach($locations as $l): ?><option value="<?= h($l['id']) ?>"><?= h($l['warehouse_name'].' / '.$l['code']) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label>Qty Diterima</label><input class="input" type="number" step="0.01" name="qty_received" required></div>
        <div class="field"><label>Qty Accepted</label><input class="input" type="number" step="0.01" name="accepted_qty" required></div>
        <div class="field"><label>Qty Reject</label><input class="input" type="number" step="0.01" name="rejected_qty" value="0"></div>
        <div class="field"><label>Lot Number</label><input class="input" name="lot_no"></div>
        <div class="field"><label>Serial Number</label><input class="input" name="serial_no"></div>
        <div class="field"><label>Surat Jalan</label><input class="input" name="supplier_doc_no"></div>
        <div class="field"><label>Invoice</label><input class="input" name="invoice_no"></div>
        <div class="field"><label>Tanggal Terima</label><input class="input" type="date" name="received_date" value="<?= h(date('Y-m-d')) ?>"></div>
        <div class="field"><label>Lampiran</label><input class="input" type="file" name="attachment"></div>
      </div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="note"></textarea></div>
      <button class="btn btn-primary">Simpan Receipt</button>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat penerimaan.</p><?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Daftar Purchase Order</h3><span class="small muted"><span data-search-count="tablePO"><?= h(count($pos)) ?></span> PO</span></div>
  <div class="toolbar"><input class="input" id="searchPO" data-search-input="tablePO" placeholder="Cari PO, supplier, barang, status..."><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'po'])) ?>">Export</a><?php endif;?></div>
  <div class="table-wrap"><table class="table" id="tablePO"><thead><tr><th>PO</th><th>Supplier</th><th>Barang</th><th>Qty</th><th>Nilai</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($pos as $po): ?><tr data-search-row data-search="<?= h(implode(' ', [$po['po_no'], $po['supplier_name'], $po['sku'], $po['item_name'], $po['status'], $po['expected_date'], $po['notes']])) ?>">
      <td data-label="PO"><strong><?= h($po['po_no']) ?></strong><br><span class="small muted"><?= h($po['expected_date'] ?: '') ?></span></td>
      <td data-label="Supplier"><?= h($po['supplier_name']) ?></td>
      <td data-label="Barang"><?= h($po['sku'].' - '.$po['item_name']) ?></td>
      <td data-label="Qty"><?= h($po['qty_received'].' / '.$po['qty_ordered'].' '.$po['unit']) ?></td>
      <td data-label="Nilai"><?= rupiah((float)$po['qty_ordered'] * (float)$po['unit_price']) ?></td>
      <td data-label="Status"><?= status_badge($po['status']) ?></td>
      <td data-label="Aksi" class="actions"><?php if(has_perm('po_manage') && !in_array($po['status'], ['closed'], true)): ?><?= action_form('po_close',['id'=>$po['id']],'Tutup','btn btn-sm btn-warning') ?><?php endif; ?><?php if(has_perm('po_manage') && (float)$po['qty_received'] <= 0): ?><?= action_form('po_delete',['id'=>$po['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada PO sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>

<div class="card">
  <div class="section-title"><h3>Riwayat Penerimaan</h3><span class="small muted"><span data-search-count="tableReceipt"><?= h(count($receipts)) ?></span> receipt</span></div>
  <div class="toolbar"><input class="input" id="searchReceipt" data-search-input="tableReceipt" placeholder="Cari receipt, PO, barang, lot, invoice..."><?php if(has_perm('report_export')):?><a class="btn btn-sm" href="<?= h(url('reports',['export'=>'receipts'])) ?>">Export</a><?php endif;?></div>
  <div class="table-wrap"><table class="table" id="tableReceipt"><thead><tr><th>Receipt</th><th>Barang</th><th>Lokasi</th><th>Qty</th><th>Lot/Serial</th><th>Dokumen</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($receipts as $r): ?><tr data-search-row data-search="<?= h(implode(' ', [$r['receipt_no'], $r['po_no'], $r['sku'], $r['item_name'], $r['supplier_name'], $r['warehouse_name'], $r['location_code'], $r['lot_no'], $r['serial_no'], $r['supplier_doc_no'], $r['invoice_no'], $r['note']])) ?>">
      <td data-label="Receipt"><strong><?= h($r['receipt_no']) ?></strong><br><span class="small muted"><?= h(($r['po_no'] ?: '-') . ' / ' . $r['received_date']) ?></span></td>
      <td data-label="Barang"><?= h($r['sku'].' - '.$r['item_name']) ?><br><span class="small muted"><?= h($r['supplier_name']) ?></span></td>
      <td data-label="Lokasi"><?= h($r['warehouse_name'].' / '.$r['location_code']) ?></td>
      <td data-label="Qty"><?= h($r['accepted_qty'].' accepted / '.$r['rejected_qty'].' reject') ?></td>
      <td data-label="Lot/Serial"><?= h(trim(($r['lot_no'] ?: '-') . ' / ' . ($r['serial_no'] ?: '-'))) ?></td>
      <td data-label="Dokumen"><?= h(trim(($r['supplier_doc_no'] ?: '-') . ' / ' . ($r['invoice_no'] ?: '-'))) ?></td>
      <td data-label="Aksi" class="actions"><?php if(has_perm('receipt_delete')): ?><?= action_form('receipt_delete',['id'=>$r['id']],'Hapus/Reverse','btn btn-sm btn-danger') ?><?php endif; ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada receipt sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
