<?php
require_perm('page_stock_card');
$items = all_rows("SELECT id, sku, name, unit FROM items ORDER BY sku");
$itemId = isset($_GET['item_id']) ? (int)$_GET['item_id'] : (int)($items[0]['id'] ?? 0);
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$item = $itemId ? one("SELECT * FROM items WHERE id=?", [$itemId]) : null;
$balances = $itemId ? all_rows("SELECT sb.*, l.code, w.name warehouse_name FROM stock_balances sb JOIN locations l ON l.id=sb.location_id JOIN warehouses w ON w.id=l.warehouse_id WHERE sb.item_id=? ORDER BY w.name,l.code,sb.stock_status", [$itemId]) : [];
$events = [];
if ($itemId) {
    $events = array_merge($events, all_rows("SELECT created_at event_at, 'movement' source, movement_type type, qty, status, reference_no ref_no, note, department, cost_center, lot_no, serial_no FROM stock_movements WHERE item_id=? AND DATE(created_at) BETWEEN ? AND ?", [$itemId, $dateFrom, $dateTo]));
    $events = array_merge($events, all_rows("SELECT created_at event_at, 'receipt' source, 'in' type, accepted_qty qty, status, receipt_no ref_no, note, '' department, '' cost_center, lot_no, serial_no FROM goods_receipts WHERE item_id=? AND DATE(created_at) BETWEEN ? AND ?", [$itemId, $dateFrom, $dateTo]));
    $events = array_merge($events, all_rows("SELECT created_at event_at, 'quality' source, type, qty, type status, '' ref_no, note, '' department, '' cost_center, '' lot_no, '' serial_no FROM quality_records WHERE item_id=? AND DATE(created_at) BETWEEN ? AND ?", [$itemId, $dateFrom, $dateTo]));
    $events = array_merge($events, all_rows("SELECT created_at event_at, 'opname' source, status type, variance qty, status, CONCAT('COUNT#',id) ref_no, note, '' department, '' cost_center, '' lot_no, '' serial_no FROM stock_counts WHERE item_id=? AND DATE(created_at) BETWEEN ? AND ?", [$itemId, $dateFrom, $dateTo]));
    usort($events, fn($a, $b) => strcmp($b['event_at'], $a['event_at']));
}
?>
<div class="card dashboard-filter-card">
  <div class="filter-head">
    <div><h3>Kartu Stock</h3><p class="small muted">Riwayat transaksi per barang</p></div>
    <?php if(has_perm('report_export')): ?><a class="btn btn-outline" href="<?= h(url('reports',['export'=>'movements'])) ?>">Export Transaksi</a><?php endif; ?>
  </div>
  <form class="dashboard-filters" method="get">
    <input type="hidden" name="p" value="stock_card">
    <div class="field"><label>Barang</label><select class="select" name="item_id"><?php foreach($items as $i): ?><option value="<?= h($i['id']) ?>" <?= enterprise_selected($i['id'], $itemId) ?>><?= h($i['sku'].' - '.$i['name']) ?></option><?php endforeach; ?></select></div>
    <div class="field"><label>Dari</label><input class="input" type="date" name="date_from" value="<?= h($dateFrom) ?>"></div>
    <div class="field"><label>Sampai</label><input class="input" type="date" name="date_to" value="<?= h($dateTo) ?>"></div>
    <div class="field filter-actions"><button class="btn btn-primary">Tampilkan</button></div>
  </form>
</div>

<?php if($item): ?>
<div class="grid grid-3">
  <div class="card stat"><div><h3>Barang</h3><strong><?= h($item['sku']) ?></strong><p class="small muted"><?= h($item['name']) ?></p></div><div class="icon">SKU</div></div>
  <div class="card stat"><div><h3>Total Available</h3><strong><?= h(array_sum(array_map(fn($r) => $r['stock_status']==='available' ? (float)$r['qty'] : 0, $balances))) ?></strong><p class="small muted"><?= h($item['unit']) ?></p></div><div class="icon">Av</div></div>
  <div class="card stat"><div><h3>Nilai Available</h3><strong><?= rupiah(array_sum(array_map(fn($r) => $r['stock_status']==='available' ? (float)$r['qty'] * (float)$item['price'] : 0, $balances))) ?></strong><p class="small muted">Harga master</p></div><div class="icon">Rp</div></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Saldo Saat Ini</h3></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Lokasi</th><th>Status</th><th>Qty</th></tr></thead><tbody>
      <?php foreach($balances as $b): ?><tr><td data-label="Lokasi"><?= h($b['warehouse_name'].' / '.$b['code']) ?></td><td data-label="Status"><?= status_badge($b['stock_status']) ?></td><td data-label="Qty"><?= h($b['qty'].' '.$item['unit']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="card">
    <div class="section-title"><h3>Parameter Reorder</h3></div>
    <div class="table-wrap"><table class="table"><tbody>
      <tr><td>Minimum Stock</td><td><?= h($item['min_stock']) ?></td></tr>
      <tr><td>Safety Stock</td><td><?= h($item['safety_stock'] ?? 0) ?></td></tr>
      <tr><td>Reorder Point</td><td><?= h($item['reorder_point'] ?? 0) ?></td></tr>
      <tr><td>Max Stock</td><td><?= h($item['max_stock'] ?? 0) ?></td></tr>
      <tr><td>Lead Time</td><td><?= h($item['default_lead_time_days'] ?? 0) ?> hari</td></tr>
    </tbody></table></div>
  </div>
</div>

<div class="card">
  <div class="section-title"><h3>Timeline Mutasi</h3><span class="small muted"><span data-search-count="tableStockCard"><?= h(count($events)) ?></span> event</span></div>
  <div class="toolbar"><input class="input" id="searchStockCard" data-search-input="tableStockCard" placeholder="Cari sumber, tipe, referensi, lot..."></div>
  <div class="table-wrap"><table class="table" id="tableStockCard"><thead><tr><th>Tanggal</th><th>Sumber</th><th>Tipe</th><th>Qty</th><th>Status</th><th>Referensi</th><th>Lot/Serial</th><th>Catatan</th></tr></thead><tbody>
    <?php foreach($events as $e): ?><tr data-search-row data-search="<?= h(implode(' ', $e)) ?>">
      <td data-label="Tanggal"><?= h($e['event_at']) ?></td>
      <td data-label="Sumber"><?= h($e['source']) ?></td>
      <td data-label="Tipe"><?= h($e['type']) ?></td>
      <td data-label="Qty"><?= h($e['qty']) ?></td>
      <td data-label="Status"><?= status_badge($e['status']) ?></td>
      <td data-label="Referensi"><?= h(trim(($e['ref_no'] ?: '-') . ' / ' . ($e['department'] ?: '') . ' ' . ($e['cost_center'] ?: ''))) ?></td>
      <td data-label="Lot/Serial"><?= h(trim(($e['lot_no'] ?: '-') . ' / ' . ($e['serial_no'] ?: '-'))) ?></td>
      <td data-label="Catatan"><?= h($e['note']) ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="8">Tidak ada event sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
<?php endif; ?>
