<?php

namespace App\Services;

use Carbon\Carbon;

class MailTemplateService
{
    private FirestoreRestService $firestore;

    public function __construct(FirestoreRestService $firestore)
    {
        $this->firestore = $firestore;
    }

    public function getTemplate(string $type): array
    {
        if (\App\Support\FeatureFlags::useMock()) {
            $mock = config('mock_data.mail_templates', []);
            if (isset($mock[$type])) {
                return $mock[$type];
            }
            return $this->defaultTemplate($type);
        }
        if ($type === '') {
            return $this->defaultTemplate($type);
        }

        if (!\App\Support\FeatureFlags::shouldUseFirestore('MAIL_TEMPLATES')) {
            return $this->defaultTemplate($type);
        }

        $doc = $this->firestore->getDocumentFields('mail_templates', $type);
        if (empty($doc)) {
            return $this->defaultTemplate($type);
        }

        return [
            'type' => $type,
            'subject' => (string) ($doc['subject'] ?? 'No subject'),
            'body_html' => (string) ($doc['body_html'] ?? ($doc['description'] ?? '')),
            'body_text' => (string) ($doc['body_text'] ?? ''),
        ];
    }

    public function saveTemplate(string $type, string $subject, string $bodyHtml, string $bodyText = ''): bool
    {
        if (\App\Support\FeatureFlags::useMock()) {
            return true;
        }
        if (!\App\Support\FeatureFlags::shouldUseFirestore('MAIL_TEMPLATES')) {
            return false;
        }
        if ($type === '') {
            return false;
        }

        $fields = [
            'type' => $type,
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
            'updatedAt' => Carbon::now('UTC'),
        ];

        return $this->firestore->patchDocumentTyped('mail_templates', $type, $fields);
    }

    public function seedDefaults(array $types): int
    {
        $count = 0;
        foreach ($types as $type) {
            $existing = $this->firestore->getDocumentFields('mail_templates', $type);
            if (!empty($existing)) {
                continue;
            }
            $template = $this->defaultTemplate($type);
            $ok = $this->saveTemplate($type, $template['subject'], $template['body_html'], $template['body_text']);
            if ($ok) {
                $count++;
            }
        }
        return $count;
    }

    private function defaultTemplate(string $type): array
    {
        $label = $type !== '' ? $type : 'template';
        return [
            'type' => $type,
            'subject' => 'Default subject: ' . $label,
            'body_html' => '<p>Default template for <strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</strong>.</p>',
            'body_text' => 'Default template for ' . $label . '.',
        ];
    }
}
