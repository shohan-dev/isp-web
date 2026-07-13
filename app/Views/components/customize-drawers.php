<?php
/**
 * Dashboard Customize + Theme Studio drawers (JSX parity).
 * Presentation-only; state lives in localStorage via customize.js.
 */
?>
<div class="ipb-drawer-overlay" id="ipbDrawerOverlay" aria-hidden="true"></div>

<aside class="ipb-drawer-panel" id="ipbCustomizeDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ipbCustomizeTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="ipbCustomizeTitle">Customize Dashboard</div>
      <p class="ipb-drawer-sub">Show what matters, resize and reorder the rest</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body" data-customize-list></div>
  <div class="ipb-drawer-footer">
    <button type="button" class="ipb-btn-block" data-customize-reset>
      <i class="fa fa-rotate-left" aria-hidden="true"></i> Reset to default
    </button>
  </div>
</aside>

<aside class="ipb-drawer-panel wide" id="ipbThemeDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ipbThemeTitle">
  <div class="ipb-drawer-header">
    <div>
      <div class="ipb-drawer-title" id="ipbThemeTitle">Theme Studio</div>
      <p class="ipb-drawer-sub">Pick brand colors — a full ramp applies across the app</p>
    </div>
    <button type="button" class="ipb-icon-btn" data-ipb-drawer-close aria-label="Close">
      <i class="fa fa-times" aria-hidden="true"></i>
    </button>
  </div>
  <div class="ipb-drawer-body">
    <?= $this->include('components/theme-studio-panel'); ?>
  </div>
  <div class="ipb-drawer-footer">
    <button type="button" class="ipb-btn-block" data-theme-reset>
      <i class="fa fa-rotate-left" aria-hidden="true"></i> Reset to default
    </button>
  </div>
</aside>
