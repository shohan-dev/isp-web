<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>


<?= $this->section('content'); ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content ipb-saas-list">


    <?= $this->include('components/page-header', [
      'title' => 'POP',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'POP'],
        ['label' => 'payment_details'],
        ['label' => 'POP'],
      ],
    ]); ?>
<div class="row">

  <div class="col-xs-6  col-sm-6 col-md-4 col-lg-3 ">
    <div class="small-box bg-green">
      <div class="inner">
        <h3><?= $total_price; ?></h3>

        <p>Total Price</p>
      </div>
      <div class="icon">
        <i class="fa fa-users"></i>
      </div>
      <a href="<?= route_to('route.Reseller.payment'); ?>" class="small-box-footer">
        View Details <i class="fa fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>

  <div class="col-xs-6  col-sm-6 col-md-4 col-lg-3 ">
    <div class="small-box bg-red">
      <div class="inner">
        <h3><?= $paidAmount; ?></h3>

        <p>Total Paid</p>
      </div>
      <div class="icon">
        <i class="fa fa-users"></i>
      </div>
      <a href="<?= route_to('route.Reseller.payment'); ?>" class="small-box-footer">
        View Details <i class="fa fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>


  <div class="col-xs-6  col-sm-6 col-md-4 col-lg-3 ">
    <div class="small-box bg-teal-active">
      <div class="inner">
        <h3><?= $Due; ?></h3>

        <p>Total Due</p>
      </div>
      <div class="icon">
        <i class="fa fa-table-list"></i>
      </div>
      <a href="<?= route_to('route.Reseller.payment'); ?>" class="small-box-footer">
        View Details <i class="fa fa-arrow-circle-right"></i>
      </a>
    </div>
  </div>

</div>


</section>
  <!-- /.content -->
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<script>
  $(document).ready(function() {

    $('.datatable').DataTable({
      ajax: {
        url: '<?= route_to("route.Reseller.fetch"); ?>',
          type: 'post',
          
        beforeSend: function (req) {
            req.setRequestHeader('<?= csrf_header() ?>', '<?= csrf_hash() ?>',);
          }
        },
        columnDefs: [
            {
              "targets": "_all",  
              "defaultContent": "-"
            }
          ],
      });

    <?php if (userHasPermission('Resellers', 'delete')||userHasPermission('reseller', 'delete')) : ?>

      //check all checkbox function
      $("#select_all").click(function() {

        if (this.checked) {

          $(".input-check-selected").each(function() {
            this.checked = true;
          });

        } else {

          $(".input-check-selected").each(function() {
            this.checked = false;
          });
        }
      });

      $(document).on("click", ".input-check-selected:checkbox", function() {

        if ($(".input-check-selected:checkbox:checked").length === $(".input-check-selected:checkbox").length) {

          $("#select_all").prop("checked", true);

        } else {

          $("#select_all").prop("checked", false);
        }
      });

      //Function for delete users
      $(document).on('click', '.delete-btn', function() {

        swal({
          title: "Confirmation",
          text: "Are you sure you want to delete the selected POP?",
          dangerMode: true,
          icon: 'warning',
          buttons: ["No", {
            text: "Yes",
            closeModal: false,
          }],
        }).then((willDelete) => {

          if (willDelete) {

            const selectedIds = $('.input-check-selected:checkbox:checked');

            const ids = [];

            $(selectedIds).each(function() {
              ids.push($(this).val());
            });

            $.ajax({
              url: '<?= route_to("route.reseller.delete"); ?>',
              type: 'DELETE',
              data: {
                ids
              },
              headers: {
                '<?= csrf_header() ?>': '<?= csrf_hash() ?>',
              },
              success: function(result) {

                swal.close();

                tata.success('Reseller deleted', result.response);

                $('.datatable').DataTable().ajax.reload(null, false);
              },

              error: function(response) {

                const result = jQuery.parseJSON(response.responseText);

                swal.close();

                tata.error("Couldn't delete reseller", result.response);
              }

            });
          }
        });
      });

    <?php endif; ?>

  });
</script>

<?= $this->endSection('script'); ?>