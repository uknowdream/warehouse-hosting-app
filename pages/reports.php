<?php
require_perm('page_reports');
$totalQty = (float)scalar("SELECT COALESCE(SUM(qty),0) FROM stock_balances WHERE stock_status='available'");
$value = (float)scalar("SELECT COALESCE(SUM(sb.qty*i.price),0) FROM stock_balances sb JOIN items i ON i.id=sb.item_id WHERE sb.stock_status='available'");
$lowCount = (int)scalar("SELECT COUNT(*) FROM (SELECT i.id, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) available_qty, i.min_stock FROM items i LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY i.id HAVING available_qty <= min_stock) x");
$cat = all_rows("SELECT c.name, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty ELSE 0 END),0) qty, COALESCE(SUM(CASE WHEN sb.stock_status='available' THEN sb.qty*i.price ELSE 0 END),0) value FROM categories c LEFT JOIN items i ON i.category_id=c.id LEFT JOIN stock_balances sb ON sb.item_id=i.id GROUP BY c.id ORDER BY c.name");
?>
<div class="grid grid-3">
  <div class="card stat"><div><h3>Total Stock</h3><strong><?= h($totalQty) ?></strong><p class="small muted">Qty available</p></div><div class="icon">Qty</div></div>
  <div class="card stat"><div><h3>Nilai Stock</h3><strong><?= rupiah($value) ?></strong><p class="small muted">Valuasi inventory</p></div><div class="icon">Rp</div></div>
  <div class="card stat"><div><h3>Stock Menipis</h3><strong><?= h($lowCount) ?></strong><p class="small muted">SKU butuh review</p></div><div class="icon">Min</div></div>
</div>
<div class="card">
  <div class="section-title"><h3>Download Laporan</h3></div>
  <?php if(has_perm('report_export')): ?>
    <div class="toolbar-left">
      <a class="btn" href="<?= h(url('reports',['export'=>'stock'])) ?>">Stock CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'movements'])) ?>">Transaksi CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'opname'])) ?>">Opname CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'purchase'])) ?>">Purchase CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'po'])) ?>">PO CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'receipts'])) ?>">Receipt CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'quality'])) ?>">Quality CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'lots'])) ?>">Lot CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'picking'])) ?>">Picking CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'cycle'])) ?>">Cycle CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'reorder'])) ?>">Reorder CSV</a>
      <a class="btn" href="<?= h(url('reports',['export'=>'audit'])) ?>">Audit CSV</a>
      <button class="btn btn-primary" onclick="window.print()">Print / PDF</button>
    </div>
  <?php else: ?><p class="muted">Role Anda tidak memiliki izin export laporan.</p><?php endif; ?>
</div>
<div class="card">
  <div class="section-title"><h3>Valuasi per Kategori</h3><span class="small muted"><span data-search-count="tableReportCategory"><?= h(count($cat)) ?></span> kategori</span></div>
  <div class="toolbar"><input class="input" id="searchReportCategory" data-search-input="tableReportCategory" placeholder="Cari kategori laporan..."></div>
  <div class="table-wrap"><table class="table" id="tableReportCategory"><thead><tr><th>Kategori</th><th>Qty</th><th>Nilai</th></tr></thead><tbody><?php foreach($cat as $c): ?><tr data-search-row data-search="<?= h(implode(' ', [$c['name'], $c['qty'], $c['value']])) ?>"><td data-label="Kategori"><?= h($c['name']) ?></td><td data-label="Qty"><?= h($c['qty']) ?></td><td data-label="Nilai"><?= rupiah($c['value']) ?></td></tr><?php endforeach; ?><tr data-search-empty hidden><td colspan="3">Tidak ada kategori sesuai pencarian.</td></tr></tbody></table></div>
</div>
