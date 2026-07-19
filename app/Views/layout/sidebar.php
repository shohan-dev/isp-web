<aside class="main-sidebar" aria-label="Main navigation">
  <?php
  $uri = service('uri');
  $isExpiredSession = (getSession('status') === 'inactive');
  $currentRole = getSession('user_role');
  $currentUserId = (int) getSession('user_id');
  $sidebarPins = $currentUserId > 0 ? model('App\Models\SidebarPinModel')->getForUser($currentUserId) : [];
  $orgRow = getOrgById(getSession('user_id'));
  $orgName = (is_array($orgRow) ? ($orgRow['organization_name'] ?? null) : null)
    ?? (function_exists('currentTenant') && currentTenant() ? currentTenant()->name : null)
    ?? getSetting('app_name');
  $appName = getSetting('app_name') ?: 'ISP Pay BD';
  if (function_exists('isTenantRequest') && isTenantRequest() && currentTenant()) {
    $appName = currentTenant()->name ?: $appName;
  }
  $brandFile = getSetting('app_icon') ?: getSetting('app_logo');
  $brandLogoUrl = (function_exists('isTenantRequest') && isTenantRequest() && function_exists('tenantLogoUrl'))
    ? tenantLogoUrl()
    : (!empty($brandFile)
      ? base_url('assets/img/logo/' . $brandFile)
      : (function_exists('getBrandFaviconUrl') ? getBrandFaviconUrl() : base_url('assets/img/icon/logo.png')));
  ?>

  <a href="<?= route_to('route.dashboard'); ?>" class="ipb-brand" title="<?= esc($appName, 'attr'); ?>">
    <div class="ipb-brand-mark">
      <img src="<?= esc($brandLogoUrl, 'attr'); ?>" alt="<?= esc($appName, 'attr'); ?>" />
    </div>
    <div class="ipb-brand-text">
      <div class="ipb-brand-name"><?= esc($appName); ?></div>
      <div class="ipb-brand-org"><?= esc($orgName); ?></div>
    </div>
  </a>

  <div class="ipb-sidebar-search">
    <div class="ipb-sidebar-search-inner">
      <i class="fa fa-search" aria-hidden="true"></i>
      <input type="search" id="ipbSidebarSearch" placeholder="Search menu..." aria-label="Search menu" autocomplete="off" />
    </div>
  </div>

  <section class="sidebar">
    <div class="ipb-sidebar-empty" id="ipbSidebarEmpty" aria-live="polite">No menu matches</div>

    <?php if ($isExpiredSession): ?>
      <!-- ========== EXPIRED USER: LIMITED SIDEBAR ========== -->
      <ul class="sidebar-menu" data-widget="tree">

        <?php if ($currentRole === 'admin'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
          <!-- Admin's Packages -->
          <li class="<?= ($uri->getSegment(2) === 'packages') ? 'active' : ''; ?>">
            <a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-table-list"></i> <span>Admin's Packages</span></a>
          </li>

          <!-- Self Recharge -->
          <li>
            <a
              href="<?= route_to('route.Admin.subscription', (int) session()->get('user_id') ?: (int) session()->get('id') ?: 0); ?>">
              <i class="fa fa-bolt"></i>
              <span>Self Recharge</span>
            </a>
          </li>

          <!-- My Payment -->
          <li class="<?= ($uri->getSegment(1) === 'payment') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.payment'); ?>">
              <i class="fa fa-money-bill-transfer"></i>
              <span>My Payment</span>
            </a>
          </li>
        <?php elseif ($currentRole === 'user'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
          <!-- User's Packages -->
          <li class="<?= ($uri->getSegment(2) === 'packages') ? 'active' : ''; ?>">
            <a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-table-list"></i> <span>User's Packages</span></a>
          </li>

          <!-- My Subscription -->
          <li class="<?= ($uri->getSegment(1) === 'subscription') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.subscription'); ?>">
              <i class="fa fa-calendar-check"></i>
              <span>My Subscription</span>
            </a>
          </li>

          <!-- My Payment -->
          <li class="<?= ($uri->getSegment(1) === 'payment') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.payment'); ?>">
              <i class="fa fa-money-bill-transfer"></i>
              <span>My Payment</span>
            </a>
          </li>
        <?php endif; ?>

        <!-- Logout (always shown) -->
        <li data-no-pin="1">
          <a href="<?= route_to('route.logout'); ?>" data-full-reload="1">
            <i class="fa fa-sign-out"></i>
            <span>Logout</span>
          </a>
        </li>

      </ul>
    <?php else: ?>
      <!-- ========== NORMAL (NON-EXPIRED) SIDEBAR ========== -->
      <ul class="sidebar-menu" data-widget="tree">

        <li class="<?= ($uri->getSegment(1) === 'dashboard') ? 'active' : ''; ?>">

          <a href="<?= route_to('route.dashboard'); ?>">
            <i class="fa fa-gauge-high"></i>
            <span>Dashboard</span>
          </a>
        </li>

        <?php if (!empty($sidebarPins)): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Pinned</span></li>
          <?php foreach ($sidebarPins as $pin): ?>
            <li class="ipb-pinned-item" data-pin-key="<?= esc($pin['pin_key'], 'attr'); ?>">
              <a href="<?= esc($pin['href'], 'attr'); ?>">
                <i class="fa <?= esc($pin['icon'] ?: 'fa-thumbtack', 'attr'); ?>"></i>
                <span><?= esc($pin['label']); ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($currentRole === 'super_admin'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">System</span></li>
          <?= view('layout/_sidebar_platform', ['uri' => $uri]); ?>

          <?php if (userHasPermission('customer_payment')): ?>
            <li>
              <a href="<?= route_to('route.customer.payment'); ?>">
                <i class="fa fa-money-bill-1"></i>
                <span>Customers Payment</span>
              </a>
            </li>
          <?php endif; ?>

          <li class="<?= ($uri->getSegment(1) === 'admin' && $uri->getSegment(2) === 'plugins') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.plugins.admin'); ?>">
              <i class="fa fa-puzzle-piece"></i>
              <span>Plugins & Addons</span>
            </a>
          </li>

          <?php /* File Manager browses ROOTPATH (backend source + .env); it is a
                   platform-owner tool. FileManager::checkAccess() now enforces
                   super_admin server-side — keep the menu item in step so tenant
                   admins are not shown a link that 404s. */ ?>
          <?php if (getSession('user_role') === 'super_admin'): ?>
          <li class="<?= ($uri->getSegment(1) === 'file-manager') ? 'active' : ''; ?>">
            <a href="<?= base_url('file-manager'); ?>">
              <i class="fa fa-folder-tree"></i>
              <span>File Manager</span>
            </a>
          </li>
          <?php endif; ?>

          <li class="<?= ($uri->getSegment(1) === 'user-access') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.useraccess'); ?>">
              <i class="fa fa-user-lock"></i>
              <span>User Access Management</span>
            </a>
          </li>

          <?php /* Software Settings was only in the non-super_admin branch below and
                   gated by userHasPermission('software_settings'), so the platform
                   owner never saw it. Surface it here for super_admin too. */ ?>
          <li class="<?= ($uri->getSegment(2) === 'software') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.settings.software'); ?>">
              <i class="fa fa-gears"></i>
              <span>Software Settings</span>
            </a>
          </li>

        <?php else: ?>

        <?php if (!in_array($currentRole, ['super_admin', 'user'], true) && userHasPermission('area')): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Operations</span></li>
          <li class="<?= ($uri->getSegment(1) === 'area') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.area'); ?>">
              <i class="fa fa-map-location-dot"></i>
              <span>Service Areas</span>
            </a>
          </li>
        <?php endif; ?>
        <?php
        $user = getUserById(getSession('user_id'));
        $createdBy = null;

        if (is_array($user) && isset($user['created_by'])) {
          $createdBy = $user['created_by'];
        } elseif (is_object($user) && isset($user->created_by)) {
          $createdBy = $user->created_by;
        }
        ?>

        <?php if (userHasPermission('customer')): ?>
            <li class="treeview">
              <a href="#" class="dropdown-toggle">
                <i class="fa fa-users-gear"></i>
                <span>Customers</span>
                <span class="fa fa-angle-down pull-right"></span>
              </a>
              <ul class="treeview-menu" style="display: none;">
                <li><a href="<?= route_to('route.customer'); ?>">
                    <i class="fa fa-users"></i>
                    <span>All Customers</span>
                  </a></li>
                <li><a href="<?= route_to('route.expired_customer'); ?>">
                    <i class="fa fa-user-clock"></i>
                    <span>Expired Customers</span>
                  </a></li>
                <?php if (in_array($currentRole, ['admin', 'super_admin'], true)): ?>
                  <li><a href="<?= route_to('route.customer.free_requests'); ?>">
                      <i class="fa fa-hand-holding-heart"></i>
                      <span>Free User Requests</span>
                    </a></li>
                <?php endif; ?>
                <?php if (userHasPermission('customer_payment')): ?>
                    <li>
                      <a href="<?= route_to('route.customer.payment'); ?>">
                        <i class="fa fa-file-invoice-dollar"></i>
                        <span>Customers Payment</span>
                      </a>
                    </li>
                <?php endif; ?>
              </ul>
            </li>
        <?php endif; ?>


        <?php if (getSession('user_role') === 'admin'): ?>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-user-group"></i>
              <span>Employees</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <?php if (userHasPermission('employee')): ?>
                  <li class="<?= ($uri->getSegment(2) === 'employees') ? 'active' : ''; ?>">
                    <a href="<?= route_to('route.employee'); ?>">
                      <i class="fa fa-user-gear"></i>
                      <span>Employees</span>
                    </a>
                  </li>
              <?php endif; ?>
              <?php if (userHasPermission('employee_payment')): ?>
                  <li class="<?= ($uri->getSegment(2) === 'employee-payments') ? 'active' : ''; ?>">
                    <a href="<?= route_to('route.employee.payment'); ?>">
                      <i class="fa fa-hand-holding-dollar"></i>
                      <span>Employees Payment</span>
                    </a>
                  </li>
              <?php endif; ?>
            </ul>
          </li>
          <?php
        endif; ?>



        <?php if (($currentRole === 'user') && userHasPermission('subscription')): ?>
            <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
            <li class="<?= ($uri->getSegment(1) === 'subscription') ? 'active' : ''; ?>">
              <a href="<?= route_to('route.subscription'); ?>">
                <i class="fa fa-calendar-check"></i>
                <span>My Subscription</span>
              </a>
            </li>
        <?php endif; ?>


        <?php if (getSession('user_role') === 'admin' || $createdBy === 'admin'): ?>
          <?php if (userHasPermission('packages')): ?>

            <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
            <li class="<?= ($uri->getSegment(1) === 'packages') ? 'active' : ''; ?>">

              <a href="<?= route_to('route.packages'); ?>">
                <i class="fa fa-box-open"></i>
                <span>Packages</span>
              </a>
            </li>

            <?php
          endif; ?>
          <?php
        endif; ?>

        <?php /* Was getUserById(getSession('user_id'))->created_by, dereferenced with
                 no null check. getUserById() returns null when the row is gone (e.g.
                 an admin deletes a reseller/employee whose session is still alive),
                 and it can return an array rather than an object — so this fataled
                 with "Attempt to read property on null" and took the whole sidebar,
                 hence every page, down for that session. $createdBy is the guarded
                 value already computed from the same lookup above. */ ?>
        <?php if (getSession('user_role') === 'resellerAdmin' || $createdBy === 'resellerAdmin'): ?>
          <?php if (userHasPermission('packages')): ?>

            <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
            <li class="<?= ($uri->getSegment(1) === 'packages') ? 'active' : ''; ?>">

              <a href="<?= route_to('resellers.packages', getSession('user_id')); ?>">
                <i class="fa fa-boxes-stacked"></i>
                <span>POPs Packages</span>
              </a>
            </li>

            <?php
          endif; ?>
          <?php
        endif; ?>

        <?php if (getSession('user_role') === 'admin' && userHasPermission('accounting')): ?>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-calculator"></i>
              <span>Accounting</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>

            <ul class="treeview-menu" style="display: none;">
              <li>
                <a href="<?= route_to('route.income.list'); ?>"> <i class="fa fa-hand-holding-dollar"></i>Incomes</a>
              </li>

              <li>
                <a href="<?= route_to('route.expense.list'); ?>"> <i class="fa fa-money-bill-transfer"></i>Expenses</a>
              </li>
              <li>
                <a href="<?= route_to('route.expense.qSelectCriteria'); ?>"> <i class="fa fa-chart-bar"></i>Accounts
                  Report</a>
              </li>
              <li>
                <a href="<?= route_to('otc.report'); ?>"> <i class="fa fa-file-lines"></i>OTC Report</a>
              </li>




            </ul>
          </li>
          <?php
        endif; ?>
        <?php if (getSession('user_role') === 'resellerAdmin'): ?>

          <li class="<?= ($uri->getSegment(2) === 'transaction') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.reseller.transactionindex'); ?>">
              <i class="fa fa-receipt"></i>
              <span>POP Transactions</span>
            </a>
          </li>
          <li class="<?= ($uri->getSegment(2) === 'Funding') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.reseller.funding'); ?>">
              <i class="fa fa-wallet"></i>
              <span>POP Funding</span>
            </a>
          </li>

          <?php
        endif; ?>
        <!-- || getSession('user_role') === 'resellerAdmin' -->
        <?php if (getSession('user_role') === 'admin' || getSession('user_role') === 'employee'): ?>
          <?php if (userHasPermission('Resellers') || userHasPermission('reseller')): ?>
            <li class="treeview">
              <a href="#" class="dropdown-toggle">
                <i class="fa fa-building-columns"></i>
                <span>POP</span>
                <span class="fa fa-angle-down pull-right"></span>
              </a>
              <ul class="treeview-menu" style="display: none;">
                <!-- <li><a href="<?= route_to('reseller.subscription'); ?>"><i class="far fa-circle"></i> Subscription</a></li> -->
                <li><a href="<?= route_to('reseller.packages'); ?>"><i class="fa fa-boxes-stacked"></i>Customer Packages</a>
                </li>
                <li><a href="<?= route_to('route.reseller'); ?>"><i class="fa fa-building"></i> POP</a></li>
                <li><a href="<?= route_to('route.Reseller.payment'); ?>"><i class="fa fa-money-check-dollar"></i>Customer
                    Payments</a>
                </li>
                <li><a href="<?= route_to('route.reseller.funding'); ?>"><i class="fa fa-wallet"></i>POP Funding</a>
                </li>
                <li><a href="<?= route_to('route.reseller.transactionindex'); ?>"><i class="fa fa-receipt"></i>POP
                    Transactions</a></li>
              </ul>
            </li>
            <?php
          endif; ?>
          <?php
        endif; ?>




        <?php if (getSession('user_role') === 'admin'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Inventory</span></li>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-cart-shopping"></i>
              <span>Bandwidth Buy</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('bandwidth.item_index'); ?>"><i class="fa fa-sitemap"></i>Item</a></li>
              <li><a href="<?= route_to('bandwidth.category_index'); ?>"><i class="fa fa-tags"></i>Item Category</a>
              </li>
              <li><a href="<?= route_to('bandwidth.provider_index'); ?>"><i class="fa fa-handshake"></i>Provider</a>
              </li>
              <li><a href="<?= route_to('bandwidth.purchess'); ?>"><i class="fa fa-file-invoice"></i>Purchase Bill</a>
              </li>
            </ul>
          </li>
          <?php
        endif; ?>

        <?php if (getSession('user_role') === 'admin'): ?>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-shop"></i>
              <span>Bandwidth Sell</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('bandwidth.sell.index'); ?>"><i class="fa fa-user-tie"></i>Client</a></li>
              <li><a href="<?= route_to('bandwidth.sell.purchase_list'); ?>"><i class="fa fa-file-invoice-dollar"></i>Sales
                  Invoice</a>
              </li>
              <!-- <li><a href="<?= route_to('bandwidth.dailyindex'); ?>"><i class="far fa-circle"></i>Bill Collection</a>
            </li>
            <li><a href="<?= route_to('bandwidth.purchess'); ?>"><i class="far fa-circle"></i>Purchase Bill</a>
            </li> -->
            </ul>
          </li>
          <?php
        endif; ?>





        <?php if (getSession('user_role') === 'admin' && userHasPermission('inventory_purchess', 'read')): ?>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-truck-moving"></i>
              <span>Purchase</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('bandwidth.vendor_index'); ?>"><i class="fa fa-user-tag"></i>Vendor</a></li>
              <li><a href="<?= route_to('purchase.requisition_lists'); ?>"><i
                    class="fa fa-clipboard-list"></i>Requisition</a>
              </li>

              <li><a href="<?= route_to('purchase_bill.purchase_list'); ?>"><i class="fa fa-receipt"></i>Purchase bill</a>
              </li>
            </ul>
          </li>
          <?php
        endif; ?>
        <?php if (getSession('user_role') === 'admin' && userHasPermission('inventory_purchess', 'read')): ?>

          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-warehouse"></i>
              <span>Inventory</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('inventory.unit_index'); ?>"><i class="fa fa-ruler-combined"></i>Unit</a></li>
              <li><a href="<?= route_to('inventory.store_location'); ?>"><i class="fa fa-location-dot"></i>Store
                  location</a>
              </li>

              <li><a href="<?= route_to('inventory.category_index'); ?>"><i class="fa fa-list-ul"></i>Item Category</a>
              </li>
              <li><a href="<?= route_to('inventory.item_index'); ?>"><i class="fa fa-microchip"></i>Item</a></li>

              <li><a href="<?= route_to('inventory.purchess_stock'); ?>"><i class="fa fa-cubes"></i>Stock</a>
              </li>
            </ul>
          </li>
          <?php
        endif; ?>
        <?php if (in_array(getSession('user_role'), ['admin']) && userHasPermission('reports', 'read')): ?>

          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-chart-line"></i>
              <span>Reports</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('route.reports.btrc'); ?>"><i class="fa fa-ruler-combined"></i>BTRC report</a></li>

            </ul>
          </li>
          <?php
        endif; ?>



        <?php if (userHasPermission('hotspot') && $currentRole !== 'user'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Operations</span></li>
          <li class="<?= ($uri->getSegment(1) === 'hotspot') ? 'active' : ''; ?>">
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-wifi"></i>
              <span>Hotspot</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('route.hotspot.dashboard'); ?>"><i class="fa fa-gauge-high"></i>Hotspot
                  Dashboard</a>
              </li>

              <li><a href="<?= route_to('route.hotspot.user_profiles'); ?>"><i class="fa fa-box-open"></i>Hotspot
                  packages</a> </li>
              <li><a href="<?= route_to('route.hotspot.users'); ?>"><i class="fa fa-users"></i>Hotspot Users</a> </li>
              <li><a href="<?= route_to('hotspot.report'); ?>"><i class="fa fa-chart-bar"></i>Reports</a> </li>

            </ul>
          </li>
          <?php
        endif; ?>


        <?php if (userHasPermission('olt') && $currentRole !== 'user'): ?>

          <li class="<?= ($uri->getSegment(1) === 'olt') ? 'active' : ''; ?>">

            <a href="<?= route_to('olt.list'); ?>"><i class="fa fa-gauge-high"></i>Olt's</a>


          </li>

          <?php
        endif; ?>

        <?php if (getSession('user_role') === 'admin'): ?>

          <li class="<?= ($uri->getSegment(1) === 'routers') ? 'active' : ''; ?>">

            <a href="<?= route_to('route.routers'); ?>">
              <i class="fa fa-server"></i>
              <span>Mikrotik Routers</span>
            </a>
          </li>

          <?php
        endif; ?>

        <?php if (getSession('user_role') === 'admin' && userHasPermission('inventory_purchess', 'read')): ?>
          <li>
            <a href="#" class="dropdown-toggle">
              <i class="fa fa-network-wired"></i>
              <span>Network</span>
              <span class="fa fa-angle-down pull-right"></span>
            </a>
            <ul class="treeview-menu" style="display: none;">
              <li><a href="<?= route_to('network.diagram'); ?>"><i class="fa fa-diagram-project"></i>Diagram</a> </li>
              <li><a href="<?= route_to('network.map'); ?>"><i class="fa fa-map-location-dot"></i>Mapping</a> </li>
            </ul>
          </li>
          <?php
        endif; ?>

        <?php if (userHasPermission('sms_message')): ?>

          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Comms</span></li>
          <li class="<?= ($uri->getSegment(1) === 'sms' && $uri->getSegment(2) === '') ? 'active' : ''; ?>">

            <a href="<?= route_to('route.sms'); ?>">
              <i class="fa fa-paper-plane"></i>
              <span>SMS</span>
            </a>
          </li>

          <?php
        endif; ?>

        <?php if (userHasPermission('sms_message')): ?>

          <li class="<?= ($uri->getSegment(1) === 'sms' && $uri->getSegment(2) === 'sms_Tamplates') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.sms_Tamplates'); ?>">
              <i class="fa fa-envelope-open-text"></i>
              <span>SMS Templates</span>
            </a>
          </li>
          <?php
        endif; ?>

        <?php if (userHasPermission('sms_message')): ?>

          <li class="<?= ($uri->getSegment(1) === 'voice-sms') ? 'active' : ''; ?>">

            <a href="<?= route_to('route.voice-sms'); ?>">
              <i class="fa fa-microphone"></i>
              <span>Voice SMS</span>
            </a>
          </li>

          <?php
        endif; ?>


        <?php if (userHasPermission('referral')): ?>
          <li class="<?= ($uri->getSegment(1) === 'reward-center') ? 'active' : ''; ?>">
            <a href="<?= base_url('reward-center'); ?>">
              <i class="fa fa-gift"></i>
              <span>Referral &amp; Reward</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if (userHasPermission('support_ticket')): ?>
          <li class="<?= ($uri->getSegment(1) === 'support-tickets') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.ticket'); ?>">
              <i class="fa fa-ticket"></i>
              <span>Support Tickets</span>
              <?php if ((countUnseenTicket() > 0) && (getSession('user_role') != 'user')): ?>
                <span class="pull-right-container">
                  <span class="badge bg-red pull-right">
                    <?= countUnseenTicket(); ?>
                  </span>
                </span>
              <?php endif; ?>
            </a>
          </li>
        <?php endif; ?>

        <?php if (userHasPermission('recycle_bin')): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">System</span></li>
          <li class="<?= ($uri->getSegment(1) === 'recycle-bin') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.recycle_bin'); ?>">
              <i class="fa fa-trash-can"></i>
              <span>Recycle Bin</span>
            </a>
          </li>
        <?php endif; ?>



        <?php if (userHasPermission('software_settings')): ?>
          <li class="<?= ($uri->getSegment(2) === 'software') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.settings.software'); ?>">
              <i class="fa fa-gears"></i>
              <span>Software Settings</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
          <li class="<?= ($uri->getSegment(1) === 'user-access') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.useraccess'); ?>">
              <i class="fa fa-user-lock"></i>
              <span>User Access Management</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole === 'admin' || getSession('status') === 'inactive'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Billing</span></li>
          <li class="<?= ($uri->getSegment(2) === 'packages') ? 'active' : ''; ?>">
            <a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-table-list"></i> <span>Admin's Packages</span></a>
          </li>
        <?php elseif ($currentRole === 'user' || getSession('status') === 'inactive'): ?>
          <li class="<?= ($uri->getSegment(2) === 'packages') ? 'active' : ''; ?>">
            <a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-table-list"></i> <span>User's Packages</span></a>
          </li>
        <?php endif; ?>

        <?php if (in_array($currentRole, ['admin', 'resellerAdmin'], true) || getSession('status') === 'inactive'): ?>
          <li>
            <a
              href="<?= route_to('route.Admin.subscription', (int) session()->get('user_id') ?: (int) session()->get('id') ?: 0); ?>">
              <i class="fa fa-bolt"></i>
              <span>Self Recharge</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole === 'admin'): ?>
          <li class="<?= ($uri->getSegment(1) === 'wallet') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.wallet'); ?>">
              <i class="fa fa-wallet"></i>
              <span>My Wallet</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole === 'user'): ?>
          <li class="ipb-nav-section-li" aria-hidden="true"><span class="ipb-nav-section">Comms</span></li>
          <li class="<?= ($uri->getSegment(1) === 'my-rewards') ? 'active' : ''; ?>">
            <a href="<?= base_url('my-rewards'); ?>">
              <i class="fa fa-gift"></i>
              <span>Referrals &amp; Rewards</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole === 'user'): ?>
          <li class="<?= ($uri->getSegment(1) === 'news') ? 'active' : ''; ?>">
            <a href="<?= site_url('news'); ?>">
              <i class="fa fa-newspaper"></i>
              <span>News & Notices</span>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($currentRole !== 'employee' && (userHasPermission('payment') || getSession('status') === 'inactive')): ?>
          <li class="<?= ($uri->getSegment(1) === 'payment') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.payment'); ?>">
              <i class="fa fa-money-bill-transfer"></i>
              <span>My Payment</span>
            </a>
          </li>
        <?php endif; ?>

        <?php endif; ?>
        <?php if (userHasPermission('profile_update')): ?>

          <li class="<?= ($uri->getSegment(1) === 'profile') ? 'active' : ''; ?>">

            <a href="<?= route_to('route.profile'); ?>">
              <i class="fa fa-user"></i>
              <span>My Profile</span>
            </a>
          </li>

          <?php
        endif; ?>
         <li class="<?= ($uri->getSegment(1) === 'theme-studio') ? 'active' : ''; ?>">
          <a href="<?= route_to('route.theme.studio'); ?>">
            <i class="fa fa-palette"></i>
            <span>Theme Studio</span>
          </a>
        </li>

        <?php if (userHasPermission('password_change')): ?>

          <li class="<?= ($uri->getSegment(1) === 'change-password') ? 'active' : ''; ?>">

            <a href="<?= route_to('route.cngpass'); ?>">
              <i class="fa fa-lock"></i>
              <span>Change Password</span>
            </a>
          </li>

          <?php
        endif; ?>

        <?php if (isRedisInspectorViewer()): ?>
          <li class="<?= ($uri->getSegment(1) === 'system' && $uri->getSegment(2) === 'redis-cache') ? 'active' : ''; ?>">
            <a href="<?= route_to('route.redis_inspector'); ?>">
              <i class="fa fa-database"></i>
              <span>Redis Cache</span>
            </a>
          </li>
        <?php endif; ?>

        <li data-no-pin="1">
          <a href="<?= route_to('route.logout'); ?>" data-full-reload="1">
            <i class="fa fa-sign-out"></i>
            <span>Logout</span>
          </a>
        </li>

      </ul>
    <?php endif; ?>
  </section>

  <div class="ipb-sidebar-foot">
    <button type="button" class="ipb-icon-btn" id="ipbSidebarCollapse" title="Collapse sidebar" aria-label="Collapse sidebar">
      <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
    </button>
  </div>
</aside>

<script type="application/json" id="ipbSidebarPinData"><?= json_encode([
  'toggleUrl' => route_to('route.sidebar.pins.toggle'),
  'pinnedKeys' => array_column($sidebarPins, 'pin_key'),
  'maxPins' => \App\Models\SidebarPinModel::MAX_PINS_PER_USER,
]); ?></script>

<?php /* Parser-blocking BY DESIGN, and it must stay right here — directly after the
         sidebar markup and before the page content. The menu ships closed and
         scrolled to the top; this opens the active section and restores the scroll
         position while the sidebar is parsed but not yet painted. Deferring it (or
         moving it down with the other bundles) puts that work after first paint,
         which is exactly the flip it exists to remove. */ ?>
<?= saas_js('sidebar-boot.js') ?>