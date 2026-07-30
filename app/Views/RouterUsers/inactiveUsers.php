<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Offline Users',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Offline Users'],
      ],
    ]); ?>

<div class="box box-warning">
      <div class="box-body">
        <div class="table-responsive">
          <table class="table table-bordered table-striped datatable" id="router-users-table" width="100%">
          <caption class="sr-only">Offline users</caption>
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">PPPOE Name</th>
              <th scope="col">Customer Name</th>
              <th scope="col">Service</th>
              <th scope="col">Last Caller ID</th>
              <th scope="col">Last Logged Out</th>
              <th scope="col">Logout reason</th>
              <th scope="col">profile</th>
            </tr>
          </thead>
          <tbody id="customer-data"></tbody>
        </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {
    var routerId = "<?= esc($routerId) ?>";
    $('#router-users-table').DataTable({
      processing: true,
      serverSide: true,
      deferRender: true,
      pageLength: 100,
      lengthMenu: [[25, 50, 100, 250, 500], [25, 50, 100, 250, 500]],
      ajax: {
        url: "<?= base_url('routers/users-datatable'); ?>/" + routerId,
        data: function (d) { d.mode = 'inactive'; }
      },
      columnDefs: [{ targets: '_all', defaultContent: '-' }],
      order: [[0, 'asc']]
    });
  });
</script>

<?= $this->endSection('script'); ?>
