<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=4'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<?php
$net = (float) ($period_current_amount ?? 0);
$netTone = $net < 0 ? 'is-danger' : ($net > 0 ? 'is-success' : 'is-info');
$periodLabel = esc($from_date) . ' — ' . esc($to_date);
?>

<div class="content-wrapper">
  <section class="content ipb-saas-list ipb-acc-report" id="ipbAccReportPage">

    <?= $this->include('components/page-header', [
      'title' => $page_title ?? 'Accounts Report',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Accounting'],
        ['label' => $breadcrumb_active ?? 'Accounts Report'],
      ],
    ]); ?>

    <form id="dateRangeForm"
          method="GET"
          action="<?= esc(current_url(), 'attr') ?>"
          class="ipb-acc-filters"
          novalidate>
      <div class="ipb-acc-field">
        <label for="fromDate">From date</label>
        <input type="date"
               name="from_date"
               class="form-control"
               value="<?= esc($from_date) ?>"
               id="fromDate"
               max="<?= date('Y-m-d') ?>"
               required>
      </div>
      <div class="ipb-acc-field">
        <label for="toDate">To date</label>
        <input type="date"
               name="to_date"
               class="form-control"
               value="<?= esc($to_date) ?>"
               id="toDate"
               max="<?= date('Y-m-d') ?>"
               required>
      </div>
      <div class="ipb-acc-filter-actions">
        <button type="submit" class="btn btn-primary" id="searchBtn">
          <i class="fa fa-search" aria-hidden="true"></i>
          <span class="ipb-acc-btn-label">Search</span>
        </button>
        <button type="button" class="btn btn-default" id="resetBtn">
          <i class="fa fa-refresh" aria-hidden="true"></i> Reset
        </button>
      </div>
    </form>

    <div id="ipbAccReportResults" class="ipb-acc-report-results" aria-live="polite">
      <?= $this->include('accounts/partials/report-results', [
        'from_date' => $from_date,
        'to_date' => $to_date,
        'periodLabel' => $periodLabel,
        'customers_payment_received' => $customers_payment_received,
        'Band_sell' => $Band_sell,
        'totalOtc' => $totalOtc,
        'other_income' => $other_income,
        'total_income' => $total_income,
        'EmployeePayment' => $EmployeePayment,
        'Band_buy' => $Band_buy,
        'other_expenses' => $other_expenses,
        'total_expense' => $total_expense,
        'period_total_income' => $period_total_income,
        'period_total_expenses' => $period_total_expenses,
        'period_current_amount' => $period_current_amount,
        'netTone' => $netTone,
      ]); ?>
    </div>

  </section>
</div>

<?= $this->endSection('content'); ?>

<?= $this->section('script'); ?>
<script>
/**
 * Accounts Report — SPA-safe search.
 * Never paint a full-viewport blank overlay; refresh only the results panel.
 */
