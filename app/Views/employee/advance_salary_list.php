<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('css'); ?>
<style>
  .box { border-radius: 12px; box-shadow: 0 5px 12px rgba(0, 0, 0, 0.1); border: none; }
  .box-header { border-bottom: 1px solid rgba(0,0,0,0.08); padding: 15px 20px; }
  .box-title { font-weight: 600; font-size: 18px; }
</style>
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>
      <?= $title; ?>
      <small>Portal</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
      <li class="active">Advance Salary</li>
    </ol>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-xs-12">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Requests List</h3>
          </div>
          <div class="box-body">
            <div class="table-responsive">
              <table id="advance-salary-table" class="table table-bordered table-striped" style="width:100%">
                <thead>
                  <tr>
                    <th>#</th>
                    <?php if ($role !== 'employee'): ?>
                      <th>Employee</th>
                    <?php endif; ?>
                    <th>Amount</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
  $(document).ready(function() {
    const table = $('#advance-salary-table').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url: '<?= route_to("route.employee.advance_salary.fetch"); ?>',
        type: 'POST',
        data: function(d) {
          d.csrf_test_name = '<?= csrf_hash(); ?>';
        }
      },
      columns: [
        { data: 'serial', orderable: false, searchable: false },
        <?php if ($role !== 'employee'): ?>
          { data: 'employee' },
        <?php endif; ?>
        { data: 'amount' },
        { data: 'reason' },
        { data: 'status' },
        { data: 'created_at' },
        { data: 'action', orderable: false, searchable: false }
      ]
    });

    // Handle Approve / Reject actions for Admin
    $(document).on('click', '.btn-approve', function() {
      const id = $(this).data('id');
      updateStatus(id, 'approved');
    });

    $(document).on('click', '.btn-reject', function() {
      const id = $(this).data('id');
      updateStatus(id, 'rejected');
    });

    function updateStatus(id, status) {
      if(!confirm('Are you sure you want to ' + status + ' this request?')) return;
      
      $.ajax({
        url: '<?= base_url("employee-portal/advance-salary/update-status"); ?>/' + id,
        type: 'POST',
        data: {
          status: status,
          csrf_test_name: '<?= csrf_hash(); ?>'
        },
        dataType: 'json',
        success: function(d) {
          if (d.status === 'success') {
            tata.success('Success', d.response);
            table.ajax.reload();
          } else {
            tata.error('Error', d.response);
          }
        },
        error: function() {
          tata.error('Error', 'Network error or unauthorized.');
        }
      });
    }
  });
</script>
<?= $this->endSection('script'); ?>
