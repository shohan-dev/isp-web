/**
 * Shared helpers for pages that populate a list/table via a custom fetch()
 * call instead of DataTables' serverSide pipeline (e.g. Hotspot's live
 * router-backed pages). Mirrors two existing, already-documented contracts
 * so these pages read as the same product instead of a one-off style:
 *   - components.css "05 §8 — skeleton -> content crossfade" (.ipb-skel-swap)
 *   - components/empty-state.php's .ipb-empty[.is-error] markup
 */
(function (global) {
  'use strict';

  function escHtml(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function ensureSkeletonRealContainer(wrapperEl) {
    var real = wrapperEl.querySelector(':scope > .ipb-real');
    if (!real) {
      real = document.createElement('div');
      real.className = 'ipb-real';
      wrapperEl.appendChild(real);
    }
    return real;
  }

  function revealFromSkeleton(wrapperEl) {
    if (!wrapperEl || !wrapperEl.classList.contains('ipb-skel-swap') || wrapperEl.classList.contains('is-ready')) return;
    wrapperEl.classList.add('is-ready');
    setTimeout(function () {
      var sk = wrapperEl.querySelector(':scope > .ipb-skeleton');
      if (sk) sk.remove();
    }, 220);
  }

  function swapSkeletonHtml(elId, html) {
    var wrapperEl = document.getElementById(elId);
    if (!wrapperEl) return;
    ensureSkeletonRealContainer(wrapperEl).innerHTML = html;
    revealFromSkeleton(wrapperEl);
  }

  // Shimmer <tr> rows for a plain <tbody> populated by JS — same varied-width
  // heuristic as components/skeleton-table.php so first paint reads as
  // content, not a grid of identical bars.
  var SKELETON_WIDTHS = [70, 55, 62, 48, 66, 52, 58, 60, 45, 64];
  function skeletonRowsHtml(cols, rows) {
    cols = Math.max(1, cols | 0);
    rows = Math.max(1, rows | 0);
    var out = '';
    for (var r = 0; r < rows; r++) {
      out += '<tr class="ipb-skeleton-row" aria-hidden="true">';
      for (var c = 0; c < cols; c++) {
        var w = SKELETON_WIDTHS[(r + c) % SKELETON_WIDTHS.length];
        out += '<td><span class="ipb-skeleton ipb-skeleton-text" style="width:' + w + '%"></span></td>';
      }
      out += '</tr>';
    }
    return out;
  }

  // Inline empty/error state built as an HTML string — same markup contract
  // as components/empty-state.php (variant 'empty' | 'error'), for the AJAX
  // .catch()/.fail() handlers that can't call the server-rendered PHP
  // component. A plain empty result ("no rows yet") is NOT an error — only
  // variant:'error' gets the red tone and the alert role.
  function emptyStateHtml(opts) {
    opts = opts || {};
    var isError = opts.variant === 'error';
    var icon = opts.icon || (isError ? 'fa fa-triangle-exclamation' : 'fa fa-inbox');
    var title = opts.title || (isError ? 'Something went wrong' : 'Nothing here yet');
    var subtitle = opts.subtitle || (isError ? 'We could not load this. Check your connection and try again.' : '');
    var retry = opts.retry; // e.g. "fetchUsers()" — a page-local function call
    // No icon here on purpose: every other .ipb-empty-action button in the app
    // (customers/allnew.php, payments/customer/all.php, etc.) is text-only.
    // These error/empty states render inside a table <td> on 3 of the 4
    // Hotspot pages, and ux.css's `table td .btn-sm:has(i)` rule force-collapses
    // any icon+text .btn-sm there into a fixed 32x32 icon-only box — the text
    // then overflows the box instead of sitting beside the icon.
    var action = retry
      ? '<div class="ipb-empty-action"><button type="button" class="btn btn-primary btn-sm" onclick="' + retry + '">Retry</button></div>'
      : '';
    return '<div class="ipb-empty' + (isError ? ' is-error' : '') + '"' + (isError ? ' role="alert"' : '') + '>' +
      '<div class="ipb-empty-icon"><i class="' + icon + '" aria-hidden="true"></i></div>' +
      '<div class="ipb-empty-title">' + escHtml(title) + '</div>' +
      (subtitle ? '<div class="ipb-empty-sub">' + escHtml(subtitle) + '</div>' : '') +
      action +
      '</div>';
  }

  global.escHtml = global.escHtml || escHtml;
  global.ensureSkeletonRealContainer = ensureSkeletonRealContainer;
  global.revealFromSkeleton = revealFromSkeleton;
  global.swapSkeletonHtml = swapSkeletonHtml;
  global.ipbSkeletonRowsHtml = skeletonRowsHtml;
  global.ipbErrorStateHtml = function (opts) { return emptyStateHtml(Object.assign({ variant: 'error' }, opts)); };
  global.ipbEmptyStateHtml = function (opts) { return emptyStateHtml(Object.assign({ variant: 'empty' }, opts)); };
})(window);
