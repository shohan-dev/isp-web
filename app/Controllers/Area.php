<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserRouterDataModel;
use App\Models\User;
use App\Libraries\DataTables;

class Area extends BaseController
{

    protected $area_model, $user_model;

    public function __construct()
    {
        /**
         * Area Model
         */
        $this->area_model = model('App\Models\Area');
        $this->user_model = model('App\Models\User');
    }


    /**
     * Resolve the effective owner id (admin/reseller) for the current session.
     * Employees act on behalf of their admin (admin_id); everyone else uses their
     * own session user_id.
     */
    private function resolveOwnerId(): int
    {
        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');
        if ($userRole === 'employee') {
            $details = $this->user_model->where(['id' => $userId])->first();
            return (int) $details->admin_id;
        }
        return (int) $userId;
    }

    /**
     * Full area tree (areas owned by $ownerId) with sub-area and customer counts,
     * computed via correlated subqueries to avoid row fan-out from joining two
     * separate 1-to-many relations.
     */
    private function buildAreaTree(int $ownerId): array
    {
        $rows = $this->area_model->builder()
            ->select("areas.*, (SELECT COUNT(*) FROM sub_areas sa WHERE sa.user_id = areas.id) as sub_area_count, (SELECT COUNT(*) FROM users u WHERE u.area_id = areas.id) as customer_count")
            ->orderBy('areas.area_name', 'asc')
            ->where('areas.user_id', $ownerId)
            ->get()->getResult();
        return $rows;
    }

    /**
     * Aggregate stats derived from an already-fetched area tree.
     */
    private function buildAreaStats(array $areas): array
    {
        $total = count($areas);
        $active = 0; $subTotal = 0; $withoutSub = 0;
        foreach ($areas as $a) {
            if ($a->status === 'active') $active++;
            $subTotal += (int) $a->sub_area_count;
            if ((int) $a->sub_area_count === 0) $withoutSub++;
        }
        return ['total_areas' => $total, 'active_areas' => $active, 'total_sub_areas' => $subTotal, 'areas_without_subareas' => $withoutSub];
    }

    /**
     * Area
     * @action: All Area View
     */
    public function index()
    {
        $ownerId = $this->resolveOwnerId();
        $areas = $this->buildAreaTree($ownerId);
        $stats = $this->buildAreaStats($areas);

        return view('areas/all', ['title' => 'Service Area', 'areas' => $areas, 'stats' => $stats, 'preselectAreaId' => null]);
    }

    public function subindex($id)
    {
        $ownerId = $this->resolveOwnerId();
        $owned = $this->area_model->where(['id' => $id, 'user_id' => $ownerId])->first();
        if (empty($owned)) {
            show_404();
        }

        $areas = $this->buildAreaTree($ownerId);
        $stats = $this->buildAreaStats($areas);

        return view('areas/all', ['title' => 'Service Area', 'areas' => $areas, 'stats' => $stats, 'preselectAreaId' => (int) $id]);
    }

    /**
     * Area
     * @action: Full area tree + stats (AJAX refresh)
     */
    public function treeData()
    {
        $ownerId = $this->resolveOwnerId();
        $areas = $this->buildAreaTree($ownerId);
        $stats = $this->buildAreaStats($areas);

        return $this->response->setJSON(['areas' => $areas, 'stats' => $stats]);
    }