(function ($) {
  function todayIso() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    var day = String(d.getDate()).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-' + day;
  }

  function firstOfMonthIso() {
    var d = new Date();
    var m = String(d.getMonth() + 1).padStart(2, '0');
    return d.getFullYear() + '-' + m + '-01';
  }

  function validateDates(fromEl, toEl) {
    if (!fromEl.value || !toEl.value) {
      if (window.tata) tata.error('Missing dates', 'Choose both from and to dates.');
      return false;
    }
    var from = new Date(fromEl.value);
    var to = new Date(toEl.value);
    var today = new Date(todayIso());

    if (from > to) {
      if (window.tata) tata.error('Invalid range', 'From date cannot be after to date.');
      return false;
    }
    if (to > today) {
      toEl.value = todayIso();
    }
    if (from > today) {
      fromEl.value = firstOfMonthIso();
    }
    return true;
  }

  function buildUrl(form) {
    var params = new URLSearchParams();
    params.set('from_date', form.from_date.value);
    params.set('to_date', form.to_date.value);
    var base = form.getAttribute('action') || window.location.pathname;
    return base.split('?')[0] + '?' + params.toString();
  }

  function setBusy(busy) {
    var $page = $('#ipbAccReportPage');
    var $results = $('#ipbAccReportResults');
    var $btn = $('#searchBtn');
    var $reset = $('#resetBtn');

    $page.toggleClass('is-searching', !!busy);
    $results.attr('aria-busy', busy ? 'true' : 'false');
    $btn.prop('disabled', !!busy);
    $reset.prop('disabled', !!busy);

    if (busy) {
      $btn.data('ipbLabel', $btn.html());
      $btn.html('<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> <span class="ipb-acc-btn-label">Searching…</span>');
    } else if ($btn.data('ipbLabel')) {
      $btn.html($btn.data('ipbLabel'));
      $btn.removeData('ipbLabel');
    }
  }

  function extractResultsHtml(html) {
    var doc = new DOMParser().parseFromString(html, 'text/html');

    // SPA partial response (<template id="ipb-nav-content">…)
    var contentTpl = doc.getElementById('ipb-nav-content');
    if (contentTpl) {
      var fromTpl = contentTpl.content
        ? contentTpl.content.querySelector('#ipbAccReportResults')
        : null;
      if (!fromTpl) {
        var wrap = doc.createElement('div');
        wrap.innerHTML = contentTpl.innerHTML;
        fromTpl = wrap.querySelector('#ipbAccReportResults');
      }
      if (fromTpl) return fromTpl.innerHTML;
    }

    // Full document fallback
    var fromFull = doc.querySelector('#ipbAccReportResults');
    if (fromFull) return fromFull.innerHTML;

    return null;
  }

  var searchAbort = null;

  function runSearch(form, push) {
    var fromEl = form.querySelector('#fromDate');
    var toEl = form.querySelector('#toDate');
    if (!validateDates(fromEl, toEl)) return;

    var url = buildUrl(form);
    setBusy(true);

    if (searchAbort) {
      try { searchAbort.abort(); } catch (e) {}
    }
    searchAbort = typeof AbortController !== 'undefined' ? new AbortController() : null;

    fetch(url, {
      headers: {
        'X-IPB-Nav': '1',
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html'
      },
      credentials: 'same-origin',
      signal: searchAbort ? searchAbort.signal : undefined
    })
      .then(function (resp) {
        if (!resp.ok) throw new Error('Report request failed');
        return resp.text();
      })
      .then(function (html) {
        var next = extractResultsHtml(html);
        if (!next) throw new Error('Could not parse report response');

        var $results = $('#ipbAccReportResults');
        $results.addClass('is-refreshing');
        $results.html(next);
        // Force reflow then clear refresh cue
        void $results[0].offsetWidth;
        $results.removeClass('is-refreshing');

        if (push !== false && window.history && history.pushState) {
          history.pushState({ ipbAccReport: true }, '', url);
        }
      })
      .catch(function (err) {
        if (err && err.name === 'AbortError') return;
        if (window.tata) {
          tata.error('Search failed', 'Could not refresh the report. Try again.');
        }
      })
      .finally(function () {
        setBusy(false);
      });
  }

  window.ipbAccReportInit = function () {
    var form = document.getElementById('dateRangeForm');
    if (!form || form.dataset.ipbBound === '1') return;
    form.dataset.ipbBound = '1';

    var fromDate = document.getElementById('fromDate');
    var toDate = document.getElementById('toDate');
    var resetBtn = document.getElementById('resetBtn');

    if (fromDate && !fromDate.value) fromDate.value = firstOfMonthIso();
    if (toDate && !toDate.value) toDate.value = todayIso();

    $(form).off('submit.ipbAcc').on('submit.ipbAcc', function (e) {
      e.preventDefault();
      runSearch(form, true);
    });

    $(resetBtn).off('click.ipbAcc').on('click.ipbAcc', function () {
      fromDate.value = firstOfMonthIso();
      toDate.value = todayIso();
      runSearch(form, true);
    });

    $(fromDate).add(toDate).off('change.ipbAcc').on('change.ipbAcc', function () {
      validateDates(fromDate, toDate);
    });
  };

  // SPA injects after DOM ready — call immediately; $(fn) covers full reload.
  window.ipbAccReportInit();
  $(window.ipbAccReportInit);
})(jQuery);
</script>
<?= $this->endSection('script'); ?>
