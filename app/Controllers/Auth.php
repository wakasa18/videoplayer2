<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;

class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    public function index()
    {
        if ((bool) session()->get('site_authenticated')) {
            return redirect()->to('/');
        }

        return $this->response
            ->setHeader('Cache-Control', 'no-store, private, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Robots-Tag', 'noindex, nofollow')
            ->setBody(view('auth/login', [
                'configured' => $this->isConfigured(),
            ]));
    }

    public function login(): RedirectResponse
    {
        $session = session();
        $now     = time();
        $lockedUntil = (int) $session->get('site_login_locked_until');

        if ($lockedUntil > $now) {
            $minutes = max(1, (int) ceil(($lockedUntil - $now) / 60));
            return redirect()->to('/login')->withInput()->with('error', "Too many failed attempts. Try again in {$minutes} minute" . ($minutes === 1 ? '' : 's') . '.');
        }

        if (! $this->isConfigured()) {
            return redirect()->to('/login')->with('error', 'Website login is not configured yet. Add the login environment variables in Vercel.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');
        $expectedUsername = trim((string) getenv('SITE_LOGIN_USERNAME'));

        $usernameMatches = $username !== ''
            && hash_equals(strtolower($expectedUsername), strtolower($username));
        $passwordMatches = $this->passwordMatches($password);

        if (! $usernameMatches || ! $passwordMatches) {
            $attempts = ((int) $session->get('site_login_attempts')) + 1;
            $session->set('site_login_attempts', $attempts);

            if ($attempts >= 5) {
                $session->set('site_login_locked_until', $now + 15 * 60);
                $session->set('site_login_attempts', 0);
                return redirect()->to('/login')->withInput()->with('error', 'Too many failed attempts. Login is locked for 15 minutes.');
            }

            usleep(min($attempts, 4) * 250000);
            return redirect()->to('/login')->withInput()->with('error', 'Incorrect username or password.');
        }

        $redirect = (string) $session->get('site_login_redirect');
        if ($redirect === '' || ! str_starts_with($redirect, '/') || str_starts_with($redirect, '//')) {
            $redirect = '/';
        }

        $session->regenerate(true);
        $session->set([
            'site_authenticated' => true,
            'site_username'      => $expectedUsername,
            'site_last_activity' => $now,
        ]);
        $session->remove([
            'site_login_attempts',
            'site_login_locked_until',
            'site_login_redirect',
        ]);

        return redirect()->to($redirect)->with('success', 'Signed in successfully.');
    }

    public function logout(): RedirectResponse
    {
        $session = session();
        $session->remove([
            'site_authenticated',
            'site_username',
            'site_last_activity',
            'site_login_redirect',
            'files_unlocked',
            'files_pending_uploads',
            'files_pending_folder_downloads',
        ]);
        $session->regenerate(true);

        return redirect()->to('/login')->with('success', 'You have been signed out.');
    }

    private function isConfigured(): bool
    {
        $username = trim((string) getenv('SITE_LOGIN_USERNAME'));
        $hash     = trim((string) getenv('SITE_LOGIN_PASSWORD_HASH'));
        $plain    = (string) getenv('SITE_LOGIN_PASSWORD');

        return $username !== '' && ($hash !== '' || $plain !== '');
    }

    private function passwordMatches(string $password): bool
    {
        $hash = trim((string) getenv('SITE_LOGIN_PASSWORD_HASH'));
        if ($hash !== '') {
            return password_verify($password, $hash);
        }

        $plain = (string) getenv('SITE_LOGIN_PASSWORD');
        return $plain !== '' && hash_equals($plain, $password);
    }
}
