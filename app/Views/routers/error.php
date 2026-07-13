<?= $this->extend('layout/main-layout'); ?>


<?= $this->section('content'); ?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Main content -->
    <section class="content ipb-saas-list">
      
    <?= $this->include('components/page-header', [
      'title' => 'Connection Error',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Mikrotik Routers'],
        ['label' => 'Connection Error'],
      ],
    ]); ?>
<div class="error-page">
        <h2 class="headline text-red">502</h2>
        <div class="error-content">

          <h3><i class="fa fa-warning text-red"></i> Oops! Could Not Connect</h3>

          <p><?= $error; ?></p>
          
          <a href="<?= route_to('route.customer'); ?>" class="btn btn-warning">
            <i class="fa fa-arrow-left"></i> Back
          </a>

          <?php if(!empty($router_id)): ?>
            <a href="<?= route_to('route.routers.edit', $router_id); ?>" class="btn btn-info">
              Check Router <i class="fa fa-arrow-right"></i>
            </a>
          <?php endif; ?>
          
        </div>
      </div>
    </section>
    <!-- /.content -->
  </div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>

<?= $this->endSection('script'); ?>
