<?php

namespace App\Controllers;

class Sitemap extends BaseController
{
    public function index()
    {
        helper('url');

        $baseUrl = rtrim(base_url(), '/');
        $urls = [
            $baseUrl . '/',
            $baseUrl . '/auth/home',
            $baseUrl . '/auth/login',
            $baseUrl . '/auth/pricing',
            $baseUrl . '/auth/registration',
        ];

        // Ensure proper output type
        $response = service('response');
        $response->setContentType('text/xml');

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

        foreach ($urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url) . "</loc>\n";
            $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
            $xml .= "    <changefreq>weekly</changefreq>\n";
            $xml .= "    <priority>0.8</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= "</urlset>";

        return $response->setBody($xml);
    }
}
