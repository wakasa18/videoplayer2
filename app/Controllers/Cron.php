<?php

namespace App\Controllers;

use App\Models\AssignmentModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class Cron extends BaseController
{
    protected $helpers = ['url'];

    /**
     * Hit on a schedule (see vercel.json "crons"). Emails ONE digest
     * covering every pending assignment that's due within the next 2 days
     * or already overdue, as long as it hasn't already been emailed today
     * — so it re-sends daily for as long as something stays urgent,
     * stopping only once it's marked done, its due date moves out, or
     * it's deleted. Everything urgent goes into a single email rather
     * than one email per assignment.
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

        return $this->response->setJSON($this->runDigest());
    }

    /**
     * Manual trigger for testing from a browser, e.g.
     * /cron/check-deadlines/test?secret=yourvalue
     * Identical to checkDeadlines(), just documented as the test entry
     * point and reachable via a plain browser visit.
     */
    public function test(): ResponseInterface
    {
        if (! $this->cronSecretIsValid()) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized. Add ?secret=your_cron_secret to the URL.']);
        }

        $result         = $this->runDigest();
        $result['note'] = 'This also marks any sent assignments as notified, same as the real cron.';

        return $this->response->setJSON($result);
    }

    /**
     * Shared logic behind both entry points: find what's due, email it as
     * one digest, and mark everything notified only if the send actually
     * succeeded (so a failed send gets retried next time instead of
     * silently going dark).
     */
    private function runDigest(): array
    {
        $model  = new AssignmentModel();
        $urgent = $model->getDueSoonForReminder(2);

        if ($urgent === []) {
            return ['checked' => 0, 'sent' => [], 'failed' => []];
        }

        $ok = $this->sendDigestEmail($urgent);
        $titles = array_column($urgent, 'title');

        if ($ok) {
            foreach ($urgent as $assignment) {
                $model->markReminderSent($assignment['id']);
            }
        }

        return [
            'checked' => count($urgent),
            'sent'    => $ok ? $titles : [],
            'failed'  => $ok ? [] : $titles,
        ];
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

    /**
     * Send one email covering every urgent assignment passed in.
     */
    private function sendDigestEmail(array $assignments): bool
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

        $count = count($assignments);

        $subject = $count === 1
            ? 'Due soon: ' . $assignments[0]['title']
            : "{$count} assignments due soon";

        $email->setFrom($from, "Damon's Archive");
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMessage($this->buildDigestEmailBody($assignments));

        $sent = $email->send();

        if (! $sent) {
            log_message('error', 'Cron: failed to send reminder digest — ' . $email->printDebugger(['headers']));
        }

        return $sent;
    }

    /**
     * Branded HTML digest, styled to echo the site itself: a dark header
     * bar, one card per assignment with a priority-colored left edge and
     * the same subject-tag colors used on the actual Assignments page,
     * and a single call-to-action button at the end. Built with inline
     * styles and no external assets/fonts, since that's what survives
     * cleanly across email clients (Gmail included).
     */
    private function buildDigestEmailBody(array $assignments): string
    {
        $siteUrl = rtrim(base_url('assignments'), '/');
        $count   = count($assignments);

        $intro = $count === 1
            ? '1 assignment needs your attention:'
            : "{$count} assignments need your attention:";

        $cards = '';
        foreach ($assignments as $a) {
            $cards .= $this->buildAssignmentCard($a);
        }

        return '
<div style="background:#f4f5f9; padding:32px 16px; font-family:-apple-system,\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;">
  <div style="max-width:560px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e4e7f0;">

    <div style="background:#0d1224; padding:22px 28px;">
      <div style="font-size:11px; letter-spacing:.14em; text-transform:uppercase; color:#F2C36B; font-weight:600; font-family:Menlo,Consolas,monospace;">Damon&rsquo;s Archive</div>
      <div style="font-size:20px; color:#ffffff; margin-top:6px; font-weight:600; font-family:Georgia,serif; font-style:italic;">Task Log &middot; Sector 22</div>
    </div>

    <div style="padding:26px 28px 6px;">
      <p style="margin:0; font-size:15px; color:#2c2c38; line-height:1.5;">' . esc($intro) . '</p>
    </div>

    <div style="padding:14px 28px 6px;">' . $cards . '</div>

    <div style="padding:10px 28px 28px;">
      <a href="' . esc($siteUrl, 'attr') . '" style="display:inline-block; background:#F2C36B; color:#1B1430; font-weight:700; text-decoration:none; padding:12px 26px; border-radius:6px; font-size:14px;">View in your Archive &rarr;</a>
    </div>

    <div style="padding:16px 28px; background:#f4f5f9; border-top:1px solid #e4e7f0;">
      <p style="margin:0; font-size:11px; color:#9096a8; line-height:1.6;">
        Automated reminder from your Assignments queue. Mark something done or push its due date back and it stops showing up here &mdash; no need to reply.
      </p>
    </div>

  </div>
</div>';
    }

    private function buildAssignmentCard(array $a): string
    {
        $isOverdue = AssignmentModel::isOverdue($a);
        $dueText   = AssignmentModel::relativeDueDate($a) ?? 'No due date set';
        $priority  = $a['priority'] ?? 'medium';

        $borderColor = match ($priority) {
            'high'  => '#E5636B',
            'low'   => '#dfe3ee',
            default => '#F2C36B',
        };
        $dueColor  = $isOverdue ? '#C0392B' : '#6b7085';
        $dueWeight = $isOverdue ? '700' : '500';

        $subjectHtml = '';
        if (! empty($a['subject'])) {
            $rgb = AssignmentModel::subjectColorRgb($a['subject']);
            $subjectHtml = ' <span style="display:inline-block; font-size:10px; text-transform:uppercase; letter-spacing:.05em; padding:2px 9px; border-radius:20px; border:1px solid rgba(' . $rgb . ',.55); color:rgb(' . $rgb . '); background:rgba(' . $rgb . ',.08);">' . esc($a['subject']) . '</span>';
        }

        $descHtml = '';
        if (! empty($a['description'])) {
            $descHtml = '<div style="font-size:13px; color:#6b7085; margin-top:6px; line-height:1.55;">' . nl2br(esc($a['description'])) . '</div>';
        }

        return '
      <div style="border-left:4px solid ' . $borderColor . '; background:#f9fafc; border-radius:6px; padding:14px 16px; margin-bottom:12px;">
        <div style="font-size:15px; font-weight:600; color:#1B1430;">' . esc($a['title']) . $subjectHtml . '</div>
        <div style="font-size:13px; color:' . $dueColor . '; margin-top:5px; font-weight:' . $dueWeight . ';">' . esc($dueText) . '</div>' . $descHtml . '
      </div>';
    }
}
