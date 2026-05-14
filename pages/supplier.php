<?php
require_perm('page_supplier');
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$edit = $editId ? one("SELECT * FROM suppliers WHERE id=?", [$editId]) : null;
$suppliers = all_rows("SELECT * FROM suppliers ORDER BY name");
?>
<div class="grid grid-2">
  <div class="card">
    <div class="section-title"><h3><?= $edit ? 'Edit Supplier' : 'Tambah Supplier' ?></h3></div>
    <?php if(has_perm('supplier_write')): ?>
    <?= post_form_open('supplier_save','supplier') ?>
      <input type="hidden" name="id" value="<?= h($edit['id'] ?? '') ?>">
      <div class="form-grid">
        <div class="field"><label>Nama Supplier</label><input class="input" name="name" value="<?= h($edit['name'] ?? '') ?>" required></div>
        <div class="field"><label>Kontak</label><input class="input" name="contact_name" value="<?= h($edit['contact_name'] ?? '') ?>"></div>
        <div class="field"><label>Telepon</label><input class="input" name="phone" value="<?= h($edit['phone'] ?? '') ?>"></div>
        <div class="field"><label>Email</label><input class="input" type="email" name="email" value="<?= h($edit['email'] ?? '') ?>"></div>
        <div class="field"><label>Lead Time Hari</label><input class="input" type="number" name="lead_time_days" value="<?= h($edit['lead_time_days'] ?? 0) ?>"></div>
        <div class="field"><label>Harga Terakhir</label><input class="input" type="number" step="0.01" name="last_price" value="<?= h($edit['last_price'] ?? 0) ?>"></div>
      </div>
      <div class="field"><label>Alamat</label><input class="input" name="address" value="<?= h($edit['address'] ?? '') ?>"></div>
      <div class="field"><label>Catatan</label><textarea class="textarea" name="notes"><?= h($edit['notes'] ?? '') ?></textarea></div>
      <button class="btn btn-primary">Simpan Supplier</button><?php if($edit): ?><a class="btn" href="<?= h(url('supplier')) ?>">Batal</a><?php endif; ?>
    </form>
    <?php else: ?><p class="muted">Role Anda hanya dapat melihat supplier.</p><?php endif; ?>
  </div>
  <div class="card">
    <div class="section-title"><h3>Daftar Supplier</h3><span class="small muted"><span data-search-count="tableSupplier"><?= h(count($suppliers)) ?></span> supplier</span></div>
    <div class="toolbar"><input class="input" id="searchSupplier" data-search-input="tableSupplier" placeholder="Cari supplier, kontak, email, telepon..."></div>
    <div class="table-wrap"><table class="table" id="tableSupplier"><thead><tr><th>Supplier</th><th>Kontak</th><th>Lead Time</th><th>Harga</th><th>Aksi</th></tr></thead><tbody>
    <?php foreach($suppliers as $s): ?><tr data-search-row data-search="<?= h(implode(' ', [$s['name'], $s['email'], $s['contact_name'], $s['phone'], $s['address'], $s['lead_time_days'], $s['last_price'], $s['notes']])) ?>"><td data-label="Supplier"><strong><?= h($s['name']) ?></strong><br><span class="small muted"><?= h($s['email']) ?></span></td><td data-label="Kontak"><?= h($s['contact_name']) ?><br><?= h($s['phone']) ?></td><td data-label="Lead Time"><?= h($s['lead_time_days']) ?> hari</td><td data-label="Harga"><?= rupiah($s['last_price']) ?></td><td data-label="Aksi" class="actions"><?php if(has_perm('supplier_write')): ?><a class="btn btn-sm" href="<?= h(url('supplier',['edit'=>$s['id']])) ?>">Edit</a><?php endif; ?><?php if(has_perm('supplier_delete')): ?><?= action_form('supplier_delete',['id'=>$s['id']],'Hapus','btn btn-sm btn-danger') ?><?php endif; ?></td></tr><?php endforeach; ?>
    <tr data-search-empty hidden><td colspan="5">Tidak ada supplier sesuai pencarian.</td></tr>
  </tbody></table></div></div>
</div>
