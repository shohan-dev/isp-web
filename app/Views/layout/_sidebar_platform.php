<?php
$seg1 = $uri->getSegment(1);
$seg2 = (string) $uri->getSegment(2);
$adminListSeg2 = ['', 'packages', 'add', 'details'];

$tenantsOpen = ($seg1 === 'tenants');
$adminsOpen = ($seg1 === 'admins' && (
    in_array($seg2, $adminListSeg2, true)
    || ($seg2 !== '' && ctype_digit($seg2))
));
$platformOpen = ($seg1 === 'product-showcase')
    || ($seg1 === 'admins' && $seg2 === 'revenue')
    || ($seg2 === 'contactfetch');
?>

<li class="treeview <?= $tenantsOpen ? 'active menu-open' : ''; ?>">
  <a href="#" class="dropdown-toggle">
    <i class="fa fa-globe"></i>
    <span>Tenants</span>
    <span class="fa fa-angle-down pull-right"></span>
  </a>
  <ul class="treeview-menu" style="<?= $tenantsOpen ? 'display:block;' : 'display:none;'; ?>">
    <li class="<?= ($seg1 === 'tenants' && $seg2 !== 'create') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.tenants'); ?>">
        <i class="fa fa-globe"></i> Tenant Portals
        <span class="pull-right-container"><small class="label bg-blue">SaaS</small></span>
      </a>
    </li>
    <li class="<?= ($seg1 === 'tenants' && $seg2 === 'create') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.tenants.create'); ?>"><i class="fa fa-plus-circle"></i> Create Portal</a>
    </li>
  </ul>
</li>

<li class="treeview <?= $adminsOpen ? 'active menu-open' : ''; ?>">
  <a href="#" class="dropdown-toggle">
    <i class="fa fa-user-lock"></i>
    <span>Admins</span>
    <span class="fa fa-angle-down pull-right"></span>
  </a>
  <ul class="treeview-menu" style="<?= $adminsOpen ? 'display:block;' : 'display:none;'; ?>">
    <li class="<?= ($seg1 === 'admins' && $seg2 === '') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.Admin'); ?>"><i class="fa fa-user-lock"></i> Second Admins</a>
    </li>
    <li class="<?= ($seg1 === 'admins' && $seg2 === 'packages') ? 'active' : ''; ?>">
      <a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-box"></i> Admin Packages</a>
    </li>
  </ul>
</li>

<li class="treeview <?= $platformOpen ? 'active menu-open' : ''; ?>">
  <a href="#" class="dropdown-toggle">
    <i class="fa fa-user-shield"></i>
    <span>Platform</span>
    <span class="fa fa-angle-down pull-right"></span>
  </a>
  <ul class="treeview-menu" style="<?= $platformOpen ? 'display:block;' : 'display:none;'; ?>">
    <li class="<?= ($seg1 === 'product-showcase') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.productShowcase.index'); ?>"><i class="fa fa-images"></i> Product Showcase</a>
    </li>
    <li class="<?= ($seg1 === 'admins' && $seg2 === 'revenue') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.Admin.revenue'); ?>"><i class="fa fa-chart-line"></i> Platform Revenue</a>
    </li>
    <li class="<?= ($seg2 === 'contactfetch') ? 'active' : ''; ?>">
      <a href="<?= route_to('route.contact.fetch'); ?>"><i class="fa fa-address-book"></i> Contact Info's</a>
    </li>
  </ul>
</li>
