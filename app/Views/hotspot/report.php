<?= $this->extend('layout/main-layout'); ?>
<?= $this->section('content'); ?>

<div class="content-wrapper">

    <!-- HEADER -->
    <!-- CONTENT -->
    <section class="content ipb-saas-list">

        
    <?= view('components/page-header', [
      'title' => 'Hotspot Selling Report',
      'breadcrumb' => [
        ['label' => 'Dashboard', 'url' => route_to('route.dashboard')],
        ['label' => 'Hotspot Selling Report'],
      ],
    ]); ?>

<div class="box box-warning">
            <div class="box-header with-border ipb-box-toolbar">
                <div class="ipb-list-toolbar">
                  <div class="ipb-list-toolbar-filters">
                    <form class="ipb-filter-form" onsubmit="return false;">
                      <div class="ipb-filter-field" style="flex: 1 1 200px;">
                        <label for="searchInput">Search</label>
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="Username / profile / comment">
                      </div>
                      <div class="ipb-filter-field">
                        <label for="dayFilter">Day</label>
                        <select id="dayFilter" class="form-control">
                            <option value="">All</option>
                            <?php for ($i = 1; $i <= 31; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                      </div>
                      <div class="ipb-filter-field">
                        <label for="monthFilter">Month</label>
                        <select id="monthFilter" class="form-control">
                            <option value="">All</option>
                            <?php
                            $months = [
                                'January', 'February', 'March', 'April', 'May', 'June',
                                'July', 'August', 'September', 'October', 'November', 'December',
                            ];
                            foreach ($months as $m): ?>
                                <option value="<?= strtolower($m) ?>"><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="ipb-filter-field">
                        <label for="yearFilter">Year</label>
                        <select id="yearFilter" class="form-control">
                            <?php for ($y = (int) date('Y'); $y >= 2023; $y--): ?>
                                <option value="<?= $y ?>" <?= $y === (int) date('Y') ? 'selected' : '' ?>>
                                    <?= $y ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                      </div>
                      <div class="ipb-filter-actions">
                        <button type="button" id="filterBtn" class="btn btn-primary">
                            <i class="fa fa-filter" aria-hidden="true"></i> Filter
                        </button>
                      </div>
                    </form>
                  </div>
                  <div class="ipb-list-toolbar-actions">
                        <button type="button" class="btn btn-default btn-sm"><i class="fa fa-question-circle" aria-hidden="true"></i> Help</button>
                        <button type="button" class="btn btn-success btn-sm"><i class="fa fa-file-csv" aria-hidden="true"></i> CSV</button>
                        <button type="button" class="btn btn-primary btn-sm"><i class="fa fa-search" aria-hidden="true"></i> All</button>
                        <button type="button" class="btn btn-primary btn-sm"><i class="fa fa-print" aria-hidden="true"></i> Print</button>
                        <button type="button" class="btn btn-danger btn-sm"><i class="fa fa-trash" aria-hidden="true"></i> Delete</button>
                  </div>
                </div>
            </div>

            <!-- TABLE -->
            <div class="box-body">

                <div class="table-responsive">
          <table class="table table-bordered table-striped" width="100%" id="reportTable">
                    <caption class="sr-only">Hotspot selling report</caption>
                    <thead>
                        <tr>
                            <th scope="col">No</th>
                            <th scope="col">Date</th>
                            <th scope="col">Time</th>
                            <th scope="col">Username</th>
                            <th scope="col">Profile</th>
                            <th scope="col">Comment</th>
                            <th scope="col" class="text-right">Price</th>
                        </tr>
                    </thead>
                    <?= view('components/skeleton-table', ['cols' => 7, 'rows' => 8]) ?>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-right">Total</th>
                            <th class="text-right" id="totalPrice">0</th>
                        </tr>
                    </tfoot>
                </table>
        </div>

            </div>
        </div>

    </section>
</div>

<script>
    const HOTSPOT_CTX_KEY = 'hotspot_ctx';

    // Get saved context
    function getCtx() {
        try {
            return JSON.parse(localStorage.getItem(HOTSPOT_CTX_KEY));
        } catch {
            return null;
        }
    }

    const ctx = getCtx(); // get saved hotspot context
    const routerId = ctx?.router_id || '';

    let reportData = [];

    function loadReport() {

        const day = document.getElementById('dayFilter')?.value || '';
        const month = document.getElementById('monthFilter')?.value || '';
        const year = document.getElementById('yearFilter')?.value || '';

        const url = `<?= route_to('hotspot.report.data'); ?>` +
            `?router_id=${encodeURIComponent(routerId)}` +
            `&day=${encodeURIComponent(day)}` +
            `&month=${encodeURIComponent(month)}` +
            `&year=${encodeURIComponent(year)}`;

        const tbody = document.querySelector('#reportTable tbody');
        tbody.innerHTML = ipbSkeletonRowsHtml(7, 6);

        fetch(url)
            .then(res => res.json())
            .then(res => {

                if (!res.success) {
                    tbody.innerHTML = `<tr><td colspan="7">${ipbErrorStateHtml({
                        title: 'Could not load report',
                        subtitle: res.error || 'Failed to load report.',
                        retry: 'loadReport()'
                    })}</td></tr>`;
                    return;
                }

                reportData = res.rows;
                document.getElementById('totalPrice').innerText = res.total;
                renderTable(reportData);
            })
            .catch(err => {
                tbody.innerHTML = `<tr><td colspan="7">${ipbErrorStateHtml({
                    title: 'Could not load report',
                    subtitle: err.message,
                    retry: 'loadReport()'
                })}</td></tr>`;
            });
    }

    function renderTable(rows) {

        const tbody = document.querySelector('#reportTable tbody');
        tbody.innerHTML = '';

        if (!rows.length) {
            tbody.innerHTML = `<tr><td colspan="7">${ipbEmptyStateHtml({
                icon: 'fa fa-receipt',
                title: 'No sales yet',
                subtitle: 'Selling report rows will show up here once vouchers are sold.'
            })}</td></tr>`;
            return;
        }

        rows.forEach(row => {
            tbody.innerHTML += `
          <tr>
            <td>${escHtml(row.no)}</td>
            <td>${escHtml(row.date)}</td>
            <td>${escHtml(row.time)}</td>
            <td>${escHtml(row.username)}</td>
            <td>${escHtml(row.profile)}</td>
            <td>${escHtml(row.comment)}</td>
            <td class="text-right">${escHtml(row.price)}</td>
          </tr>
        `;
        });
    }

    // CLIENT-SIDE SEARCH
    document.getElementById('searchInput').addEventListener('keyup', function() {

        const keyword = this.value.toLowerCase();

        const filtered = reportData.filter(r =>
            r.username.toLowerCase().includes(keyword) ||
            r.profile.toLowerCase().includes(keyword) ||
            r.comment.toLowerCase().includes(keyword)
        );

        renderTable(filtered);
    });

    // FILTER BUTTON
    document.getElementById('filterBtn').addEventListener('click', function() {
        loadReport();
    });

    // AUTO LOAD ON PAGE OPEN
    document.addEventListener('DOMContentLoaded', function() {
        loadReport();
    });
</script>

<?= $this->endSection(); ?>