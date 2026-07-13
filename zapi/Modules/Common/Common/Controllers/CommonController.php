<?php

namespace Zapi\Modules\Common\Common\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Common\Common\Services\CommonService;


class CommonController extends BaseApiController
{
    protected CommonService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new CommonService();
        $this->service->initController($request, $response, $logger);
    }

    public function pppoeExpiryCheck()
    {
        return $this->service->pppoeExpiryCheck();
    }

    public function movie_index()
    {
        return $this->service->movie_index();
    }

    public function add()
    {
        return $this->service->add();
    }

    public function update($id)
    {
        return $this->service->update($id);
    }

    public function view($id = null)
    {
        return $this->service->view($id);
    }

    public function delete($id)
    {
        return $this->service->delete($id);
    }

    public function news_index()
    {
        return $this->service->news_index();
    }

    public function news_add()
    {
        return $this->service->news_add();
    }

    public function news_update($id)
    {
        return $this->service->news_update($id);
    }

    public function news_view_update($id)
    {
        return $this->service->news_view_update($id);
    }

    public function news_view($id = null)
    {
        return $this->service->news_view($id);
    }

    public function news_delete($id)
    {
        return $this->service->news_delete($id);
    }

    public function invoicePrint()
    {
        return $this->service->invoicePrint();
    }

    public function invoicePrintJson()
    {
        return $this->service->invoicePrintJson();
    }

    public function get_user_data_usage()
    {
        return $this->service->get_user_data_usage();
    }
}