    /**
     * Area
     * @action: Sub-areas for a single owned area (lazy load)
     */
    public function subTree($areaId)
    {
        $ownerId = $this->resolveOwnerId();
        $area = $this->area_model->builder()
            ->select("areas.*, (SELECT COUNT(*) FROM sub_areas sa WHERE sa.user_id = areas.id) as sub_area_count, (SELECT COUNT(*) FROM users u WHERE u.area_id = areas.id) as customer_count")
            ->where(['areas.id' => $areaId, 'areas.user_id' => $ownerId])
            ->get()->getFirstRow();
        if (empty($area)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'response' => 'Area not found']);
        }

        $subarea_model = model('App\Models\AreaSub');
        $subAreas = $subarea_model->where('user_id', $areaId)->orderBy('area_name', 'asc')->findAll();

        return $this->response->setJSON(['area' => $area, 'subAreas' => $subAreas]);
    }


    /**
     * Area
     * @action: Fetch Areas
     */
    public function fetch()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $ownerId = $this->resolveOwnerId();

        $data = $this->area_model->builder()
            ->select('*')
            ->where('user_id', $ownerId)
            ->orderBy('id', 'desc');

        $statusFilter = $this->request->getPost('status_filter');
        if ($statusFilter === 'active' || $statusFilter === 'inactive') {
            $data->where('status', $statusFilter);
        }

        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('area', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        // Modern Deep Blue Bold Area Name
        $datatables->addColumn('area_name', function ($row) {
            return '<span class="package-name-link">' . esc($row->area_name) . '</span>';
        });
        $datatables->addColumn('area_code', function ($row) {
            return '<span class="badge-duration">' . esc($row->area_code) . '</span>';
        });

        // Modern Status Design
        $datatables->format('status', function ($value) {
            $class = ($value === 'active') ? 'active' : 'inactive';
            return '<span class="status ' . $class . '">' . ucfirst($value) . '</span>';
        });

        // Row actions — icon buttons with a tooltip label (matches the .ipb-row-actions
        // pattern used across Customers/etc). The previous "Update"/"Sub-areas" text
        // buttons used .btn-sm with a visible label, which a global compact-icon rule
        // in ux.css (targets any table .btn-sm containing an <i>) collapses to a fixed
        // 32px box — the label text then overflowed and visually overlapped the next
        // button instead of being cleanly hidden the way this icon-only pattern is.
        if (userHasPermission('area', 'update')) {
            $datatables->addColumn('action', function ($row) {
                $html = '<div class="ipb-row-actions">';
                $html .= '<a href="' . route_to('route.area.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update area" data-toggle="tooltip" data-placement="top">'
                    . '<i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Update</span></a>';
                $html .= '<a href="' . route_to('route.subarea', $row->id) . '" class="ipb-row-btn tone-info" title="Sub-areas" data-toggle="tooltip" data-placement="top">'
                    . '<i class="fa fa-sitemap" aria-hidden="true"></i><span class="sr-only">Sub-areas</span></a>';
                $html .= '</div>';

                return $html;
            });
        }





        $datatables->except(['id']);

        $datatables->asObject();

        $datatables->generate();
    }


    public function fetchsub()
    {
        $id = $this->request->getPost('id');
        $ownerId = $this->resolveOwnerId();
        $subarea_model = model('App\Models\AreaSub');

        $owned = $this->area_model->where(['id' => $id, 'user_id' => $ownerId])->first();

        $data = $subarea_model->builder()
            ->select('*')
            ->where('user_id', $id)
            ->orderBy('id', 'desc');

        if (empty($owned)) {
            // Not this owner's area — short-circuit to an empty result set
            // without leaking whether the area id exists for someone else.
            $data->where('1', '0');
        }

        $datatables = new DataTables($data);

        $datatables->addSequenceNumber('serial');

        if (userHasPermission('area', 'delete')) {

            $datatables->addColumn('select', function ($row) {

                return '<input type="checkbox" class="form-check-input input-check-selected" value="' . $row->id . '">';
            });
        }

        $datatables->format('status', function ($value) {

            return ($value === 'active') ? '<span class="badge label-success">Active</span>' : '<span class="badge label-danger">Inactive</span>';
        });

        if (userHasPermission('area', 'update')) {

            $datatables->addColumn('action', function ($row) {
                return '<div class="ipb-row-actions">'
                    . '<a href="' . route_to('route.subarea.edit', $row->id) . '" class="ipb-row-btn tone-brand" title="Update sub-area" data-toggle="tooltip" data-placement="top">'
                    . '<i class="far fa-pen-to-square" aria-hidden="true"></i><span class="sr-only">Update</span></a>'
                    . '</div>';
            });
        }

        $datatables->except(['id']);

        $datatables->asObject();

        $datatables->generate();
    }


    /**
     * Area
     * @action: New Area View
     */
    public function new()
    {
        $data = [
            'title' => 'New Area',
        ];

        return view('areas/new', $data);
    }

    public function subnew($id)
    {
        // $id = $this->request->getGet('id');

        $data = [
            'title' => 'New SubArea',
            'id' => $id,
        ];

        return view('areas/newsub', $data);
    }

    /**
     * Area
     * @action: New Area Create
     */
    public function create()
    {
        $this->validate([
            'area_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area name',
                    'is_unique' => 'Another area already exists in this name'
                ]
            ],
            // |is_unique[areas.area_name]
            // |is_unique[areas.area_code]
            'area_code' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area code',
                    'is_unique' => 'Another area already exists in this code'
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select area status',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $userId = session()->get('user_id');
            $userRole = session()->get('user_role');

            if ($userRole === 'employee') {
                $userModel = model('App\Models\User');
                $details = $userModel->where(['id' => $userId])->first();
                $userId = $details->admin_id;
            }
            $data = [
                'user_id' => $userId,
                'area_name'     => getPostInput('area_name'),
                'area_code'     => getPostInput('area_code'),
                'status'        => getPostInput('status'),
            ];

            $result = $this->area_model->insert($data, false);

            if ($result) {
                bumpLookupCacheVersion((int) $userId);

                return requestResponse('success', "Service area added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    public function subcreate()
    {
        $this->validate([
            'area_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area name',
                ]
            ],
            'area_code' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area code',
                ]
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select area status',
                ]
            ],
        ]);

        if ($this->validation->run()) {
            $areaId = getPostInput('id');
            $owned = $this->area_model->where(['id' => $areaId, 'user_id' => $this->resolveOwnerId()])->first();
            if (empty($owned)) {
                return requestResponse('error', 'Area not found', 404);
            }

            $data = [
                'user_id' => $areaId,
                'area_name'     => getPostInput('area_name'),
                'area_code'     => getPostInput('area_code'),
                'status'        => getPostInput('status'),
            ];

            $subarea_model = model('App\Models\AreaSub');
            $result = $subarea_model->insert($data, false);

            if ($result) {

                return requestResponse('success', "Service area added successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Area
     * @action: Edit Area View
     */
    public function edit($id)
    {
        $details = $this->area_model->find($id);

        if (!empty($details)) {

            $data = [
                'title' => 'Update Service Area',
                'details' => $details,
            ];

            return view('areas/edit', $data);
        }

        show_404();
    }

    public function editsub($id)
    {
        $subarea_model = model('App\Models\AreaSub');
        $details = $subarea_model->find($id);

        if (!empty($details)) {
            $owned = $this->area_model->where(['id' => $details->user_id, 'user_id' => $this->resolveOwnerId()])->first();
            if (empty($owned)) {
                show_404();
            }

            $data = [
                'title' => 'Update Service Area',
                'details' => $details,
            ];

            return view('areas/editsub', $data);
        }

        show_404();
    }

    /**
     * Area
     * @action: Update Area
     */
    public function update($id)
    {
        $this->validate([
            'area_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area name',
                ],
            ],
            'area_code' => [
                // 'rules' => 'required|is_unique[areas.area_code, id, ' . $id . ']',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area code',
                    'is_unique' => 'Another area already exists in this code',
                ],
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select area status',
                ],
            ],
        ]);

        if ($this->validation->run()) {
            $area = $this->area_model->find($id);

            $data = [
                'area_name'     => getPostInput('area_name'),
                'area_code'     => getPostInput('area_code'),
                'status'        => getPostInput('status'),
            ];

            $result = $this->area_model->update($id, $data);

            if ($result) {
                if ($area !== null) {
                    bumpLookupCacheVersion((int) $area->user_id);
                }

                return requestResponse('success', "Service area updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }


    public function updatesub($id)
    {
        $this->validate([
            'area_name' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area name',
                ],
            ],
            'area_code' => [
                // 'rules' => 'required|is_unique[areas.area_code, id, ' . $id . ']',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Enter the area code',
                    'is_unique' => 'Another area already exists in this code',
                ],
            ],
            'status' => [
                'rules' => 'required',
                'errors' => [
                    'required' => 'Select area status',
                ],
            ],
        ]);

        if ($this->validation->run()) {

            $subarea_model = model('App\Models\AreaSub');
            $subarea = $subarea_model->find($id);

            if (empty($subarea)) {
                return requestResponse('error', 'Not found', 404);
            }

            $owned = $this->area_model->where(['id' => $subarea->user_id, 'user_id' => $this->resolveOwnerId()])->first();
            if (empty($owned)) {
                return requestResponse('error', 'Not found', 404);
            }

            $data = [
                'area_name'     => getPostInput('area_name'),
                'area_code'     => getPostInput('area_code'),
                'status'        => getPostInput('status'),
            ];

            $result = $subarea_model->update($id, $data);

            if ($result) {

                return requestResponse('success', "Service area updated successfully", 200);
            }

            return requestResponse('error', "Something went wrong! Please try again", 500);
        }

        return requestResponse('validation-error', $this->validation->getErrors(), 400);
    }

    /**
     * Area
     * @action: Delete Areas
     */
    public function delete()
    {
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {
            $areas = $this->area_model->whereIn('id', $ids)->findAll();

            $result = $this->area_model->whereIn('id', $ids)->delete();

            if ($result) {
                $ownerIds = array_unique(array_map(static fn ($row) => (int) $row->user_id, $areas));
                foreach ($ownerIds as $ownerId) {
                    bumpLookupCacheVersion($ownerId);
                }

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }

    public function deletesub()
    {
        $subarea_model = model('App\Models\AreaSub');
        $ids = getRawInput('ids');

        if (!empty($ids) && is_array($ids) && count($ids) > 0) {
            $ownerId = $this->resolveOwnerId();

            $subAreas = $subarea_model->whereIn('id', $ids)->findAll();

            $parentAreaIds = array_unique(array_map(static fn ($row) => (int) $row->user_id, $subAreas));
            $ownedParentAreaIds = [];
            if (!empty($parentAreaIds)) {
                $ownedParentAreaIds = array_column(
                    $this->area_model->whereIn('id', $parentAreaIds)->where('user_id', $ownerId)->findAll(),
                    'id'
                );
            }

            $ownedIds = [];
            foreach ($subAreas as $row) {
                if (in_array((int) $row->user_id, $ownedParentAreaIds, true)) {
                    $ownedIds[] = $row->id;
                }
            }

            if (empty($ownedIds)) {
                return requestResponse("error", "Nothing is selected", 400);
            }

            $result = $subarea_model->whereIn('id', $ownedIds)->delete();

            if ($result) {

                return requestResponse("success", "Selected records deleted successfully", 200);
            }

            return requestResponse("error", "Something went wrong! Please try again", 500);
        }

        return requestResponse("error", "Nothing is selected", 400);
    }
}
