<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Unit',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Unit'],
      ],
    ]); ?>

<div class="box box-primary">
            <div class="box-header with-border ipb-box-toolbar">
        <div class="ipb-list-toolbar">
          <div class="ipb-list-toolbar-filters">
            <span class="ipb-filter-label"><i class="fa fa-list" aria-hidden="true"></i> Records</span>
          </div>
          <div class="ipb-list-toolbar-actions">
<?php if (userHasPermission('inventory_purchess', 'create')) : ?>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#unitModal">
                        <i class="fa fa-plus"></i> Unit
                    </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="unitTable">
                        <caption class="sr-only">Inventory units</caption>
                        <thead style="background-color: #0f3557; color: white;">
                            <tr class="text-center">
                                <th scope="col">Sl.No.</th>
                                <th scope="col">Unit</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($units as $unit): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($unit['name']) ?></td>
                                    <td>
                                        <?php if (userHasPermission('inventory_purchess', 'update')) : ?>
                                        <button class="btn btn-xs btn-success" data-toggle="modal" data-target="#editModal<?= $unit['id'] ?>" aria-label="Edit unit">
                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                        </button>
                                        <?php endif; ?>
                                        <?php if (userHasPermission('inventory_purchess', 'delete')) : ?>
                                        <a href="<?= route_to('inventory.unit_delete', $unit['id']) ?>" class="btn btn-xs btn-danger" onclick="return confirm('Delete this unit?')" aria-label="Delete unit">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $unit['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="<?= route_to('inventory.unit_update') ?>" method="post">
                                             <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $unit['id'] ?>">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Edit Unit</h5>
                                                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="text" name="name" class="form-control" value="<?= esc($unit['name']) ?>" required>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Add Modal -->
    <div class="modal fade" id="unitModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="<?= route_to('inventory.unit_create') ?>" method="post">
    <?= csrf_field() ?>
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Add Unit</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="text" name="name" class="form-control" placeholder="Enter unit name" required>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection(); ?>

<?= $this->section('script'); ?>
<script>
    $(document).ready(function() {
        $('#unitTable').DataTable({
            language: {
                searchPlaceholder: "Search...",
                lengthMenu: "SHOW _MENU_ ENTRIES"
            },
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rt<"row"<"col-sm-6"i><"col-sm-6 text-right"p>>'
        });
    });
</script>
<?= $this->endSection(); ?>
