<?php

namespace Zapi\Modules\Reseller\VoiceSms\Controllers;

use Zapi\Core\Base\BaseApiController;
use Zapi\Modules\Reseller\VoiceSms\Services\VoiceSmsService;

class VoiceSmsController extends BaseApiController
{
    protected VoiceSmsService $service;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->service = new VoiceSmsService();
        if (method_exists($this->service, 'initController')) {
            $this->service->initController($request, $response, $logger);
        }
    }

    public function recipients(...$args)
    {
        return $this->service->recipients(...$args);
    }

    public function send(...$args)
    {
        return $this->service->send(...$args);
    }

    public function templates(...$args)
    {
        return $this->service->templates(...$args);
    }

    public function createTemplate(...$args)
    {
        return $this->service->createTemplate(...$args);
    }

    public function updateTemplate(...$args)
    {
        return $this->service->updateTemplate(...$args);
    }

    public function deleteTemplate(...$args)
    {
        return $this->service->deleteTemplate(...$args);
    }

    public function settings(...$args)
    {
        return $this->service->settings(...$args);
    }

    public function updateSettings(...$args)
    {
        return $this->service->updateSettings(...$args);
    }

    public function updateEventConfig(...$args)
    {
        return $this->service->updateEventConfig(...$args);
    }

    public function gatewayVoices(...$args)
    {
        return $this->service->gatewayVoices(...$args);
    }

}









