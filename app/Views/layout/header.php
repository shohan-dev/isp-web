<header class="main-header">
  <a href="<?= route_to('route.dashboard'); ?>" class="logo" style="display:none" aria-hidden="true">
    <span class="logo-mini">
      <img src="<?= base_url('assets/img/logo/' . getSetting('app_logo')); ?>" alt="" />
    </span>
    <span class="logo-lg">
      <img src="<?= base_url('assets/img/logo/' . getSetting('app_logo')); ?>" alt="" />
    </span>
  </a>

  <nav class="navbar navbar-static-top" role="navigation" aria-label="Top bar">
    <a href="#" class="sidebar-toggle" id="ipbMenuToggle" role="button" aria-label="Toggle navigation">
      <span class="sr-only">Toggle navigation</span>
      <i class="fa fa-bars" aria-hidden="true"></i>
    </a>

   

    <!-- 06 §2.2 — derived from the sidebar's own active path by saas.js buildTopCrumbs() -->
    <nav class="ipb-topcrumbs" id="ipbTopCrumbs" aria-label="Breadcrumb" hidden></nav>

    <button type="button" class="ipb-palette-trigger hide-mobile" data-ipb-palette title="Search pages">
      <i class="fa fa-search" aria-hidden="true"></i>
      Search or jump to...
      <kbd>⌘K</kbd>
    </button>
    <button type="button" class="ipb-icon-btn hide-desktop" data-ipb-palette title="Search" aria-label="Search pages" style="margin-left:8px">
      <i class="fa fa-search" aria-hidden="true"></i>
    </button>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">
        <li class="hidden-xs">
          <div id="google_translate_element"></div>
        </li>

        <li>
          <button type="button" class="toggle-btn" data-ipb-open-theme title="Theme Studio" aria-label="Open Theme Studio">
            <i class="fa-solid fa-palette" aria-hidden="true"></i>
          </button>
        </li>

        <li>
          <button type="button" class="toggle-btn" id="darkToggle" title="Toggle theme" aria-label="Toggle dark mode">
            <i class="fa-solid fa-moon" aria-hidden="true"></i>
          </button>
        </li>

        <?php if ((getSession('user_role') != 'user') && userHasPermission('support_ticket')): ?>
          <?php $unseenTickets = (int) countUnseenTicket(); ?>
          <li class="dropdown messages-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-label="Support tickets<?= $unseenTickets ? ' (' . $unseenTickets . ' new)' : ''; ?>">
              <i class="fa fa-envelope" aria-hidden="true"></i>
              <span class="label label-danger<?= $unseenTickets ? '' : ' is-zero'; ?>">
                <?= $unseenTickets; ?>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li class="header text-center">
                <?= $unseenTickets > 0 ? 'You have ' . $unseenTickets . ' new support request' . ($unseenTickets > 1 ? 's' : '') : 'No new support requests'; ?>
              </li>
              <li>
                <ul class="menu">
                  <?php if ($unseenTickets > 0):
                    foreach (getUnseenTickets() as $ticket): ?>
                      <li>
                        <a href="<?= route_to('route.ticket.details', $ticket->id); ?>">
                          <div class="pull-left">
                            <img src="<?= base_url('assets/img/icon/avatar.png'); ?>" class="img-circle" alt="">
                          </div>
                          <h5>
                            <?= getUserById($ticket->user_id)->name ?? '--'; ?>
                            <small class="pull-right">
                              <i class="fa fa-clock-o" aria-hidden="true"></i> <?= date('d-m-y', strtotime($ticket->datetime)); ?>
                            </small>
                          </h5>
                          <p><?= esc($ticket->subject); ?></p>
                        </a>
                      </li>
                    <?php endforeach;
                  else: ?>
                    <li>
                      <a href="javascript:void(0)">
                        <p>No new support requests</p>
                      </a>
                    </li>
                  <?php endif; ?>
                </ul>
              </li>
              <li class="footer">
                <a href="<?= route_to('route.ticket'); ?>">View All</a>
              </li>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (in_array(getSession('user_role'), ['admin', 'super_admin'])): ?>
          <?php
            $pendingFreeCount = countPendingFreeRequests(getSession('user_id'));
            $pendingFreeRequests = getPendingFreeRequests(getSession('user_id'));
          ?>
          <li class="dropdown notifications-menu">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-label="Free user requests">
              <i class="fa fa-bell" aria-hidden="true"></i>
              <span class="label label-warning<?= $pendingFreeCount > 0 ? '' : ' is-zero'; ?>" style="position: absolute; top: 9px; right: 7px;">
                <?= (int) $pendingFreeCount; ?>
              </span>
            </a>
            <ul class="dropdown-menu">
              <li class="header text-center" style="padding: 10px; font-weight: bold; border-bottom: 1px solid var(--border);">
                <?= $pendingFreeCount > 0
                  ? 'You have ' . (int) $pendingFreeCount . ' pending free user request' . ($pendingFreeCount > 1 ? 's' : '')
                  : 'No pending free user requests'; ?>
              </li>
              <li>
                <ul class="menu" style="max-height: 200px; overflow-y: auto; list-style: none; padding: 0; margin: 0;">
                  <?php if ($pendingFreeCount > 0 && !empty($pendingFreeRequests)): ?>
                    <?php foreach ($pendingFreeRequests as $req): ?>
                      <li style="border-bottom: 1px solid var(--border);">
                        <a href="<?= route_to('route.customer.free_requests'); ?>" style="display: block; padding: 10px; color: var(--text-primary);">
                          <i class="fa fa-user" style="margin-right: 5px; color: var(--primary-500);"></i>
                          <?= esc($req['customer_name']); ?> (by <?= esc($req['reseller_name']); ?>)
                        </a>
                      </li>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <li>
                      <a href="javascript:void(0)" style="display: block; padding: 10px; color: var(--text-secondary); text-align: center;">
                        No pending requests
                      </a>
                    </li>
                  <?php endif; ?>
                </ul>
              </li>
              <li class="footer text-center" style="border-top: 1px solid var(--border); padding: 10px 0;">
                <a href="<?= route_to('route.customer.free_requests'); ?>">View All Requests</a>
              </li>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (getSession('user_role') === 'super_admin'): ?>
          <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-label="Admin sections">
              <i class="fa fa-user-shield" aria-hidden="true"></i>
              <span class="hidden-xs">Admin</span>
            </a>
            <ul class="dropdown-menu">
              <li><a href="<?= route_to('route.tenants'); ?>"><i class="fa fa-globe"></i> Tenant Portals</a></li>
              <li><a href="<?= route_to('route.tenants.create'); ?>"><i class="fa fa-plus-circle"></i> Create Portal</a></li>
              <li><a href="<?= route_to('route.Admin'); ?>"><i class="fa fa-user-lock"></i> Second Admins</a></li>
              <li><a href="<?= route_to('Admin.packages'); ?>"><i class="fa fa-box"></i> Admin Packages</a></li>
              <li><a href="<?= route_to('route.Admin.revenue'); ?>"><i class="fa fa-chart-line"></i> Platform Revenue</a></li>
              <li><a href="<?= route_to('route.contact.fetch'); ?>"><i class="fa fa-address-book"></i> Contact Info's</a></li>
            </ul>
          </li>
        <?php endif; ?>

        <?php if (getSession('user_role') === 'resellerAdmin'): ?>
          <li class="user user-menu ipb-fund-balance">
            <a href="javascript:void(0);" title="POP fund balance">
              <i class="fa-solid fa-coins" style="margin-right: 2px;" aria-hidden="true"></i>
              <span class="ipb-fund-amount"><?= getfund() ?? '0'; ?>৳</span>
            </a>
          </li>
        <?php endif; ?>

        <!-- 06 §2.2 — user name + logout were a bare link and an orphan icon
             with no menu; consolidated into one Bootstrap dropdown so account
             actions live in one place. -->
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img src="<?= base_url('assets/img/icon/avatar.png'); ?>" class="user-image" alt="">
            <span class="hidden-xs"><?= esc(getUserById(getSession('user_id'))->name ?? ''); ?></span>
            <i class="fa fa-angle-down" aria-hidden="true" style="margin-left:4px"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
            <li><a href="<?= route_to('route.profile'); ?>"><i class="fa fa-user" aria-hidden="true"></i> My Profile</a></li>
            <li><a href="<?= route_to('route.cngpass'); ?>"><i class="fa fa-lock" aria-hidden="true"></i> Change Password</a></li>
            <li><a href="#" data-ipb-open-theme><i class="fa-solid fa-palette" aria-hidden="true"></i> Theme Studio</a></li>
            <li class="divider"></li>
            <li><a href="<?= route_to('route.logout'); ?>"><i class="fa fa-sign-out" aria-hidden="true"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </nav>
</header>
