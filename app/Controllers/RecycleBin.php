<?php

namespace App\Controllers;

use App\Models\RecycleBinModel;
use App\Services\TrashService;
use Config\Trash;

class RecycleBin extends BaseController
{
    protected RecycleBinModel $binModel;
    protected Trash $trashConfig;

    public function __construct()
    {
        $this->binModel    = model(RecycleBinModel::class);
        $this->trashConfig = config('Trash');
        helper(['user', 'utility']);
    }

    public function index()
    {
        $tenantId = $this->tenantId();
        $entity   = $this->request->getGet('entity');
        $from     = $this->request->getGet('from');
        $to       = $this->request->getGet('to');
        $perPage  = (int) ($this->request->getGet('per_page') ?? 25);

        $entityLabels = [];
        foreach ($this->trashConfig->entities as $key => $def) {
            $entityLabels[$key] = ucwords(str_replace('_', ' ', $key));
        }

        $data = [
            'title'        => 'Recycle Bin',
            'items'        => $this->binModel->getForTenant($tenantId, $entity, $from, $to, $perPage),
            'pager'        => $this->binModel->pager,
            'entity'       => $entity,
            'from'         => $from,
            'to'           => $to,
            'perPage'      => $perPage,
            'entityLabels' => $entityLabels,
        ];

        return view('recyclebin/index', $data);
    }

    public function restore()
    {
        $ids = getRawInput('ids');
        if (empty($ids) || !is_array($ids)) {
            return requestResponse('error', 'Nothing is selected', 400);
        }

        $tenantId = $this->tenantId();
        $service  = new TrashService();
        $restored = 0;

        foreach ($ids as $id) {
            if ($service->restore((int) $id, $tenantId)) {
                $restored++;
            }
        }

        if ($restored > 0) {
            return requestResponse('success', "{$restored} item(s) restored successfully", 200);
        }

        return requestResponse('error', 'Restore failed — item may not exist or ID already in use', 400);
    }

    public function deleteForever()
    {
        $ids = getRawInput('ids');
        if (empty($ids) || !is_array($ids)) {
            return requestResponse('error', 'Nothing is selected', 400);
        }

        $tenantId = $this->tenantId();
        $service  = new TrashService();
        $deleted  = 0;

        foreach ($ids as $id) {
            if ($service->deleteForever((int) $id, $tenantId)) {
                $deleted++;
            }
        }

        if ($deleted > 0) {
            return requestResponse('success', "{$deleted} item(s) permanently deleted", 200);
        }

        return requestResponse('error', 'Nothing was deleted', 400);
    }

    public function emptyTrash()
    {
        $count = (new TrashService())->emptyTrash($this->tenantId());

        if ($count > 0) {
            return requestResponse('success', "{$count} item(s) permanently deleted", 200);
        }

        return requestResponse('success', 'Recycle bin is already empty', 200);
    }

    private function tenantId(): int
    {
        return (int) getSAdminIdForUser((int) session()->get('user_id'));
    }
}
