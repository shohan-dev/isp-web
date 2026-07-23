<?= $this->extend('layout/main-layout'); ?>
<?php $this->section('needsDataTable'); ?>1<?php $this->endSection(); ?>
<?= $this->section('content'); ?>
<div class="content-wrapper">
    <section class="content ipb-saas-list">
        
    <?= $this->include('components/page-header', [
      'title' => 'Store Location',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Store Location'],
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
                    <button class="btn btn-primary" data-toggle="modal" data-target="#locationModal">
                        <i class="fa fa-plus"></i> Store Location
                    </button>
                    <?php endif; ?>
          </div>
        </div>
      </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="locationTable">
                        <caption class="sr-only">Store locations</caption>
                        <thead style="background-color: #0f3557; color: white;">
                            <tr class="text-center">
                                <th scope="col">Sl.No.</th>
                                <th scope="col">Location Name</th>
                                <th scope="col">Location Short Value</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1;
                            foreach ($locations as $location): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= esc($location['location_name']) ?></td>
                                    <td><?= esc($location['short_value']) ?></td>
                                    <td>
                                        <?php if (userHasPermission('inventory_purchess', 'update')) : ?>
                                        <button class="btn btn-xs btn-success" data-toggle="modal"
                                            data-target="#editModal<?= $location['id'] ?>" aria-label="Edit location">
                                            <i class="fa fa-edit" aria-hidden="true"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if (userHasPermission('inventory_purchess', 'delete')) : ?>
                                        <a href="<?= route_to('inventory.store_location_delete', $location['id']) ?>"
                                            class="btn btn-xs btn-danger" onclick="return confirm('Delete this location?')" aria-label="Delete location">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?= $location['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form action="<?= route_to('inventory.store_location_update') ?>" method="post">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $location['id'] ?>">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title">Edit Store Location</h5>
                                                    <button type="button" class="close"
                                                        data-dismiss="modal">&times;</button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="form-group">
                                                        <label for="location_name_<?= $location['id'] ?>">Location
                                                            Name</label>
                                                        <input type="text" name="location_name"
                                                            id="location_name_<?= $location['id'] ?>" class="form-control"
                                                            value="<?= esc($location['location_name']) ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label for="short_value_<?= $location['id'] ?>">Short Value</label>
                                                        <input type="text" name="short_value"
                                                            id="short_value_<?= $location['id'] ?>" class="form-control"
                                                            value="<?= esc($location['short_value']) ?>" required>
                                                    </div>

                                                </div>
                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-primary">Update</button>
                                                    <button type="button" class="btn btn-secondary"
                                                        data-dismiss="modal">Cancel</button>
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
    <div class="modal fade" id="locationModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="<?= route_to('inventory.store_location_create') ?>" method="post">
                <?= csrf_field() ?>
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Add Store Location</h5>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="location_name">Location Name</label>
                            <input type="text" name="location_name" id="location_name" class="form-control"
                                placeholder="Enter location name" required>
                        </div>
                        <div class="form-group">
                            <label for="short_value">Short Value</label>
                            <input type="text" name="short_value" id="short_value" class="form-control"
                                placeholder="Enter short value" required>
                        </div>

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
    $(document).ready(function () {
        $('#locationTable').DataTable({
            language: {
                searchPlaceholder: "Search...",
                lengthMenu: "SHOW _MENU_ ENTRIES"
            },
            dom: '<"row"<"col-sm-6"l><"col-sm-6 text-right"f>>rt<"row"<"col-sm-6"i><"col-sm-6 text-right"p>>'
        });
    });
</script>
<?= $this->endSection(); ?>