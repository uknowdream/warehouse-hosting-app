<?php
require_perm('page_tools');
$backups = all_rows("SELECT * FROM backup_logs ORDER BY id DESC LIMIT 80");
$tokens = all_rows("SELECT at.*, u.name user_name FROM api_tokens at LEFT JOIN users u ON u.id=at.created_by ORDER BY at.id DESC LIMIT 80");
$plainToken = $_SESSION['api_plain_token'] ?? '';
unset($_SESSION['api_plain_token']);
$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
?>
<div class="grid grid-3">
  <div class="card">
    <div class="section-title"><h3>Import CSV / Excel</h3><span class="small muted">Excel bisa disimpan sebagai CSV</span></div>
    <?php if(has_perm('import_manage')): ?>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?><input type="hidden" name="action" value="import_csv"><input type="hidden" name="_back" value="tools">
      <div class="field"><label>Target Data</label><select class="select" name="target">
        <option value="items">Master Barang</option>
        <option value="suppliers">Supplier</option>
        <option value="stock">Stock Awal / Penyesuaian</option>
        <option value="departments">Department</option>
        <option value="cost_centers">Cost Center</option>
        <option value="customers">Customer</option>
      </select></div>
      <div class="field"><label>File CSV</label><input class="input" type="file" name="csv_file" accept=".csv,text/csv" required></div>
      <button class="btn btn-primary">Import</button>
    </form>
    <p class="small muted">Header contoh barang: sku,name,unit,category,supplier,min_stock,safety_stock,reorder_point,max_stock,lead_time_days,price,barcode,batch_no,expired_date,issue_method</p>
    <?php else: ?><p class="muted">Role Anda tidak memiliki akses import.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title"><h3>Backup Database</h3></div>
    <?php if(has_perm('maintenance_manage')): ?>
      <?= post_form_open('backup_create','tools') ?><button class="btn btn-primary">Buat Backup SQL</button></form>
      <hr>
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?><input type="hidden" name="action" value="backup_restore"><input type="hidden" name="_back" value="tools">
        <div class="field"><label>Restore SQL</label><input class="input" type="file" name="sql_file" accept=".sql" required></div>
        <button class="btn btn-warning">Restore</button>
      </form>
    <?php else: ?><p class="muted">Role Anda tidak memiliki akses backup/restore.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="section-title"><h3>API Integrasi</h3></div>
    <?php if(has_perm('api_manage')): ?>
      <?php if($plainToken): ?><div class="alert alert-success"><strong>Token baru:</strong><br><code><?= h($plainToken) ?></code></div><?php endif; ?>
      <?= post_form_open('api_token_create','tools') ?>
        <div class="field"><label>Nama Token</label><input class="input" name="name" placeholder="ERP / Mobile Scanner" required></div>
        <button class="btn btn-primary">Buat Token</button>
      </form>
      <p class="small muted">Endpoint: <code><?= h($baseUrl . '/api.php?resource=stock') ?></code></p>
    <?php else: ?><p class="muted">Role Anda tidak memiliki akses API token.</p><?php endif; ?>
  </div>
</div>

<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3>Riwayat Backup</h3><span class="small muted"><?= h(count($backups)) ?> file</span></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>File</th><th>Size</th><th>Status</th><th>Download</th></tr></thead><tbody>
      <?php foreach($backups as $b): ?><tr>
        <td data-label="File"><strong><?= h($b['file_name']) ?></strong><br><span class="small muted"><?= h($b['created_at']) ?></span></td>
        <td data-label="Size"><?= h(number_format((float)$b['size_bytes']/1024, 1)) ?> KB</td>
        <td data-label="Status"><?= status_badge($b['status']) ?></td>
        <td data-label="Download"><a class="btn btn-sm" href="<?= h('download_backup.php?file=' . rawurlencode($b['file_name'])) ?>">Download</a></td>
      </tr><?php endforeach; ?>
    </tbody></table></div>
  </div>

  <div class="card">
    <div class="section-title"><h3>API Token</h3><span class="small muted"><?= h(count($tokens)) ?> token</span></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Nama</th><th>Prefix</th><th>Status</th><th>Last Used</th><th>Aksi</th></tr></thead><tbody>
      <?php foreach($tokens as $t): ?><tr>
        <td data-label="Nama"><strong><?= h($t['name']) ?></strong><br><span class="small muted"><?= h($t['user_name'].' / '.$t['created_at']) ?></span></td>
        <td data-label="Prefix"><code><?= h($t['token_prefix']) ?></code></td>
        <td data-label="Status"><?= status_badge($t['is_active'] ? 'active' : 'inactive') ?></td>
        <td data-label="Last Used"><?= h($t['last_used_at'] ?: '-') ?></td>
        <td data-label="Aksi" class="actions"><?php if(has_perm('api_manage')): ?><?= action_form('api_token_toggle',['id'=>$t['id']],$t['is_active'] ? 'Nonaktifkan' : 'Aktifkan','btn btn-sm btn-warning') ?><?php endif; ?></td>
      </tr><?php endforeach; ?>
    </tbody></table></div>
  </div>
</div>
