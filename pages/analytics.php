<?php
require_perm('page_analytics');
$days = max(30, (int)($_GET['days'] ?? 90));
$usageRows = all_rows(
    "SELECT i.id, i.sku, i.name, i.unit, i.min_stock, COALESCE(i.safety_stock,0) safety_stock, COALESCE(NULLIF(i.reorder_point,0), i.min_stock + COALESCE(i.safety_stock,0)) reorder_point,
            COALESCE(NULLIF(i.default_lead_time_days,0), s.lead_time_days, 0) lead_time,
            COALESCE(u.used_qty,0) used_qty,
            COALESCE(b.available_qty,0) available_qty,
            m.last_movement
     FROM items i
     LEFT JOIN suppliers s ON s.id=i.supplier_id
     LEFT JOIN (
        SELECT item_id, SUM(qty) used_qty
        FROM stock_movements
        WHERE movement_type='out' AND status='completed' AND created_at >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
        GROUP BY item_id
     ) u ON u.item_id=i.id
     LEFT JOIN (
        SELECT item_id, MAX(created_at) last_movement
        FROM stock_movements
        GROUP BY item_id
     ) m ON m.item_id=i.id
     LEFT JOIN (
        SELECT item_id, SUM(CASE WHEN stock_status='available' THEN qty ELSE 0 END) available_qty
        FROM stock_balances
        GROUP BY item_id
     ) b ON b.item_id=i.id
     ORDER BY used_qty DESC, i.sku"
);
$reorder = [];
$fast = [];
$slow = [];
$dead = [];
foreach ($usageRows as $r) {
    $avgDaily = (float)$r['used_qty'] / $days;
    $target = max((float)$r['reorder_point'], ($avgDaily * (int)$r['lead_time']) + (float)$r['safety_stock'] + (float)$r['min_stock']);
    $suggest = max(0, ceil($target - (float)$r['available_qty']));
    $r['avg_daily'] = $avgDaily;
    $r['suggest_qty'] = $suggest;
    if ($suggest > 0) $reorder[] = $r;
    if ((float)$r['used_qty'] > 0) $fast[] = $r;
    if ((float)$r['used_qty'] > 0 && (float)$r['used_qty'] <= 5) $slow[] = $r;
    if (empty($r['last_movement']) || strtotime($r['last_movement']) < strtotime('-180 days')) $dead[] = $r;
}
$fast = array_slice($fast, 0, 20);
?>
<div class="card dashboard-filter-card">
  <div class="filter-head">
    <div><h3>Analitik Stock</h3><p class="small muted">Fast moving, slow moving, dead stock, dan rekomendasi reorder</p></div>
    <?php if(has_perm('report_export')): ?><a class="btn btn-outline" href="<?= h(url('reports',['export'=>'reorder'])) ?>">Export Reorder</a><?php endif; ?>
  </div>
  <form class="dashboard-filters" method="get">
    <input type="hidden" name="p" value="analytics">
    <div class="field"><label>Periode Usage</label><select class="select" name="days"><option value="30" <?= $days===30?'selected':'' ?>>30 hari</option><option value="90" <?= $days===90?'selected':'' ?>>90 hari</option><option value="180" <?= $days===180?'selected':'' ?>>180 hari</option></select></div>
    <div class="field filter-actions"><button class="btn btn-primary">Terapkan</button></div>
  </form>
</div>

<div class="grid grid-4">
  <div class="card stat"><div><h3>Reorder</h3><strong><?= h(count($reorder)) ?></strong><p class="small muted">SKU butuh pembelian</p></div><div class="icon">RO</div></div>
  <div class="card stat"><div><h3>Fast Moving</h3><strong><?= h(count($fast)) ?></strong><p class="small muted">ada pemakaian</p></div><div class="icon">FM</div></div>
  <div class="card stat"><div><h3>Slow Moving</h3><strong><?= h(count($slow)) ?></strong><p class="small muted">usage rendah</p></div><div class="icon">SM</div></div>
  <div class="card stat"><div><h3>Dead Stock</h3><strong><?= h(count($dead)) ?></strong><p class="small muted">tidak bergerak 180 hari</p></div><div class="icon">DS</div></div>
</div>

<div class="card">
  <div class="section-title"><h3>Rekomendasi Reorder</h3><span class="small muted"><span data-search-count="tableReorder"><?= h(count($reorder)) ?></span> item</span></div>
  <div class="toolbar"><input class="input" id="searchReorder" data-search-input="tableReorder" placeholder="Cari reorder..."><a class="btn btn-sm" href="<?= h(url('purchase')) ?>">Buat PR</a></div>
  <div class="table-wrap"><table class="table" id="tableReorder"><thead><tr><th>SKU</th><th>Available</th><th>Usage</th><th>Lead Time</th><th>ROP</th><th>Suggest</th></tr></thead><tbody>
    <?php foreach($reorder as $r): ?><tr data-search-row data-search="<?= h(implode(' ', [$r['sku'], $r['name'], $r['available_qty'], $r['used_qty'], $r['suggest_qty']])) ?>">
      <td data-label="SKU"><strong><?= h($r['sku']) ?></strong><br><span class="small muted"><?= h($r['name']) ?></span></td>
      <td data-label="Available"><?= h($r['available_qty'].' '.$r['unit']) ?></td>
      <td data-label="Usage"><?= h($r['used_qty']) ?><br><span class="small muted"><?= h(number_format($r['avg_daily'],2)) ?>/hari</span></td>
      <td data-label="Lead Time"><?= h($r['lead_time']) ?> hari</td>
      <td data-label="ROP"><?= h($r['reorder_point']) ?></td>
      <td data-label="Suggest"><strong><?= h($r['suggest_qty']) ?></strong></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="6">Tidak ada rekomendasi sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Fast Moving</h3></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Usage</th><th>Avg/Hari</th></tr></thead><tbody>
      <?php foreach($fast as $r): ?><tr><td data-label="Barang"><strong><?= h($r['sku']) ?></strong><br><span class="small muted"><?= h($r['name']) ?></span></td><td data-label="Usage"><?= h($r['used_qty']) ?></td><td data-label="Avg/Hari"><?= h(number_format($r['avg_daily'],2)) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
  <div class="card">
    <div class="section-title"><h3>Slow / Dead Stock</h3></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Barang</th><th>Usage</th><th>Last Move</th></tr></thead><tbody>
      <?php foreach(array_slice(array_merge($dead, $slow), 0, 40) as $r): ?><tr><td data-label="Barang"><strong><?= h($r['sku']) ?></strong><br><span class="small muted"><?= h($r['name']) ?></span></td><td data-label="Usage"><?= h($r['used_qty']) ?></td><td data-label="Last Move"><?= h($r['last_movement'] ?: '-') ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
