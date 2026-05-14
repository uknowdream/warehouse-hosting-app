<?php
require_perm('page_qr');

function qr_filter_int(string $key): int {
    if (!isset($_GET[$key]) || $_GET[$key] === '') return 0;
    return max(0, (int)$_GET[$key]);
}

function qr_selected($value, $current): string {
    return (string)$value === (string)$current ? 'selected' : '';
}

$itemFilter = qr_filter_int('item');
$warehouseId = qr_filter_int('warehouse_id');
$locationId = qr_filter_int('location_id');
$search = trim((string)($_GET['q'] ?? ''));

$warehouses = all_rows("SELECT id, name FROM warehouses ORDER BY name");
$locations = all_rows("SELECT l.id, l.code, l.warehouse_id, w.name warehouse_name FROM locations l JOIN warehouses w ON w.id=l.warehouse_id ORDER BY w.name,l.code");
$warehouseNames = array_column($warehouses, 'name', 'id');
$locationById = [];
foreach ($locations as $locationRow) {
    $locationById[(int)$locationRow['id']] = $locationRow;
}
if ($locationId && !isset($locationById[$locationId])) $locationId = 0;
if ($locationId && $warehouseId && (int)$locationById[$locationId]['warehouse_id'] !== $warehouseId) $locationId = 0;

$itemRow = $itemFilter ? one("SELECT id, sku, name FROM items WHERE id=?", [$itemFilter]) : null;
if ($itemFilter && !$itemRow) $itemFilter = 0;

$where = [];
$params = [];
if ($itemFilter) {
    $where[] = 'i.id = ?';
    $params[] = $itemFilter;
}
if ($warehouseId) {
    $where[] = 'w.id = ?';
    $params[] = $warehouseId;
}
if ($locationId) {
    $where[] = 'l.id = ?';
    $params[] = $locationId;
}
if ($search !== '') {
    $where[] = 'i.sku LIKE ?';
    $like = '%' . $search . '%';
    $params[] = $like;
}
$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$items = all_rows(
    "SELECT i.*, sb.location_id, sb.available_qty, l.code, w.name warehouse_name
     FROM items i
     LEFT JOIN (
        SELECT item_id, location_id, SUM(qty) available_qty
        FROM stock_balances
        WHERE stock_status='available'
        GROUP BY item_id, location_id
     ) sb ON sb.item_id=i.id
     LEFT JOIN locations l ON l.id=sb.location_id
     LEFT JOIN warehouses w ON w.id=l.warehouse_id
     {$sqlWhere}
     ORDER BY i.sku,w.name,l.code",
    $params
);

$activeFilters = [];
if ($itemFilter && $itemRow) $activeFilters[] = 'Barang: ' . $itemRow['sku'] . ' - ' . $itemRow['name'];
if ($search !== '') $activeFilters[] = 'Kode barang: ' . $search;
if ($warehouseId) $activeFilters[] = 'Gudang: ' . ($warehouseNames[$warehouseId] ?? $warehouseId);
if ($locationId) $activeFilters[] = 'Lokasi: ' . ($locationById[$locationId]['warehouse_name'] ?? '') . ' / ' . ($locationById[$locationId]['code'] ?? $locationId);
?>
<div class="card dashboard-filter-card">
  <div class="filter-head">
    <div>
      <h3>Filter Label QR</h3>
      <p class="small muted"><span data-qr-visible-count><?= h(count($items)) ?></span> label ditemukan</p>
    </div>
    <?php if(has_perm('qr_generate')):?><button class="btn btn-primary" type="button" onclick="window.print()">Cetak Label</button><?php endif;?>
  </div>
  <form class="dashboard-filters" method="get">
    <input type="hidden" name="p" value="qr">
    <?php if ($itemFilter): ?><input type="hidden" name="item" value="<?= h($itemFilter) ?>"><?php endif; ?>
    <div class="field">
      <label>Kode Barang</label>
      <input class="input" id="searchQR" data-qr-search name="q" value="<?= h($search) ?>" placeholder="Cari kode barang...">
    </div>
    <div class="field">
      <label>Gudang</label>
      <select class="select" name="warehouse_id" id="dashboardWarehouse">
        <option value="">Semua gudang</option>
        <?php foreach ($warehouses as $w): ?><option value="<?= h($w['id']) ?>" <?= qr_selected($w['id'], $warehouseId) ?>><?= h($w['name']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field">
      <label>Lokasi/Rak</label>
      <select class="select" name="location_id" id="dashboardLocation">
        <option value="" data-warehouse="">Semua lokasi</option>
        <?php foreach ($locations as $l): ?><option value="<?= h($l['id']) ?>" data-warehouse="<?= h($l['warehouse_id']) ?>" <?= qr_selected($l['id'], $locationId) ?>><?= h($l['warehouse_name'] . ' / ' . $l['code']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="field filter-actions">
      <button class="btn btn-primary" type="submit">Terapkan</button>
      <a class="btn btn-outline" href="<?= h(url('qr')) ?>">Reset</a>
    </div>
  </form>
  <?php if ($activeFilters): ?>
    <div class="active-filters">
      <?php foreach ($activeFilters as $filter): ?><span><?= h($filter) ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if(!has_perm('qr_generate')): ?><p class="muted">Role Anda hanya dapat melihat daftar label QR.</p><?php endif; ?>
</div>

<div class="card">
  <div class="section-title">
    <div>
      <h3>Label QR</h3>
      <span class="small muted">QR berisi ID barang, SKU, nama barang, lokasi, batch, dan expired date.</span>
    </div>
  </div>
  <div class="qr-grid" id="qrTable">
    <?php foreach($items as $i):
      $locationName = ($i['warehouse_name'] ?? '-') . ' / ' . ($i['code'] ?? '-');
      $payload = qr_payload($i, ['id'=>$i['location_id'], 'warehouse_name'=>$i['warehouse_name'], 'code'=>$i['code']]);
      $searchText = (string)$i['sku'];
    ?>
      <div class="qr-label" data-qr-label data-search="<?= h($searchText) ?>">
        <div class="qr-box" data-qr='<?= h($payload) ?>'></div>
        <strong><?= h($i['sku']) ?></strong><br>
        <span><?= h($i['name']) ?></span><br>
        <span class="small muted"><?= h($locationName) ?></span><br>
        <span class="small muted">Batch <?= h($i['batch_no'] ?: '-') ?> - Exp <?= h($i['expired_date'] ?: '-') ?></span><br>
        <span class="small muted">Available <?= h($i['available_qty'] ?? 0) ?> <?= h($i['unit']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="empty-state" data-qr-empty <?= $items ? 'hidden' : '' ?>>
    Tidak ada label QR sesuai filter.
  </div>
</div>
