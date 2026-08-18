<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $now     = time();
        $timeout = (int) (getenv('SITE_SESSION_TIMEOUT_MINUTES') ?: 480);
        $timeout = max(5, min($timeout, 10080)) * 60;

        if ((bool) $session->get('site_authenticated')) {
            $lastActivity = (int) $session->get('site_last_activity');
            if ($lastActivity > 0 && ($now - $lastActivity) > $timeout) {
                $session->remove([
                    'site_authenticated',
                    'site_username',
                    'site_last_activity',
                    'files_unlocked',
                    'files_pending_uploads',
                    'files_pending_folder_downloads',
                ]);

                if ($this->expectsJson($request)) {
                    return service('response')
                        ->setStatusCode(401)
                        ->setJSON(['error' => 'Your login session expired. Refresh the page and sign in again.']);
                }

                return redirect()->to('/login')->with('error', 'Your session expired. Please sign in again.');
            }

            $session->set('site_last_activity', $now);
            return null;
        }

        if ($this->expectsJson($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON(['error' => 'Please sign in to continue.']);
        }

        if (strtoupper($request->getMethod()) === 'GET') {
            $uri = $request->getUri();
            $path = '/' . ltrim($uri->getPath(), '/');
            $query = $uri->getQuery();
            $session->set('site_login_redirect', $path . ($query !== '' ? '?' . $query : ''));
        }

        return redirect()->to('/login');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function expectsJson(RequestInterface $request): bool
    {
        $accept      = strtolower($request->getHeaderLine('Accept'));
        $contentType = strtolower($request->getHeaderLine('Content-Type'));
        $requestedWith = strtolower($request->getHeaderLine('X-Requested-With'));

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || $requestedWith === 'xmlhttprequest';
    }
}
