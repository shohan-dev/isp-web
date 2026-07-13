<?php
/**
 * Theme Studio controls (shared by drawer + Software Settings tab).
 * Presentation-only; state in localStorage via customize.js.
 * Inputs use data-* only (no name=) so they never submit with server forms.
 *
 * @var string $layout  'drawer' (default) | 'page'
 */
$layout = isset($layout) ? (string) $layout : 'drawer';
$isPage = $layout === 'page';
?>
<div class="ipb-theme-grid<?= $isPage ? ' ipb-theme-grid--page' : ''; ?>" data-theme-studio-root>

  <?php if ($isPage): ?>
  <div class="ipb-theme-page-cols">
    <div class="ipb-theme-card">
      <div class="ipb-theme-card-head">
        <strong>Brand colors</strong>
        <span>Pick any color — a full 50–900 ramp and dark-mode variant generate automatically</span>
      </div>
      <div class="ipb-theme-card-body">
  <?php endif; ?>

  <div class="ipb-theme-field">
    <label>Primary</label>
    <div class="ipb-theme-color-row">
      <label class="ipb-theme-swatch">
        <input type="color" data-theme-primary-color value="#f75803" aria-label="Primary color" />
      </label>
      <input type="text" class="ipb-theme-hex" data-theme-primary-hex value="#f75803" spellcheck="false" aria-label="Primary hex" />
    </div>
    <div class="ipb-theme-ramp" data-theme-primary-ramp></div>
  </div>

  <div class="ipb-theme-field">
    <label>Secondary</label>
    <div class="ipb-theme-color-row">
      <label class="ipb-theme-swatch">
        <input type="color" data-theme-secondary-color value="#001f55" aria-label="Secondary color" />
      </label>
      <input type="text" class="ipb-theme-hex" data-theme-secondary-hex value="#001f55" spellcheck="false" aria-label="Secondary hex" />
    </div>
    <div class="ipb-theme-ramp" data-theme-secondary-ramp></div>
  </div>

  <div class="ipb-theme-field">
    <label>Corner radius</label>
    <div class="ipb-radius-opts">
      <button type="button" data-radius="4"><span class="dot" style="border-radius:4px"></span> Sharp</button>
      <button type="button" data-radius="8"><span class="dot" style="border-radius:8px"></span> Soft</button>
      <button type="button" data-radius="12" class="active"><span class="dot" style="border-radius:12px"></span> Rounded</button>
      <button type="button" data-radius="20"><span class="dot" style="border-radius:20px"></span> Pill</button>
    </div>
  </div>

  <div class="ipb-theme-note">
    <i class="fa fa-circle-info" aria-hidden="true"></i>
    <span>Success, warning and error colors stay fixed — red always means trouble regardless of brand.</span>
  </div>

  <?php if ($isPage): ?>
      </div>
    </div>

    <div class="ipb-theme-card">
      <div class="ipb-theme-card-head">
        <strong>Live preview</strong>
        <span>Updates instantly — this is what your team will see</span>
      </div>
      <div class="ipb-theme-card-body">
  <?php endif; ?>

  <?php if (!$isPage): ?>
  <div class="ipb-theme-field">
    <label>Live preview</label>
  <?php endif; ?>
    <div class="ipb-theme-preview" aria-hidden="true">
      <div class="ipb-theme-preview-btns">
        <span class="btn-p">Primary action</span>
        <span class="btn-s">Secondary</span>
        <span class="btn-o">Outline</span>
      </div>
      <div class="ipb-theme-preview-badges">
        <span class="badge-brand">Brand</span>
        <span class="badge-ok">Online</span>
        <span class="badge-warn">Due</span>
      </div>
      <div class="ipb-theme-preview-stat">
        <small>Payment received</small>
        <strong>৳30,820</strong>
      </div>
      <div class="ipb-theme-preview-hero">
        <small>SECONDARY GRADIENT</small>
        <strong>Hero panels &amp; receipts</strong>
      </div>
    </div>
  <?php if (!$isPage): ?>
  </div>
  <?php endif; ?>

  <?php if ($isPage): ?>
      </div>
    </div>
  </div>

  <div class="ipb-theme-card">
    <div class="ipb-theme-card-head">
      <strong>Display preferences</strong>
      <span>Density, type scale, sidebar and motion — saved on this device</span>
    </div>
    <div class="ipb-theme-card-body ipb-theme-prefs-row">
  <?php endif; ?>

  <div class="ipb-theme-field">
    <label>UI density</label>
    <div class="ipb-radius-opts" data-density-opts>
      <button type="button" data-density="comfortable" class="active">Comfortable</button>
      <button type="button" data-density="compact">Compact</button>
    </div>
  </div>

  <div class="ipb-theme-field">
    <label>Table density</label>
    <div class="ipb-radius-opts" data-table-density-opts>
      <button type="button" data-table-density="comfortable" class="active">Comfortable</button>
      <button type="button" data-table-density="compact">Compact</button>
    </div>
  </div>

  <div class="ipb-theme-field">
    <label>Text size</label>
    <div class="ipb-radius-opts" data-font-opts>
      <button type="button" data-font-scale="sm">Small</button>
      <button type="button" data-font-scale="md" class="active">Default</button>
      <button type="button" data-font-scale="lg">Large</button>
    </div>
  </div>

  <div class="ipb-theme-field">
    <label class="ipb-theme-check">
      <input type="checkbox" data-theme-dark-mode />
      <span>Dark mode</span>
    </label>
  </div>

  <div class="ipb-theme-field">
    <label class="ipb-theme-check">
      <input type="checkbox" data-theme-sidebar-compact />
      <span>Compact sidebar (icons-first)</span>
    </label>
  </div>

  <div class="ipb-theme-field">
    <label class="ipb-theme-check">
      <input type="checkbox" data-theme-reduce-motion />
      <span>Reduce motion (less animation)</span>
    </label>
  </div>

  <?php if ($isPage): ?>
    </div>
  </div>

  <div class="ipb-theme-card">
    <div class="ipb-theme-card-head">
      <strong>Theme templates</strong>
      <span>Professionally paired presets — click to apply instantly</span>
    </div>
    <div class="ipb-theme-card-body">
  <?php else: ?>
  <div class="ipb-theme-field">
    <label>Theme templates</label>
  <?php endif; ?>

    <div class="ipb-theme-presets" data-theme-presets></div>

  <?php if ($isPage): ?>
    </div>
  </div>

  <div class="ipb-theme-card">
    <div class="ipb-theme-card-head">
      <strong>Export / import</strong>
      <span>Download a JSON file, upload one, or copy to clipboard</span>
    </div>
    <div class="ipb-theme-card-body">
  <?php else: ?>
  </div>

  <div class="ipb-theme-field">
    <label>Export / import</label>
  <?php endif; ?>

    <div class="ipb-theme-io">
      <button type="button" class="btn btn-primary btn-sm" data-theme-export-file>
        <i class="fa fa-download" aria-hidden="true"></i> Export JSON file
      </button>
      <button type="button" class="btn btn-default btn-sm" data-theme-import-file>
        <i class="fa fa-upload" aria-hidden="true"></i> Import JSON file
      </button>
      <input type="file" class="ipb-theme-file-input" data-theme-file-input accept="application/json,.json" hidden>
      <button type="button" class="btn btn-default btn-sm" data-theme-copy>
        <i class="fa fa-copy" aria-hidden="true"></i> Copy JSON
      </button>
      <button type="button" class="btn btn-default btn-sm" data-theme-reset>
        <i class="fa fa-rotate-left" aria-hidden="true"></i> Reset default
      </button>
    </div>
    <p class="ipb-theme-io-hint">Export downloads <code>ipb-brand-theme.json</code>. Import accepts the same file from another browser or device.</p>

  <?php if ($isPage): ?>
    </div>
  </div>
  <?php else: ?>
  </div>
  <?php endif; ?>

</div>
