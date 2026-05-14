<?php
require_perm('page_notifications');
$uid = (int)current_user()['id'];
$rid = (int)current_user()['role_id'];
$rows = all_rows("SELECT n.*, r.name role_name, u.name user_name FROM notifications n LEFT JOIN roles r ON r.id=n.role_id LEFT JOIN users u ON u.id=n.user_id WHERE n.user_id=? OR n.role_id=? OR (n.user_id IS NULL AND n.role_id IS NULL) ORDER BY n.is_read, n.id DESC LIMIT 200", [$uid, $rid]);
$outbox = has_perm('notification_manage') ? all_rows("SELECT * FROM notification_outbox ORDER BY id DESC LIMIT 80") : [];
?>
<div class="card">
  <div class="section-title"><h3>Notifikasi</h3><span class="small muted"><span data-search-count="tableNotif"><?= h(count($rows)) ?></span> notifikasi</span></div>
  <div class="toolbar"><input class="input" id="searchNotif" data-search-input="tableNotif" placeholder="Cari notifikasi..."><?php if($rows): ?><?= action_form('notification_read_all',[],'Tandai Semua Dibaca','btn btn-sm btn-primary') ?><?php endif; ?></div>
  <div class="table-wrap"><table class="table" id="tableNotif"><thead><tr><th>Waktu</th><th>Tipe</th><th>Judul</th><th>Target</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($rows as $n): ?><tr data-search-row data-search="<?= h(implode(' ', [$n['created_at'], $n['type'], $n['title'], $n['message'], $n['role_name'], $n['user_name'], $n['is_read']])) ?>">
      <td data-label="Waktu"><?= h($n['created_at']) ?></td>
      <td data-label="Tipe"><?= h($n['type']) ?></td>
      <td data-label="Judul"><strong><?= h($n['title']) ?></strong><br><span class="small muted"><?= h($n['message']) ?></span></td>
      <td data-label="Target"><?= h($n['user_name'] ?: ($n['role_name'] ?: 'Global')) ?></td>
      <td data-label="Status"><?= status_badge($n['is_read'] ? 'read' : 'pending') ?></td>
      <td data-label="Aksi" class="actions"><?php if(!$n['is_read']): ?><?= action_form('notification_read',['id'=>$n['id']],'Dibaca','btn btn-sm') ?><?php endif; ?></td>
    </tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="6">Tidak ada notifikasi sesuai pencarian.</td></tr>
  </tbody></table></div>
</div>

<?php if(has_perm('notification_manage')): ?>
<div class="card">
  <div class="section-title"><h3>Outbox Email / WhatsApp</h3><span class="small muted"><?= h(count($outbox)) ?> pesan</span></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Channel</th><th>Recipient</th><th>Subject</th><th>Status</th></tr></thead><tbody>
    <?php foreach($outbox as $o): ?><tr>
      <td data-label="Waktu"><?= h($o['created_at']) ?></td>
      <td data-label="Channel"><?= h($o['channel']) ?></td>
      <td data-label="Recipient"><?= h($o['recipient']) ?></td>
      <td data-label="Subject"><?= h($o['subject']) ?><br><span class="small muted"><?= h($o['message']) ?></span></td>
      <td data-label="Status"><?= status_badge($o['status']) ?></td>
    </tr><?php endforeach; ?>
  </tbody></table></div>
</div>
<?php endif; ?>
