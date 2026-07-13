<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<style>
  /* The bulk-action group (#recycleBinBulkActions) is a bare inline-styled
     flex row (display:flex, no flex-wrap) instead of the shared
     .ipb-list-toolbar-actions class every other list-toolbar uses. On phone
     the parent .ipb-list-toolbar already stacks to column/100% width
     (responsive.css), but this inner row still lays its 3 buttons out
     nowrap — with restore + delete_forever + empty permissions all granted
     (super_admin) they have no room and shrink until their own label text
     wraps mid-word instead of the row wrapping. Give it the same
     flex-wrap + 2-per-row + 44px tap target treatment .ipb-list-toolbar-actions
     gets, without touching that shared class (blast radius unknown) or any
     desktop rule. */
  @media (max-width: 767px) {
    #recycleBinBulkActions {
      margin-left: 0 !important;
      width: 100%;
      flex-wrap: wrap;
    }
    #recycleBinBulkActions .btn {
      flex: 1 1 calc(50% - 8px);
      min-height: 44px;
    }
  }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">
    <?= $this->include('components/page-header', [
      'title' => 'Recycle Bin',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Recycle Bin'],
      ],
    ]); ?>

    <div class="box box-warning">
      <div class="box-header with-border">
        <form method="get" class="form-inline ipb-list-toolbar" style="display:flex;flex-wrap:wrap;gap:10px;align-items:flex-end;">
          <div class="form-group">
            <label class="ipb-filter-label">Entity</label>
            <select name="entity" class="form-control input-sm">
              <option value="">All types</option>
              <?php foreach ($entityLabels as $key => $label): ?>
                <option value="<?= esc($key) ?>" <?= $entity === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>From</label>
            <input type="date" name="from" class="form-control input-sm" value="<?= esc((string) $from) ?>">
          </div>
          <div class="form-group">
            <label>To</label>
            <input type="date" name="to" class="form-control input-sm" value="<?= esc((string) $to) ?>">
          </div>
          <div class="form-group">
            <label>Per page</label>
            <select name="per_page" class="form-control input-sm">
              <?php foreach ([10, 25, 50, 100] as $n): ?>
                <option value="<?= $n ?>" <?= (int) $perPage === $n ? 'selected' : '' ?>><?= $n ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-default btn-sm"><i class="fa fa-filter"></i> Filter</button>
          <a href="<?= route_to('route.recycle_bin') ?>" class="btn btn-link btn-sm">Clear</a>

          <div id="recycleBinBulkActions" style="margin-left:auto;display:flex;gap:8px;">
            <?php if (userHasPermission('recycle_bin', 'restore')): ?>
              <button type="button" class="btn btn-success btn-sm" id="restoreSelectedBtn" disabled>
                <i class="fa fa-undo"></i> Restore Selected
              </button>
            <?php endif; ?>
            <?php if (userHasPermission('recycle_bin', 'delete_forever')): ?>
              <button type="button" class="btn btn-danger btn-sm" id="deleteForeverSelectedBtn" disabled>
                <i class="fa fa-trash"></i> Delete Forever
              </button>
            <?php endif; ?>
            <?php if (userHasPermission('recycle_bin', 'empty')): ?>
              <button type="button" class="btn btn-danger btn-sm" id="emptyTrashBtn" data-toggle="modal" data-target="#emptyTrashModal">
                <i class="fa fa-trash-can"></i> Empty Trash
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="box-body">
        <?php if (empty($items)): ?>
          <?= $this->include('components/empty-state', [
            'icon' => 'fa-recycle',
            'title' => 'Recycle bin is empty',
            'subtitle' => 'Deleted items will appear here for 30 days before automatic purge.',
          ]); ?>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <caption class="sr-only">Recycle bin items</caption>
              <thead>
                <tr>
                  <?php if (userHasPermission('recycle_bin', 'restore') || userHasPermission('recycle_bin', 'delete_forever')): ?>
                    <th width="40" scope="col"><input type="checkbox" id="select_all"></th>
                  <?php endif; ?>
                  <th scope="col">Entity</th>
                  <th scope="col">Label</th>
                  <th scope="col">Deleted by</th>
                  <th scope="col">Deleted at</th>
                  <th scope="col">Expires</th>
                  <?php if (userHasPermission('recycle_bin', 'restore')): ?>
                    <th width="100" scope="col">Action</th>
                  <?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($items as $item):
                  $expiresTs = strtotime((string) $item->expires_at);
                  $daysLeft  = max(0, (int) ceil(($expiresTs - time()) / 86400));
                ?>
                  <tr>
                    <?php if (userHasPermission('recycle_bin', 'restore') || userHasPermission('recycle_bin', 'delete_forever')): ?>
                      <td><input type="checkbox" class="row-check" value="<?= (int) $item->id ?>"></td>
                    <?php endif; ?>
                    <td><?= esc($entityLabels[$item->entity] ?? $item->entity) ?></td>
                    <td><?= esc($item->entity_label) ?></td>
                    <td><?= esc($item->deleted_by_name ?: '—') ?></td>
                    <td><?= esc($item->created_at) ?></td>
                    <td>
                      <span class="label <?= $daysLeft <= 3 ? 'label-danger' : 'label-default' ?>">
                        <?= $daysLeft ?> day<?= $daysLeft === 1 ? '' : 's' ?> left
                      </span>
                    </td>
                    <?php if (userHasPermission('recycle_bin', 'restore')): ?>
                      <td>
                        <button type="button" class="btn btn-xs btn-success restore-one" data-id="<?= (int) $item->id ?>">
                          <i class="fa fa-undo"></i> Restore
                        </button>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?= $pager->links() ?>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php if (userHasPermission('recycle_bin', 'empty')): ?>
