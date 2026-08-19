<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

class SharedAssets extends BaseController
{
    private const ASSETS = [
        'pdf.min.mjs'        => ['https://unpkg.com/pdfjs-dist@4.10.38/build/pdf.min.mjs', 'text/javascript; charset=utf-8'],
        'pdf.worker.min.mjs' => ['https://unpkg.com/pdfjs-dist@4.10.38/build/pdf.worker.min.mjs', 'text/javascript; charset=utf-8'],
        'qrcode.min.js'      => ['https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js', 'text/javascript; charset=utf-8'],
    ];

    public function file(string $filename): ResponseInterface
    {
        if (! isset(self::ASSETS[$filename])) {
            return $this->response->setStatusCode(404)->setBody('Not found');
        }
        [$url, $contentType] = self::ASSETS[$filename];
        try {
            $client = service('curlrequest', [
                'timeout' => 20,
                'connect_timeout' => 8,
                'headers' => ['User-Agent' => 'DamonArchive/1.0'],
            ]);
            $remote = $client->get($url, ['http_errors' => false]);
            if ($remote->getStatusCode() !== 200) {
                throw new \RuntimeException('Vendor returned ' . $remote->getStatusCode());
            }
            return $this->response
                ->setHeader('Content-Type', $contentType)
                ->setHeader('Cache-Control', 'public, max-age=86400, s-maxage=604800, immutable')
                ->setHeader('CDN-Cache-Control', 'public, max-age=604800')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setBody($remote->getBody());
        } catch (Throwable $e) {
            log_message('error', 'Shared vendor asset failed: {message}', ['message' => $e->getMessage()]);
            return $this->response->setStatusCode(502)->setHeader('Cache-Control', 'no-store')->setBody('Vendor asset unavailable');
        }
    }
}
