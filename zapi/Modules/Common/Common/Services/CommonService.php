<?php

namespace Zapi\Modules\Common\Common\Services;

use App\Models\MovieServersModel;
use App\Models\News_notice;
use Zapi\Core\Base\BaseApiController;
use Zapi\Core\Support\Auth\JwtToken;

class CommonService extends BaseApiController
{
    protected $payment_model, $News_notice, $user_model, $movieModel;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->payment_model = model('App\Models\Payment');
        $this->movieModel = new MovieServersModel();
        $this->News_notice = new News_notice();
        $this->user_model = model('App\Models\User');
    }

    public function pppoeExpiryCheck()
    {
        $pppoeName = trim((string) $this->request->getGet('name'));
        $token = trim((string) $this->request->getGet('token'));

        $expectedToken = trim((string) getenv('MIKROTIK_API_TOKEN'));
        if ($expectedToken !== '' && !hash_equals($expectedToken, $token)) {
            return $this->response->setStatusCode(403)->setContentType('text/plain')->setBody('DENY');
        }
        if ($pppoeName === '') {
            return $this->response->setStatusCode(400)->setContentType('text/plain')->setBody('ERROR|MISSING_NAME');
        }

        $routerDataModel = model('App\Models\UserRouterDataModel');
        $userModel = model('App\Models\User');
        $packageModel = model('App\Models\Package');

        $user = $routerDataModel
            ->select('users.id as user_id, users.admin_id, users.package_id, users.created_by, users.status as account_status, users.subscription_status, users.conn_status, users.will_expire')
            ->join('users', 'users.id = user_router_data.user_id', 'inner')
            ->where('users.role', 'user')
            ->where('user_router_data.pppoe_secret', $pppoeName)
            ->first();

        if (empty($user)) {
            $user = $userModel
                ->select('id as user_id, admin_id, package_id, created_by, status as account_status, subscription_status, conn_status, will_expire')
                ->where('role', 'user')
                ->where('pppoe_id', $pppoeName)
                ->first();
        }
        if (empty($user)) {
            return $this->response->setStatusCode(200)->setContentType('text/plain')->setBody('UNKNOWN|' . base_url(route_to('route.auth.login')));
        }

        $willExpireTs = !empty($user->will_expire) ? strtotime((string) $user->will_expire) : null;
        $isExpired = (is_int($willExpireTs) && $willExpireTs <= time())
            || ($user->subscription_status === 'inactive')
            || ($user->account_status !== 'active');
        if (!$isExpired) {
            return $this->response->setStatusCode(200)->setContentType('text/plain')->setBody('ACTIVE');
        }

        $pendingPayment = $this->payment_model
            ->select('id')
            ->where([
                'user_id' => $user->user_id,
                'paidby' => $user->user_id,
                'user_type' => 'user',
                'status' => 'pending',
                'month' => date('F'),
            ])->orderBy('id', 'DESC')->first();

        if (empty($pendingPayment)) {
            $amount = 0;
            if ($user->created_by === 'resellerAdmin') {
                $amount = (float) (ResellerPackagePrice($user->package_id, null, $user->admin_id, 'resellerAdmin') ?? 0);
            } else {
                $package = $packageModel->select('price')->where('id', $user->package_id)->first();
                $amount = (float) (($package->price ?? 0));
            }
            $this->payment_model->insert([
                'user_id' => $user->user_id,
                'admin_id' => $user->admin_id,
                'paidby' => $user->user_id,
                'user_type' => 'user',
                'invoice' => 'INV-' . random_int(100000, 999999),
                'amount' => $amount,
                'month' => date('F'),
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $pendingPaymentId = (int) $this->payment_model->getInsertID();
        } else {
            $pendingPaymentId = (int) $pendingPayment->id;
        }

        $paymentUrl = $pendingPaymentId > 0
            ? base_url('api/customer/make-payment/' . $pendingPaymentId)
            : base_url(route_to('route.auth.login'));
        return $this->response->setStatusCode(200)->setContentType('text/plain')->setBody('EXPIRED|' . $paymentUrl);
    }

    public function movie_index()
    {
        $tokenUserId = $this->resolveAccessTokenUserId();
        if ($tokenUserId === null) {
            return $this->respondError('Unauthorized', 401, 'UNAUTHORIZED');
        }

        $requestUserId = (int) ($this->request->getGet('user_id') ?? 0);
        if ($requestUserId > 0 && $requestUserId !== $tokenUserId) {
            return $this->respondError('Forbidden', 403, 'FORBIDDEN');
        }

        $admin_id = $this->resolveMovieAdminIdForUser($tokenUserId);
        if ($admin_id === null) {
            return $this->respondError('admin_id is required', 400, 'REQUEST_FAILED');
        }

        $servers = $this->movieModel->getAllServers($admin_id);
        $pager = $this->getPaginationParams();
        $totalFound = count($servers);
        $paged = array_slice($servers, $pager['offset'], $pager['limit']);
        return $this->respondSuccess([
            'data' => $paged,
            'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($paged)),
        ]);
    }

    public function add()
    {
        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $imageName = null;
        if ($imageFile && $imageFile->isValid()) {
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/movies/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }
        $adminId = $this->request->getPost('admin_id');
        if (empty($adminId)) {
            $tokenUserId = $this->resolveAccessTokenUserId();
            $adminId = $tokenUserId !== null ? $this->resolveMovieAdminIdForUser($tokenUserId) : null;
        }
        if (empty($adminId)) {
            return $this->respondError('admin_id is required', 400, 'REQUEST_FAILED');
        }
        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'rating' => $this->request->getPost('rating'),
            'admin_id' => $adminId,
            'image' => $imageName,
        ];
        if ($this->movieModel->addServer($data)) {
            return $this->respondSuccess(['message' => 'Movie server added']);
        }
        return $this->respondError('Failed to add server', 400, 'REQUEST_FAILED');
    }

    public function update($id)
    {
        helper(['form']);
        $server = $this->movieModel->getServerById($id);
        if (!$server) {
            return $this->respondError('Server not found', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanManageMovieServer($server)) {
            return $this->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        $imageFile = $this->request->getFile('image');
        $imageName = $server['image'];
        if ($imageFile && $imageFile->isValid()) {
            if (!empty($imageName) && file_exists(FCPATH . 'assets/movies/' . $imageName)) {
                unlink(FCPATH . 'assets/movies/' . $imageName);
            }
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/movies/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }
        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'rating' => $this->request->getPost('rating'),
            'image' => $imageName,
        ];
        if ($this->movieModel->updateServer($id, $data)) {
            return $this->respondSuccess(['message' => "Server ID {$id} updated"]);
        }
        return $this->respondError('Failed to update server', 400, 'REQUEST_FAILED');
    }

    public function view($id = null)
    {
        if (!$id) {
            return $this->respondError('ID is required', 400, 'REQUEST_FAILED');
        }
        $movie = $this->movieModel->getServerById($id);
        if (!$movie) {
            return $this->respondError('Movie not found', 404, 'REQUEST_FAILED');
        }
        if (!$this->actorCanManageMovieServer($movie)) {
            return $this->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        return $this->respondSuccess($movie);
    }

    public function delete($id)
    {
        $server = $this->movieModel->getServerById($id);
        if (!$server) {
            return $this->respondError('Server not found', 400, 'REQUEST_FAILED');
        }
        if (!$this->actorCanManageMovieServer($server)) {
            return $this->respondError('Forbidden', 403, 'FORBIDDEN');
        }
        if (!empty($server->image) && file_exists(WRITEPATH . 'assets/movies//' . $server->image)) {
            unlink(WRITEPATH . 'assets/movies//' . $server->image);
        }
        if ($this->movieModel->deleteServer($id)) {
            return $this->respondSuccess(['message' => "Server ID {$id} deleted"]);
        }
        return $this->respondError('Failed to delete server', 400, 'REQUEST_FAILED');
    }

    public function news_index()
    {
        $user_id = $this->request->getGet('user_id');
        if (empty($user_id)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        $details = $this->user_model->where(['id' => $user_id])->first();
        if (!$details) {
            return $this->respondError('User not found', 404, 'REQUEST_FAILED');
        }
        $admin_id = $details->admin_id ?? null;
        if ($details->created_by === 'resellerAdmin') {
            $details = $this->user_model->where(['id' => $admin_id])->first();
            $admin_id = $details->admin_id ?? null;
        }
        if ($details->role === 'admin') {
            $admin_id = $user_id;
        }
        if (!$admin_id) {
            return $this->respondError('admin_id is required', 400, 'REQUEST_FAILED');
        }
        $servers = $this->News_notice->getAllServers($admin_id);
        $pager = $this->getPaginationParams();
        $totalFound = count($servers);
        $paged = array_slice($servers, $pager['offset'], $pager['limit']);
        return $this->respondSuccess([
            'data' => $paged,
            'pagination' => $this->buildPaginationMeta($totalFound, $pager['page'], $pager['limit'], count($paged)),
        ]);
    }

    public function news_add()
    {
        helper(['form']);
        $imageFile = $this->request->getFile('image');
        $imageName = null;
        if ($imageFile && $imageFile->isValid()) {
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/news/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }
        $newsAdminId = $this->request->getPost('admin_id');
        if (empty($newsAdminId)) {
            return $this->respondError('admin_id is required', 400, 'REQUEST_FAILED');
        }
        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'opened' => 'false',
            'admin_id' => $newsAdminId,
            'image' => $imageName,
        ];
        if ($this->News_notice->addServer($data)) {
            return $this->respondSuccess(['message' => 'news server added']);
        }
        return $this->respondError('Failed to add news', 400, 'REQUEST_FAILED');
    }

    public function news_view_update($id)
    {
        if ($this->News_notice->updateServer($id, ['opened' => ''])) {
            return $this->respondSuccess(['message' => "news ID {$id} viewed"]);
        }
        return $this->respondError('Failed to update news viewed status', 400, 'REQUEST_FAILED');
    }

    public function news_update($id)
    {
        helper(['form']);
        $server = $this->News_notice->getServerById($id);
        if (!$server) {
            return $this->respondError('news not found', 400, 'REQUEST_FAILED');
        }
        $imageFile = $this->request->getFile('image');
        $imageName = $server['image'];
        if ($imageFile && $imageFile->isValid()) {
            if (!empty($imageName) && file_exists(FCPATH . 'assets/news/' . $imageName)) {
                unlink(FCPATH . 'assets/news/' . $imageName);
            }
            $imageName = $imageFile->getRandomName();
            $uploadPath = FCPATH . 'assets/news/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            $imageFile->move($uploadPath, $imageName);
        }
        $data = [
            'name' => $this->request->getPost('name'),
            'url' => $this->request->getPost('url'),
            'details' => $this->request->getPost('details'),
            'image' => $imageName,
        ];
        if ($this->News_notice->updateServer($id, $data)) {
            return $this->respondSuccess(['message' => "news ID {$id} updated"]);
        }
        return $this->respondError('Failed to update news', 400, 'REQUEST_FAILED');
    }

    public function news_view($id = null)
    {
        if (!$id) {
            return $this->respondError('ID is required', 400, 'REQUEST_FAILED');
        }
        $movie = $this->News_notice->getServerById($id);
        if (!$movie) {
            return $this->respondError('Movie not found', 404, 'REQUEST_FAILED');
        }
        return $this->respondSuccess($movie);
    }

    public function news_delete($id)
    {
        $server = $this->News_notice->getServerById($id);
        if (!$server) {
            return $this->respondError('news not found', 400, 'REQUEST_FAILED');
        }
        if (!empty($server->image) && file_exists(WRITEPATH . 'assets/news/' . $server->image)) {
            unlink(WRITEPATH . 'assets/news/' . $server->image);
        }
        if ($this->News_notice->deleteServer($id)) {
            return $this->respondSuccess(['message' => "news ID {$id} deleted"]);
        }
        return $this->respondError('Failed to delete news', 400, 'REQUEST_FAILED');
    }

    public function invoicePrint()
    {
        $id = $this->request->getGet('invoice_id') ?? null;
        $userId = $this->request->getGet('user_id') ?? null;
        if (!empty($userId)) {
            $orderDetails = $this->payment_model->where(['id' => $id, 'status' => 'successful'])->first();
            if (!empty($orderDetails)) {
                $user = $this->user_model->find($orderDetails->user_id);
                $adminid = $this->request->getGet('admin_id') ?? ($orderDetails->admin_id ?? null);
                if (empty($adminid)) {
                    return $this->respondError('admin_id is required (or invoice must include admin_id).', 400, 'REQUEST_FAILED');
                }
                $admindata = $this->user_model->find($adminid);
                $reseller_model = model('App\Models\Registration');
                $rdetails = $reseller_model->where(['userid' => $adminid])->first();
                $html = view('payments/pos', [
                    'details' => $orderDetails,
                    'rdetails' => $rdetails,
                    'user' => $user,
                    'admin' => $admindata,
                ]);
                return $this->response->setHeader('Content-Type', 'text/html')->setBody($html);
            }
        } else {
            $details = $this->payment_model->where(['id' => $id, 'status' => 'successful'])->first();
            if (empty($details)) {
                show_404();
                return null;
            }
            $user = getUserById($details->user_id);
            $html = view('payments/invoice', ['details' => $details, 'user' => $user]);
            return $this->response->setHeader('Content-Type', 'text/html')->setBody($html);
        }
        return $this->respondError('Invoice not found', 404, 'REQUEST_FAILED');
    }

    public function invoicePrintJson()
    {
        $id = (int) ($this->request->getGet('invoice_id') ?? 0);
        $userId = (int) ($this->request->getGet('user_id') ?? 0);

        if ($id <= 0) {
            return $this->respondError('invoice_id query parameter is required', 400, 'REQUEST_FAILED');
        }

        $details = $this->payment_model->where(['id' => $id, 'status' => 'successful'])->first();
        if (empty($details)) {
            return $this->respondError('Invoice not found', 404, 'REQUEST_FAILED');
        }

        if ($userId > 0) {
            $user = $this->user_model->find($details->user_id);
            $adminId = (int) ($this->request->getGet('admin_id') ?? ($details->admin_id ?? 0));
            if ($adminId <= 0) {
                return $this->respondError('admin_id is required (or invoice must include admin_id).', 400, 'REQUEST_FAILED');
            }

            $adminData = $this->user_model->find($adminId);
            $resellerModel = model('App\Models\Registration');
            $rdetails = $resellerModel->where(['userid' => $adminId])->first();

            return $this->respondSuccess([
                'invoice' => $details,
                'user' => $user,
                'admin' => $adminData,
                'reseller' => $rdetails,
                'view_type' => 'pos',
            ]);
        }

        $user = getUserById($details->user_id);
        return $this->respondSuccess([
            'invoice' => $details,
            'user' => $user,
            'view_type' => 'invoice',
        ]);
    }

    public function get_user_data_usage()
    {
        $user_id = $this->request->getGet('user_id');
        if (empty($user_id)) {
            return $this->respondError('user_id query parameter is required', 400, 'REQUEST_FAILED');
        }
        $user = $this->user_model->find($user_id);
        if (!$user || empty($user->router_id) || empty($user->pppoe_id)) {
            return $this->respondError('Invalid user_id or user network profile is incomplete', 404, 'REQUEST_FAILED');
        }

        try {
            $router_client = routerClient($user->router_id);
            $pppoeUserLookupFn = 'getPPPoEUserUserId';
            $pppoe = function_exists($pppoeUserLookupFn) ? $pppoeUserLookupFn($router_client, $user->id) : [];
            $pppoe_id = $pppoe[0]['.id'] ?? $user->pppoe_id ?? null;
            $pppoe_id = $pppoe_id ?? $user->pppoe_id;

            if (!is_object($router_client)) {
                $fp = connect_using_Fsocket($user->router_id);
                if (!$fp) {
                    return $this->respondError('Router connection failed', 500, 'REQUEST_FAILED');
                }
                $ppp_data = getPPPoEIdFsock($fp, $pppoe_id);
                if (!$ppp_data) {
                    return $this->respondError('No PPPoE data found', 404, 'REQUEST_FAILED');
                }
                /** @var array<string, mixed> $pppData */
                $pppData = is_array($ppp_data) ? $ppp_data : [];
                $rxMB = round(((float) ($pppData['rx'] ?? 0)) / 1024 / 1024, 2);
                $txMB = round(((float) ($pppData['tx'] ?? 0)) / 1024 / 1024, 2);
                return $this->respondSuccess(['user_id' => $user_id, 'pppoe_id' => $pppoe_id, 'rx_mb' => $rxMB, 'tx_mb' => $txMB, 'method' => 'FSOCK routerClient']);
            }

            $pppoeData = getPPPoEUser($router_client, $pppoe_id);
            if (empty($pppoeData)) {
                return $this->respondError('No PPPoE data found', 404, 'REQUEST_FAILED');
            }
            $pppoeName = $pppoeData[0]['name'] ?? $pppoe_id;
            $interfaceQuery = "<pppoe-$pppoeName>";
            $query = new \RouterOS\Query('/interface/print');
            $allInterfaces = $router_client->query($query)->read();
            $found = null;
            foreach ($allInterfaces as $intf) {
                if (isset($intf['name']) && $intf['name'] === $interfaceQuery) {
                    $found = $intf;
                    break;
                }
            }
            if (!$found) {
                return $this->respondError('Interface not found', 404, 'REQUEST_FAILED');
            }
            $rxMB = round($found['rx-byte'] / 1024 / 1024, 2);
            $txMB = round($found['tx-byte'] / 1024 / 1024, 2);
            return $this->respondSuccess(['user_id' => $user_id, 'pppoe_id' => $pppoe_id, 'rx_mb' => $rxMB, 'tx_mb' => $txMB, 'method' => 'routerClient']);
        } catch (\Throwable $e) {
            return $this->respondError($e->getMessage(), 500, 'REQUEST_FAILED');
        }
    }

    protected function resolveAccessTokenUserId(): ?int
    {
        $authHeader = (string) $this->request->getHeaderLine('Authorization');
        if ($authHeader === '' || stripos($authHeader, 'Bearer ') !== 0) {
            return null;
        }
        $token = trim(substr($authHeader, 7));
        if (stripos($token, 'bearer ') === 0) {
            $token = trim(substr($token, 7));
        }
        $payload = JwtToken::verify($token, \Zapi\utils\JwtToken::secret());
        if (!is_array($payload)) {
            return null;
        }
        $uid = $payload['sub'] ?? $payload['user_id'] ?? null;

        return $uid !== null && $uid !== '' ? (int) $uid : null;
    }

    protected function resolveMovieAdminIdForUser(int $userId): ?int
    {
        $details = $this->user_model->find($userId);
        if (!$details) {
            return null;
        }

        $admin_id = $details->admin_id ?? null;
        if (($details->created_by ?? '') === 'resellerAdmin' && !empty($admin_id)) {
            $parent = $this->user_model->find($admin_id);
            $admin_id = $parent->admin_id ?? null;
        }
        if (($details->role ?? '') === 'admin') {
            $admin_id = $userId;
        }

        return $admin_id !== null && $admin_id !== '' ? (int) $admin_id : null;
    }

    /**
     * @param array<string, mixed>|object $server
     */
    protected function actorCanManageMovieServer($server): bool
    {
        $tokenUserId = $this->resolveAccessTokenUserId();
        if ($tokenUserId === null) {
            return false;
        }

        $actorAdminId = $this->resolveMovieAdminIdForUser($tokenUserId);
        if ($actorAdminId === null) {
            return false;
        }

        $serverAdminId = is_array($server)
            ? (int) ($server['admin_id'] ?? 0)
            : (int) ($server->admin_id ?? 0);

        return $serverAdminId > 0 && $serverAdminId === $actorAdminId;
    }
}


