<?php

namespace App\Controllers;

use App\Models\AssignmentModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Cron extends BaseController
{
    protected $helpers = ['url'];

    /**
     * Hit on a schedule (see vercel.json "crons"). Emails a reminder for
     * every pending assignment that's due within the next 2 days or
     * already overdue, as long as it hasn't already been emailed today —
     * so it re-sends daily for as long as it stays urgent, stopping only
     * once it's marked done, its due date moves out, or it's deleted.
     *
     * Protected by a shared secret so random visitors can't trigger it —
     * Vercel Cron sends `Authorization: Bearer $CRON_SECRET` automatically
     * when CRON_SECRET is set as an env var on the project.
     */
    public function checkDeadlines(): ResponseInterface
    {
        if (! $this->cronSecretIsValid()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $model  = new AssignmentModel();
        $urgent = $model->getDueSoonForReminder(2);

        $sent  = [];
        $failed = [];

        foreach ($urgent as $assignment) {
            if ($this->sendReminderEmail($assignment)) {
                $model->markReminderSent($assignment['id']);
                $sent[] = $assignment['id'];
            } else {
                $failed[] = $assignment['id'];
            }
        }

        return $this->response->setJSON([
            'checked' => count($urgent),
            'sent'    => $sent,
            'failed'  => $failed,
        ]);
    }

    /**
     * Manual trigger for testing from a browser, e.g.
     * /cron/check-deadlines/test?secret=yourvalue
     * Same logic as checkDeadlines() but accepts the secret as a query
     * param instead of a header, since browsers can't set custom headers.
     */
    public function test(): ResponseInterface
    {
        if (! $this->cronSecretIsValid()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized. Add ?secret=your_cron_secret to the URL.']);
        }

        $model  = new AssignmentModel();
        $urgent = $model->getDueSoonForReminder(2);

        $sent   = [];
        $failed = [];

        foreach ($urgent as $assignment) {
            if ($this->sendReminderEmail($assignment)) {
                $model->markReminderSent($assignment['id']);
                $sent[] = $assignment['title'];
            } else {
                $failed[] = $assignment['title'];
            }
        }

        return $this->response->setJSON([
            'checked' => count($urgent),
            'sent'    => $sent,
            'failed'  => $failed,
            'note'    => 'This also marks any sent assignments as notified, same as the real cron.',
        ]);
    }

    /**
     * True if the request is allowed to trigger a deadline check.
     *
     * Checks, in order: the Authorization header the normal way, two
     * fallback $_SERVER keys some PHP runtimes use instead when a header
     * gets stripped or renamed along the way, and finally a `?secret=`
     * query param so this still works from a plain browser visit. If
     * CRON_SECRET isn't set at all, every request is allowed through —
     * handy while testing locally before you've configured it.
     */
    private function cronSecretIsValid(): bool
    {
        $expected = getenv('CRON_SECRET');

        if ($expected === false || $expected === '') {
            return true;
        }

        $given = $this->request->getHeaderLine('Authorization');

        if ($given === '' && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $given = $_SERVER['HTTP_AUTHORIZATION'];
        }

        if ($given === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $given = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        $given = trim(str_replace('Bearer', '', $given));

        if ($given === '') {
            $given = (string) $this->request->getGet('secret');
        }

        return hash_equals($expected, $given);
    }

    private function sendReminderEmail(array $assignment): bool
    {
        $to   = getenv('EMAIL_TO');
        $from = getenv('EMAIL_FROM') ?: $to;

        if (! $to) {
            log_message('error', 'Cron: EMAIL_TO is not set, cannot send assignment reminders.');
            return false;
        }

        $email = Services::email();

        $email->initialize([
            'protocol'   => 'smtp',
            'SMTPHost'   => getenv('EMAIL_SMTP_HOST') ?: 'smtp.gmail.com',
            'SMTPUser'   => getenv('EMAIL_SMTP_USER'),
            'SMTPPass'   => getenv('EMAIL_SMTP_PASS'),
            'SMTPPort'   => (int) (getenv('EMAIL_SMTP_PORT') ?: 587),
            'SMTPCrypto' => 'tls',
            'mailType'   => 'html',
            'charset'    => 'utf-8',
            'newline'    => "\r\n",
        ]);

        $due = ! empty($assignment['due_date'])
            ? date('l, F j, Y', strtotime((string) $assignment['due_date']))
            : 'an unknown date';

        $siteUrl = rtrim(base_url('assignments'), '/');

        $body = '<div style="font-family: sans-serif; font-size: 15px; color: #1B1430;">'
            . '<p>Heads up — this assignment is coming up soon:</p>'
            . '<h2 style="margin: 12px 0 4px;">' . esc($assignment['title']) . '</h2>'
            . '<p style="margin: 0 0 12px; color: #555;">Due ' . esc($due) . '</p>';

        if (! empty($assignment['subject'])) {
            $body .= '<p style="margin: 0 0 12px;"><strong>Subject:</strong> ' . esc($assignment['subject']) . '</p>';
        }

        if (! empty($assignment['description'])) {
            $body .= '<p style="margin: 0 0 12px;">' . nl2br(esc($assignment['description'])) . '</p>';
        }

        $body .= '<p><a href="' . esc($siteUrl, 'attr') . '">View it in your Archive &rarr;</a></p>'
            . '</div>';

        $email->setFrom($from, "Damon's Archive");
        $email->setTo($to);
        $email->setSubject('Due soon: ' . $assignment['title']);
        $email->setMessage($body);

        $sent = $email->send();

        if (! $sent) {
            log_message('error', 'Cron: failed to send reminder for assignment #' . $assignment['id'] . ' — ' . $email->printDebugger(['headers']));
        }

        return $sent;
    }
}