<div class="modal fade" id="emptyTrashModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Empty Recycle Bin</h4>
      </div>
      <div class="modal-body">
        <p>Permanently delete <strong>all</strong> items in the recycle bin? This cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-danger" id="confirmEmptyTrash">Empty Trash</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
(function () {
  const csrfName = '<?= csrf_token() ?>';
  const csrfHash = '<?= csrf_hash() ?>';

  function selectedIds() {
    return $('.row-check:checked').map(function () { return $(this).val(); }).get();
  }

  function toggleBulkButtons() {
    const has = selectedIds().length > 0;
    $('#restoreSelectedBtn, #deleteForeverSelectedBtn').prop('disabled', !has);
  }

  $('#select_all').on('change', function () {
    $('.row-check').prop('checked', this.checked);
    toggleBulkButtons();
  });

  $(document).on('change', '.row-check', toggleBulkButtons);

  function postAction(url, ids, successMsg) {
    if (!ids.length) return;
    $.ajax({
      url: url,
      method: 'POST',
      data: { ids: ids, [csrfName]: csrfHash },
      success: function (res) {
        if (res && res.status === 'success') {
          window.location.reload();
        } else {
          alert((res && res.response) || 'Operation failed');
        }
      },
      error: function () { alert('Request failed'); }
    });
  }

  $('#restoreSelectedBtn').on('click', function () {
    postAction('<?= route_to('route.recycle_bin.restore') ?>', selectedIds());
  });

  $('#deleteForeverSelectedBtn').on('click', function () {
    if (!confirm('Permanently delete selected items?')) return;
    postAction('<?= route_to('route.recycle_bin.delete_forever') ?>', selectedIds());
  });

  $('.restore-one').on('click', function () {
    postAction('<?= route_to('route.recycle_bin.restore') ?>', [$(this).data('id')]);
  });

  $('#confirmEmptyTrash').on('click', function () {
    $.ajax({
      url: '<?= route_to('route.recycle_bin.empty') ?>',
      method: 'POST',
      data: { [csrfName]: csrfHash },
      success: function (res) {
        if (res && res.status === 'success') {
          window.location.reload();
        } else {
          alert((res && res.response) || 'Operation failed');
        }
      },
      error: function () { alert('Request failed'); }
    });
  });
})();
</script>
<?= $this->endSection('script'); ?>
