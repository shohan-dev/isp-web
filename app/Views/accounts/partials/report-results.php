<?php
$net = (float) ($period_current_amount ?? 0);
$netTone = $netTone ?? ($net < 0 ? 'is-danger' : ($net > 0 ? 'is-success' : 'is-info'));
$periodLabel = $periodLabel ?? (esc($from_date) . ' — ' . esc($to_date));
?>

<div class="ipb-acc-report-meta">
  <span class="ipb-acc-period-chip">
    <i class="fa fa-calendar" aria-hidden="true"></i>
    Period <?= $periodLabel ?>
  </span>
</div>

<div class="ipb-acc-report-grid">
  <div class="ipb-acc-panel">
    <div class="ipb-acc-panel-head">
      <i class="fa fa-arrow-trend-up" aria-hidden="true"></i> Income details
    </div>
    <div class="ipb-acc-panel-body">
      <div class="ipb-acc-line">
        <a href="<?= route_to('route.customer.payment'); ?>">Customers payment received</a>
        <strong><?= number_format((float) $customers_payment_received, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line">
        <a href="<?= route_to('bandwidth.sell.purchase_list'); ?>">Bandwidth sell</a>
        <strong><?= number_format((float) $Band_sell, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line">
        <a href="<?= route_to('otc.report'); ?>">OTC</a>
        <strong><?= number_format((float) $totalOtc, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line">
        <a href="<?= route_to('route.income.list'); ?>">Other income</a>
        <strong><?= number_format((float) $other_income, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line is-total is-success">
        <span>Total income</span>
        <strong><?= number_format((float) $total_income, 2) ?> ৳</strong>
      </div>
    </div>
  </div>

  <div class="ipb-acc-panel">
    <div class="ipb-acc-panel-head">
      <i class="fa fa-arrow-trend-down" aria-hidden="true"></i> Expense details
    </div>
    <div class="ipb-acc-panel-body">
      <div class="ipb-acc-line">
        <a href="<?= route_to('route.employee.payment'); ?>">Employee payment</a>
        <strong><?= number_format((float) $EmployeePayment, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line">
        <a href="<?= route_to('bandwidth.purchess'); ?>">Bandwidth buy</a>
        <strong><?= number_format((float) $Band_buy, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line">
        <a href="<?= route_to('route.expense.list'); ?>">Other expenses</a>
        <strong><?= number_format((float) $other_expenses, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-line is-total is-danger">
        <span>Total expense</span>
        <strong><?= number_format((float) $total_expense, 2) ?> ৳</strong>
      </div>
    </div>
  </div>
</div>

<div class="ipb-acc-panel ipb-acc-summary-panel">
  <div class="ipb-acc-panel-head">
    <i class="fa fa-chart-pie" aria-hidden="true"></i>
    Period summary
    <span class="ipb-acc-panel-sub"><?= $periodLabel ?></span>
  </div>
  <div class="ipb-acc-panel-body">
    <div class="ipb-acc-kpi-grid">
      <div class="ipb-acc-kpi is-success">
        <span>Total income</span>
        <strong><?= number_format((float) $period_total_income, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-kpi is-danger">
        <span>Total expenses</span>
        <strong><?= number_format((float) $period_total_expenses, 2) ?> ৳</strong>
      </div>
      <div class="ipb-acc-kpi <?= esc($netTone, 'attr') ?> is-net">
        <span>Net amount</span>
        <strong><?= number_format($net, 2) ?> ৳</strong>
        <em><?= $net < 0 ? 'Expenses exceed income' : ($net > 0 ? 'Income exceeds expenses' : 'Balanced period') ?></em>
      </div>
    </div>
  </div>
</div>
