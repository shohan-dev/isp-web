<?php

namespace Zapi\Modules\Common\Docs\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

class DocsController extends BaseController
{
    public function index()
    {
        $htmlPath = ROOTPATH . 'zapi/swagger-ui/index.html';
        $html = (string) @file_get_contents($htmlPath);
        if ($html === '') {
            throw PageNotFoundException::forPageNotFound();
        }

        $html = str_replace(
            ['__SWAGGER_CSS_URL__', '__SWAGGER_BUNDLE_URL__', '__SWAGGER_JSON_URL__'],
            [
                base_url('api/docs/swagger-ui/swagger-ui.css'),
                base_url('api/docs/swagger-ui/swagger-ui-bundle.js'),
                base_url('api/docs/swagger.json'),
            ],
            $html
        );

        return $this->response
            ->setHeader('Content-Type', 'text/html; charset=UTF-8')
            ->setBody($html);
    }

    public function swagger()
    {
        return $this->response
            ->setHeader('Content-Type', 'application/json; charset=UTF-8')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', '0')
            ->setBody((string) @file_get_contents(ROOTPATH . 'zapi/swagger-ui/swagger.json'));
    }

    public function asset(string $file)
    {
        $safe = basename($file);
        $path = ROOTPATH . 'zapi/swagger-ui/swagger-ui/' . $safe;

        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $mime = str_ends_with($safe, '.css') ? 'text/css; charset=UTF-8' : 'application/javascript; charset=UTF-8';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setBody((string) @file_get_contents($path));
    }
}
