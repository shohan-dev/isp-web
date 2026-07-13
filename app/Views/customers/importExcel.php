<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">
  <!-- Message container for errors and success -->
  <div id="message"></div>



  <!-- Form for file upload -->
  <section class="content ipb-saas-list">
    
    <?= $this->include('components/page-header', [
      'title' => 'Import Customers',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Customers', 'url' => route_to('route.customer')],
        ['label' => 'Import Customers'],
      ],
    ]); ?>

<div class="container">
      <?php /* Was route.customer.upload_Excel, a route name that does not exist —
               route_to() throws a RouterException in CI4, so the Import Customers
               page crashed for everyone the moment it rendered. The submit handler
               below intercepts and posts to preview_Excel anyway; name it here too
               so the non-JS fallback lands somewhere real. */ ?>
      <form id="excelForm" action="<?= route_to('route.customer.preview_Excel') ?>" method="post"
        enctype="multipart/form-data">
        <?= csrf_field(); ?>
        <div class="form-group">
          <label for="excel_file">Upload Excel File</label>
          <a href="<?= base_url('assets/importUsers.xlsx') ?>" class="btn btn-success"
            style="margin-left: 20px; margin-bottom: 20px;" download>
            Download Excel Template
          </a>
          <input type="file" name="excel_file" id="excel_file" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload and Display</button>
      </form>
      <!-- Custom Circular Loader (Spinner) -->
      <div id="circularLoader"
        style="display: none; text-align: center; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: var(--z-overlay, 1095);">
        <div class="spinner"></div>
      </div>
      <p id="progressText" style="display: none; text-align: center; font-weight: bold; margin-top: 5px;">Uploading...
      </p>

      <!-- Container to display uploaded Excel Data -->
      <div id="sheetDataContainer" style="margin-top: 20px;"></div>
    </div>
  </section>
</div>

<?= $this->endSection(); ?>
<?= $this->section('script'); ?>

<script>
  $(document).ready(function () {
    let previewData = [];
    let routerDataMap = [];
    let csrfName = '<?= csrf_token() ?>';
    let csrfHash = '<?= csrf_hash() ?>';

    $('#excelForm').on('submit', function (e) {
      e.preventDefault();
      let formData = new FormData(this);

      $('#circularLoader').show();
      $('#progressText').show().text('Parsing File...');
      $(this).find('button[type="submit"]').prop('disabled', true);

      $.ajax({
        url: "<?= route_to('route.customer.preview_Excel') ?>",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
          $('#circularLoader').hide();
          $('#progressText').hide();
          $('#excelForm').find('button[type="submit"]').prop('disabled', false);

          $('#message').html('');
          $('#sheetDataContainer').html('');

          if (response.csrf_token) {
              csrfHash = response.csrf_token;
              $('input[name="' + csrfName + '"]').val(csrfHash);
          }

          if (response.error) {
            $('#message').append('<div class="alert alert-danger">' + response.error + '</div>');
            return;
          }

          if (response.success) {
            previewData = response.previewData;
            routerDataMap = response.routerDataMap;

            let html = `
              <div class="box box-primary">
                <div class="box-header with-border">
                  <h3 class="box-title">Import Preview (${response.rowCount} Customers Found)</h3>
                </div>
                <div class="box-body no-padding" style="max-height: 400px; overflow-y: auto;">
                  <div class="table-responsive">
                  <table class="table table-striped table-hover">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Package</th>
                        <th>Router</th>
                        <th>Status</th>
                      </tr>
                    </thead>
                    <tbody>
            `;

            $.each(previewData, function (index, row) {
              html += `
                <tr>
                  <td>${index + 1}</td>
                  <td>${row.name}</td>
                  <td>${row.mobile}</td>
                  <td>${row.package_name}</td>
                  <td>${row.router_name}</td>
                  <td><span class="label label-info">${row.status}</span></td>
                </tr>
              `;
            });

            html += `
                    </tbody>
                  </table>
                  </div>
                </div>
                <div class="box-footer">
                  <button id="confirmImport" class="btn btn-success btn-lg btn-block">
                    <i class="fa fa-check"></i> Everything Correct, Import Now
                  </button>
                  <button id="cancelImport" class="btn btn-default btn-lg pull-left">Cancel</button>
                </div>
              </div>
            `;

            $('#sheetDataContainer').html(html);

            if (response.errors && response.errors.length > 0) {
              let errorsHTML = '<div class="alert alert-warning"><h4><i class="icon fa fa-warning"></i> Warnings Found!</h4><ul>';
              $.each(response.errors, function (index, error) {
                errorsHTML += '<li>' + error + '</li>';
              });
              errorsHTML += '</ul></div>';
              $('#message').append(errorsHTML);
            }
          }
        },
        error: function (xhr) {
          $('#circularLoader').hide();
          $('#progressText').hide();
          $('#excelForm').find('button[type="submit"]').prop('disabled', false);
          $('#message').html('<div class="alert alert-danger">An error occurred while parsing the file.</div>');
        }
      });
    });

    $(document).on('click', '#confirmImport', function () {
      let btn = $(this);
      btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');

      let postData = {
        data: JSON.stringify(previewData),
        routerDataMap: JSON.stringify(routerDataMap)
      };
      postData[csrfName] = csrfHash;

      $.ajax({
        url: "<?= route_to('route.customer.process_import') ?>",
        type: "POST",
        data: postData,
        dataType: "json",
        success: function (response) {
          if (response.csrf_token) {
              csrfHash = response.csrf_token;
              $('input[name="' + csrfName + '"]').val(csrfHash);
          }

          if (response.error) {
            $('#message').html('<div class="alert alert-danger">' + response.error + '</div>');
            btn.prop('disabled', false).html('<i class="fa fa-check"></i> Everything Correct, Import Now');
          } else {
            $('#message').html('<div class="alert alert-success">' + response.success + '</div>');
            $('#sheetDataContainer').html('');
            $('#excelForm')[0].reset();
          }
        },
        error: function () {
          $('#message').html('<div class="alert alert-danger">An error occurred during final import.</div>');
          btn.prop('disabled', false).html('<i class="fa fa-check"></i> Everything Correct, Import Now');
        }
      });
    });

    $(document).on('click', '#cancelImport', function () {
      $('#sheetDataContainer').html('');
      $('#message').html('');
      $('#excelForm')[0].reset();
    });
  });
</script>

<style>
  /* Custom CSS for Spinner */
  .spinner {
    border: 4px solid #f3f3f3;
    /* Light grey */
    border-top: 4px solid #3498db;
    /* Blue */
    border-radius: 50%;
    width: 80px;
    height: 80px;
    animation: spin 2s linear infinite;
    margin: auto;
  }

  /* Keyframes for spinner animation */
  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  /* Full screen overlay for spinner */
  #circularLoader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    /* Semi-transparent background */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: var(--z-overlay, 1095);
  }
</style>

<?= $this->endSection(); ?>