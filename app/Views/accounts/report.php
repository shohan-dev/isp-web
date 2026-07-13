<?= $this->extend('layout/main-layout'); ?>

<?= $this->section('css'); ?>
<link rel="stylesheet" href="<?= base_url('assets/css/saas/accounts-pages.css?v=2'); ?>">
<?= $this->endSection('css'); ?>

<?= $this->section('content'); ?>

<style>
    .ipb-acc-report .loading-overlay {
        position: fixed;
        inset: 0;
        background: color-mix(in srgb, var(--surface) 82%, transparent);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: var(--z-overlay, 1095);
        backdrop-filter: blur(4px);
    }

    .ipb-acc-report .loading-spinner {
        width: 44px;
        height: 44px;
        border: 3px solid var(--border);
        border-top-color: var(--primary-500, #f75803);
        border-radius: 50%;
        animation: ipbAccSpin 0.8s linear infinite;
    }

    @keyframes ipbAccSpin {
        to { transform: rotate(360deg); }
    }
</style>

<div class="content-wrapper">
    <section class="content ipb-saas-list ipb-acc-report">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <?= $this->include('components/page-header', [
      'title' => $page_title ?? 'Accounts Report',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Accounting'],
        ['label' => $breadcrumb_active ?? 'Accounts Report'],
      ],
    ]); ?>

    <form id="dateRangeForm" method="GET" action="<?= current_url() ?>" class="ipb-acc-filters">
        <div class="ipb-acc-field">
            <label for="fromDate">From date</label>
            <input type="date"
                name="from_date"
                class="form-control"
                value="<?= esc($from_date) ?>"
                id="fromDate"
                max="<?= date('Y-m-d') ?>">
        </div>
        <div class="ipb-acc-field">
            <label for="toDate">To date</label>
            <input type="date"
                name="to_date"
                class="form-control"
                value="<?= esc($to_date) ?>"
                id="toDate"
                max="<?= date('Y-m-d') ?>">
        </div>
        <div class="ipb-acc-filter-actions">
            <button type="submit" class="btn btn-primary" id="searchBtn">
                <i class="fa fa-search" aria-hidden="true"></i> Search
            </button>
            <button type="button" class="btn btn-default" id="resetBtn">
                <i class="fa fa-refresh" aria-hidden="true"></i> Reset
            </button>
        </div>
    </form>

    <div class="ipb-acc-report-grid">
        <div class="ipb-acc-panel">
            <div class="ipb-acc-panel-head">
                <i class="fa fa-arrow-trend-up" aria-hidden="true"></i> Income details
            </div>
            <div class="ipb-acc-panel-body">
                <div class="ipb-acc-line">
                    <a href="<?= route_to('route.customer.payment'); ?>">Customers payment received</a>
                    <strong><?= number_format($customers_payment_received, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line">
                    <a href="<?= route_to('bandwidth.sell.purchase_list'); ?>">Bandwidth sell</a>
                    <strong><?= number_format($Band_sell, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line">
                    <a href="<?= route_to('otc.report'); ?>">OTC</a>
                    <strong><?= number_format($totalOtc, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line">
                    <a href="<?= route_to('route.income.list'); ?>">Other income</a>
                    <strong><?= number_format($other_income, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line is-total is-success">
                    <span>Total income</span>
                    <strong><?= number_format($total_income, 2) ?> ৳</strong>
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
                    <strong><?= number_format($EmployeePayment, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line">
                    <a href="<?= route_to('bandwidth.purchess'); ?>">Bandwidth buy</a>
                    <strong><?= number_format($Band_buy, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line">
                    <a href="<?= route_to('route.expense.list'); ?>">Other expenses</a>
                    <strong><?= number_format($other_expenses, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-line is-total is-danger">
                    <span>Total expense</span>
                    <strong><?= number_format($total_expense, 2) ?> ৳</strong>
                </div>
            </div>
        </div>
    </div>

    <div class="ipb-acc-panel">
        <div class="ipb-acc-panel-head">
            <i class="fa fa-clock" aria-hidden="true"></i>
            Period summary (<?= esc($from_date) ?> – <?= esc($to_date) ?>)
        </div>
        <div class="ipb-acc-panel-body">
            <div class="ipb-acc-kpi-grid">
                <div class="ipb-acc-kpi is-success">
                    <span>Total income</span>
                    <strong><?= number_format($period_total_income, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-kpi is-danger">
                    <span>Total expenses</span>
                    <strong><?= number_format($period_total_expenses, 2) ?> ৳</strong>
                </div>
                <div class="ipb-acc-kpi is-info">
                    <span>Current amount</span>
                    <strong><?= number_format($period_current_amount, 2) ?> ৳</strong>
                </div>
            </div>
        </div>
    </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('dateRangeForm');
        const fromDate = document.getElementById('fromDate');
        const toDate = document.getElementById('toDate');
        const resetBtn = document.getElementById('resetBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const searchBtn = document.getElementById('searchBtn');

        // Set default dates if not set
        if (!fromDate.value) {
            const firstDay = new Date();
            firstDay.setDate(1);
            fromDate.value = firstDay.toISOString().split('T')[0];
        }

        if (!toDate.value) {
            toDate.value = new Date().toISOString().split('T')[0];
        }

        // Validate dates
        function validateDates() {
            const from = new Date(fromDate.value);
            const to = new Date(toDate.value);

            if (from > to) {
                alert('From date cannot be greater than to date');
                return false;
            }

            if (to > new Date()) {
                alert('To date cannot be in the future');
                toDate.value = new Date().toISOString().split('T')[0];
                return false;
            }

            return true;
        }

        // Handle form submission
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (validateDates()) {
                // Show loading overlay
                loadingOverlay.style.display = 'flex';
                searchBtn.disabled = true;

                // Submit the form
                form.submit();
            }
        });

        // Handle reset button
        resetBtn.addEventListener('click', function() {
            // Set to first day of current month
            const firstDay = new Date();
            firstDay.setDate(1);
            fromDate.value = firstDay.toISOString().split('T')[0];

            // Set to today
            toDate.value = new Date().toISOString().split('T')[0];

            // Auto submit after reset
            if (validateDates()) {
                loadingOverlay.style.display = 'flex';
                searchBtn.disabled = true;
                form.submit();
            }
        });

        // Prevent future dates in date inputs
        fromDate.addEventListener('change', function() {
            if (new Date(this.value) > new Date()) {
                alert('From date cannot be in the future');
                this.value = new Date().toISOString().split('T')[0];
            }
            validateDates();
        });

        toDate.addEventListener('change', function() {
            if (new Date(this.value) > new Date()) {
                alert('To date cannot be in the future');
                this.value = new Date().toISOString().split('T')[0];
            }
            validateDates();
        });

        // Hide loading overlay when page is fully loaded
        window.addEventListener('load', function() {
            loadingOverlay.style.display = 'none';
            searchBtn.disabled = false;
        });

        // Handle browser back/forward buttons
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                loadingOverlay.style.display = 'none';
                searchBtn.disabled = false;
            }
        });
    });
</script>

<?= $this->endSection(); ?>