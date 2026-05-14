<?php
require_perm('page_audit');
$rows = all_rows("SELECT a.*, u.name user_name, r.name role_name FROM audit_logs a LEFT JOIN users u ON u.id=a.user_id LEFT JOIN roles r ON r.id=u.role_id ORDER BY a.id DESC LIMIT 300");
?>
<div class="card">
  <div class="toolbar">
    <input class="input" id="searchAudit" data-search-input="tableAudit" placeholder="Cari user, role, aksi, detail, IP...">
    <span class="small muted"><span data-search-count="tableAudit"><?= h(count($rows)) ?></span> log</span>
    <?php if(has_perm('report_export')):?><a class="btn" href="<?= h(url('reports',['export'=>'audit'])) ?>">Export Audit</a><?php endif;?>
  </div>
  <div class="table-wrap"><table class="table" id="tableAudit"><thead><tr><th>Waktu</th><th>User</th><th>Role</th><th>Aksi</th><th>Detail</th><th>IP</th></tr></thead><tbody>
    <?php foreach($rows as $a): ?><tr data-search-row data-search="<?= h(implode(' ', [$a['created_at'], $a['user_name'] ?? 'System', $a['role_name'], $a['action'], $a['detail'], $a['ip_address']])) ?>"><td data-label="Waktu"><?= h($a['created_at']) ?></td><td data-label="User"><?= h($a['user_name'] ?? 'System') ?></td><td data-label="Role"><?= h($a['role_name']) ?></td><td data-label="Aksi"><strong><?= h($a['action']) ?></strong></td><td data-label="Detail"><?= h($a['detail']) ?></td><td data-label="IP"><?= h($a['ip_address']) ?></td></tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="6">Tidak ada log audit sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
