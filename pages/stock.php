<?php
require_perm('page_stock');

function stock_filter_int(string $key): int {
    if (!isset($_GET[$key]) || $_GET[$key] === '') return 0;
    return max(0, (int)$_GET[$key]);
}

function stock_filter_selected($value, $current): string {
    return (string)$value === (string)$current ? 'selected' : '';
}

$warehouseId = stock_filter_int('warehouse_id');
$locationId = stock_filter_int('location_id');
$status = isset($_GET['stock_status']) ? trim((string)$_GET['stock_status']) : '';
$search = trim((string)($_GET['q'] ?? ''));
$lowOnly = isset($_GET['low_only']) && $_GET['low_only'] === '1';

$warehouses = all_rows("SELECT id, name FROM warehouses ORDER BY name");
$locations = all_rows("SELECT l.id, l.code, w.name warehouse_name, l.warehouse_id FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$statusRows = all_rows("SELECT DISTINCT stock_status FROM stock_balances WHERE stock_status <> '' ORDER BY stock_status");
$statusOptions = array_column($statusRows, 'stock_status');
$warehouseNames = array_column($warehouses, 'name', 'id');
$locationById = [];
foreach ($locations as $locationRow) {
    $locationById[(int)$locationRow['id']] = $locationRow;
}
if ($locationId && !isset($locationById[$locationId])) $locationId = 0;
if ($locationId && $warehouseId && (int)$locationById[$locationId]['warehouse_id'] !== $warehouseId) $locationId = 0;
if ($status !== '' && !in_array($status, $statusOptions, true)) $status = '';

$where = [];
$params = [];
if ($warehouseId) {
    $where[] = 'w.id = ?';
    $params[] = $warehouseId;
}
if ($locationId) {
    $where[] = 'l.id = ?';
    $params[] = $locationId;
}
if ($status !== '') {
    $where[] = 'sb.stock_status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(i.sku LIKE ? OR i.name LIKE ? OR w.name LIKE ? OR l.code LIKE ? OR sb.stock_status LIKE ?)';
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($lowOnly) {
    $where[] = 'sb.stock_status = "available" AND sb.qty <= i.min_stock';
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$rows = all_rows(
    "SELECT i.sku, i.name item_name, i.unit, i.min_stock, i.price, i.expired_date,
            sb.qty, sb.stock_status, l.code location_code, w.name warehouse_name
     FROM stock_balances sb
     JOIN items i ON i.id=sb.item_id
     JOIN locations l ON l.id=sb.location_id
     JOIN warehouses w ON w.id=l.warehouse_id
     {$sqlWhere}
     ORDER BY w.name,l.code,i.sku,sb.stock_status",
    $params
);

$activeFilters = [];
if ($search !== '') $activeFilters[] = 'Cari: ' . $search;
if ($warehouseId) $activeFilters[] = 'Gudang: ' . ($warehouseNames[$warehouseId] ?? $warehouseId);
if ($locationId) $activeFilters[] = 'Lokasi: ' . ($locationById[$locationId]['warehouse_name'] ?? '') . ' / ' . ($locationById[$locationId]['code'] ?? $locationId);
if ($status !== '') $activeFilters[] = 'Status: ' . ucwords($status);
if ($lowOnly) $activeFilters[] = 'Stock menipis';
?>
<div class="card dashboard-filter-card">
  <div class="filter-head">
    <div>
      <h3>Filter Data Stock</h3>
      <p class="small muted"><span data-search-count="tableStock"><?= h(count($rows)) ?></span> baris ditemukan</p>
    </div>
    <?php if (has_perm('report_export')): ?><a class="btn btn-outline" href="<?= h(url('reports',['export'=>'stock'])) ?>">Export CSV</a><?php endif; ?>
  </div>
  <form class="dashboard-filters" method="get">
    <input type="hidden" name="p" value="stock">
    <div class="field">
      <label>Kata Kunci</label>
      <input class="input" id="searchStock" data-search-input="tableStock" name="q" value="<?= h($search) ?>" placeholder="SKU, barang, gudang, rak">
    </div>
    <div class="field">
      <label>Gudang</label>
      <select class="select" name="warehouse_id" id="dashboardWarehouse">
        <option value="">Semua gudang</option>
        <?php foreach ($warehouses as $w): ?><option value="<?= h($w['id']) ?>" <?= stock_filter_selected($w['id'], $warehouseId) ?>><?= h($w['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Lokasi/Rak</label>
      <select class="select" name="location_id" id="dashboardLocation">
        <option value="" data-warehouse="">Semua lokasi</option>
        <?php foreach ($locations as $l): ?><option value="<?= h($l['id']) ?>" data-warehouse="<?= h($l['warehouse_id']) ?>" <?= stock_filter_selected($l['id'], $locationId) ?>><?= h($l['warehouse_name'] . ' / ' . $l['code']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Status</label>
      <select class="select" name="stock_status">
        <option value="">Semua status</option>
        <?php foreach ($statusOptions as $option): ?><option value="<?= h($option) ?>" <?= stock_filter_selected($option, $status) ?>><?= h(ucwords($option)) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Prioritas</label>
      <select class="select" name="low_only">
        <option value="">Semua stock</option>
        <option value="1" <?= $lowOnly ? 'selected' : '' ?>>Stock menipis</option>
      </select>
    </div>
    <div class="field filter-actions">
      <button class="btn btn-primary" type="submit">Terapkan</button>
      <a class="btn btn-outline" href="<?= h(url('stock')) ?>">Reset</a>
    </div>
  </form>
  <?php if ($activeFilters): ?>
    <div class="active-filters">
      <?php foreach ($activeFilters as $filter): ?><span><?= h($filter) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="table-wrap"><table class="table" id="tableStock"><thead><tr><th>SKU</th><th>Nama</th><th>Gudang</th><th>Rak</th><th>Status</th><th>Qty</th><th>Min</th><th>Harga</th><th>Nilai</th><th>Expired</th></tr></thead><tbody>
  <?php if (!$rows): ?><tr data-search-empty><td colspan="10">Tidak ada stock sesuai filter.</td></tr><?php endif; ?>
  <?php foreach($rows as $r): ?>
    <tr data-search-row data-search="<?= h(implode(' ', [$r['sku'], $r['item_name'], $r['warehouse_name'], $r['location_code'], $r['stock_status'], $r['qty'], $r['unit'], $r['min_stock'], $r['price'], $r['expired_date']])) ?>">
      <td data-label="SKU"><strong><?= h($r['sku']) ?></strong></td>
      <td data-label="Nama"><?= h($r['item_name']) ?></td>
      <td data-label="Gudang"><?= h($r['warehouse_name']) ?></td>
      <td data-label="Rak"><?= h($r['location_code']) ?></td>
      <td data-label="Status"><?= status_badge($r['stock_status']) ?></td>
      <td data-label="Qty"><?= h($r['qty']) ?> <?= h($r['unit']) ?></td>
      <td data-label="Min"><?= h($r['min_stock']) ?></td>
      <td data-label="Harga"><?= rupiah($r['price']) ?></td>
      <td data-label="Nilai"><?= rupiah((float)$r['qty'] * (float)$r['price']) ?></td>
      <td data-label="Expired"><?= h($r['expired_date']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if ($rows): ?><tr data-search-empty hidden><td colspan="10">Tidak ada stock sesuai pencarian.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
