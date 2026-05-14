<?php
require_perm('page_approval');
$rows = all_rows("SELECT a.*, ru.name requester_name, au.name approver_name FROM approvals a LEFT JOIN users ru ON ru.id=a.requester_id LEFT JOIN users au ON au.id=a.approved_by ORDER BY FIELD(a.status,'pending','approved','rejected'), a.id DESC");
?>
<div class="card">
  <div class="toolbar">
    <input class="input" id="searchApproval" data-search-input="tableApproval" placeholder="Cari tipe, requester, status, catatan...">
    <span class="small muted"><span data-search-count="tableApproval"><?= h(count($rows)) ?></span> approval</span>
  </div>
  <div class="table-wrap"><table class="table" id="tableApproval"><thead><tr><th>Tgl</th><th>Tipe</th><th>Requester</th><th>Catatan</th><th>Status</th><th>Approved By</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $a): ?>
    <tr data-search-row data-search="<?= h(implode(' ', [$a['created_at'], $a['type'], $a['ref_table'], $a['ref_id'], $a['requester_name'], $a['notes'], $a['status'], $a['approver_name'], $a['approved_at']])) ?>">
      <td data-label="Tgl"><?= h($a['created_at']) ?></td>
      <td data-label="Tipe"><strong><?= h($a['type']) ?></strong><br><span class="small muted"><?= h($a['ref_table'].' #'.$a['ref_id']) ?></span></td>
      <td data-label="Requester"><?= h($a['requester_name']) ?></td>
      <td data-label="Catatan"><?= h($a['notes']) ?></td>
      <td data-label="Status"><?= status_badge($a['status']) ?></td>
      <td data-label="Approved By"><?= h($a['approver_name']) ?><br><span class="small muted"><?= h($a['approved_at']) ?></span></td>
      <td data-label="Aksi" class="actions">
        <?php if($a['status']==='pending' && has_perm('approval_manage')): ?>
          <?= action_form('approval_approve',['id'=>$a['id']],'Approve','btn btn-sm btn-success') ?>
          <?= action_form('approval_reject',['id'=>$a['id']],'Reject','btn btn-sm btn-danger') ?>
        <?php else: ?><span class="small muted">Sudah diproses</span><?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="7">Tidak ada approval sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>
