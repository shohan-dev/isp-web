<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('content'); ?>

<div class="content-wrapper">
  <section class="content ipb-saas-list">

    <?= $this->include('components/page-header', [
      'title' => 'Theme Studio',
      'subtitle' => 'Brand colors, density and presets — saved on this device',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Theme Studio'],
      ],
      'actions' => '<button type="button" class="btn btn-default" data-theme-reset><i class="fa fa-rotate-left" aria-hidden="true"></i> Reset to default</button>',
    ]); ?>

    <div class="box box-solid ipb-theme-page-box">
      <div class="box-body">
        <?= $this->include('components/theme-studio-panel', ['layout' => 'page']); ?>
      </div>
    </div>

  </section>
</div>

<?= $this->endSection('content'); ?>
