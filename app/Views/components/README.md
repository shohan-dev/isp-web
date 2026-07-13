# Reusable UI components

Use from any view that extends `layout/main-layout`:

```php
<?= $this->include('components/page-header', [
  'title' => 'Customers',
  'breadcrumb' => [
    ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
    ['label' => 'Customers'],
  ],
  'actions' => '<a class="btn btn-primary" href="...">New</a>',
]); ?>

<?= $this->include('components/stat-card', [
  'label' => 'Active Customers',
  'value' => $users_active,
  'icon' => 'fa fa-users',
  'tone' => 'success',
  'href' => route_to('route.customer'),
]); ?>

<?= $this->include('components/empty-state', [
  'title' => 'No records',
  'subtitle' => 'Try adjusting filters.',
  'icon' => 'fa fa-inbox',
]); ?>

<?= $this->include('components/badge', [
  'label' => 'Active',
  'tone' => 'success',
  'dot' => true,
]); ?>
```

`command-palette.php` is included automatically by `main-layout.php`.
