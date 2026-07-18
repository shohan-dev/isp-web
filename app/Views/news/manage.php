<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Manage News & Notices',
      'subtitle' => 'Create and share important updates with your users',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'News Management'],
      ],
    ]); ?>

    <div class="row">
      <div class="col-md-5">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title" id="form-title">Create New Notice</h3>
          </div>
          <form action="<?= base_url('news/save'); ?>" method="post">
            <?= csrf_field(); ?>
            <input type="hidden" name="id" id="notice-id">
            <div class="box-body">
              <div class="form-group">
                <label>Title <span class="text-danger">*</span></label>
                <input type="text" name="name" id="notice-name" class="form-control" required placeholder="Enter notice title">
              </div>
              <div class="form-group">
                <label>Details / Message</label>
                <textarea name="details" id="notice-details" rows="5" class="form-control" placeholder="Enter notice content"></textarea>
              </div>
              <div class="form-group">
                <label>Link (Optional)</label>
                <input type="url" name="url" id="notice-url" class="form-control" placeholder="https://example.com">
                <small class="text-muted">Users will see a "View Details" button with this link.</small>
              </div>
            </div>
            <div class="box-footer">
              <button type="submit" class="btn btn-primary" style="border-radius: 6px;">
                <i class="fa fa-save"></i> Save Notice
              </button>
              <button type="reset" onclick="resetNoticeForm()" class="btn btn-default pull-right" style="border-radius: 6px;">
                Reset
              </button>
            </div>
          </form>
        </div>
      </div>

      <div class="col-md-7">
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title">Recent Notices</h3>
          </div>
          <div class="box-body">
            <?php if (!empty($notices)): ?>
              <div class="table-responsive">
                <table class="table table-hover">
                  <caption class="sr-only">Recent notices</caption>
                  <thead>
                    <tr>
                      <th style="width: 50px" scope="col">#</th>
                      <th scope="col">Title</th>
                      <th scope="col">Created At</th>
                      <th style="width: 120px" scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 1; foreach ($notices as $notice): ?>
                      <tr>
                        <td><?= $i++; ?></td>
                        <td>
                          <strong><?= $notice->name; ?></strong>
                          <div style="font-size: 11px; color: var(--text-muted, #777);" class="text-truncate">
                            <?= substr(strip_tags($notice->details ?? ''), 0, 50); ?>...
                          </div>
                        </td>
                        <td><?= date('d M, Y', strtotime($notice->created_at)); ?></td>
                        <td>
                          <button class="btn btn-xs btn-info" onclick='editNotice(<?= json_encode($notice); ?>)' title="Edit" aria-label="Edit notice">
                            <i class="fa fa-edit" aria-hidden="true"></i>
                          </button>
                          <a href="<?= base_url('news/delete/' . $notice->id); ?>"
                             class="btn btn-xs btn-danger"
                             onclick="return confirm('Are you sure you want to delete this notice?')" title="Delete" aria-label="Delete notice">
                            <i class="fa fa-trash" aria-hidden="true"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php else: ?>
              <div class="text-center" style="padding: 30px;">
                <i class="far fa-newspaper" style="font-size: 50px; color: #ddd; margin-bottom: 10px; display: block;"></i>
                <p class="text-muted">No notices posted yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<script>
  function editNotice(notice) {
    document.getElementById('notice-id').value = notice.id;
    document.getElementById('notice-name').value = notice.name;
    document.getElementById('notice-details').value = notice.details || '';
    document.getElementById('notice-url').value = notice.url || '';
    document.getElementById('form-title').innerText = 'Edit Notice';
    document.querySelector('.btn-primary').innerHTML = '<i class="fa fa-save"></i> Update Notice';
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function resetNoticeForm() {
    document.getElementById('notice-id').value = '';
    document.getElementById('form-title').innerText = 'Create New Notice';
    document.querySelector('.btn-primary').innerHTML = '<i class="fa fa-save"></i> Save Notice';
  }
</script>

<?= $this->endSection('content'); ?>
