<?php

declare(strict_types=1);

namespace App\Support\ListingIngest;

use DateTimeImmutable;
use Exception;
use RuntimeException;

final class MailboxReader
{
    private $connection;

    private bool $readonly;

    public function __construct(array $config)
    {
        $this->readonly = !empty($config['readonly']);
        $readonlyFlag = $this->readonly ? '/readonly' : '';
        $mailbox = (string) ($config['mailbox'] ?? 'INBOX');

        $this->connection = imap_open(
            '{' . $config['host'] . ':' . $config['port'] . '/' . $config['encryption'] . $readonlyFlag . '}' . $mailbox,
            (string) $config['username'],
            (string) $config['password']
        );

        if (!$this->connection) {
            throw new RuntimeException('Cannot connect to IMAP: ' . (imap_last_error() ?: 'unknown error'));
        }
    }

    public function fetchEmails(?string $fromFilter, ?string $fromNameExclude, int $maxMessages = 1): array
    {
        $emails = [];
        $query = 'UNSEEN';
        if ($fromFilter !== null && $fromFilter !== '') {
            $query .= ' FROM "' . addcslashes($fromFilter, '"\\') . '"';
        }

        $search = imap_search($this->connection, $query);
        if (!$search || count($search) === 0) {
            return $emails;
        }

        sort($search);

        foreach ($search as $msgno) {
            $header = imap_headerinfo($this->connection, $msgno);
            $body = imap_body($this->connection, $msgno);
            if (!is_object($header) || !is_string($body)) {
                continue;
            }

            $senderName = '';
            $senderEmail = '';
            if (isset($header->from) && is_array($header->from) && count($header->from) > 0) {
                $sender = $header->from[0];
                $senderName = isset($sender->personal) ? $this->decodeHeaderValue((string) $sender->personal) : '';
                $senderEmail = isset($sender->mailbox, $sender->host) ? (string) $sender->mailbox . '@' . (string) $sender->host : '';
            }

            if ($this->matchesExcludedFromName($senderName, $fromNameExclude)) {
                continue;
            }

            $emails[] = [
                'subject' => isset($header->subject) ? (string) $header->subject : '',
                'sender_name' => $senderName,
                'sender_email' => $senderEmail,
                'date' => $this->formatDate(isset($header->date) ? (string) $header->date : null),
                'body' => $body,
                'msgno' => (int) $msgno,
            ];

            $this->markAsRead((int) $msgno);

            if (count($emails) >= max(1, $maxMessages)) {
                break;
            }
        }

        return $emails;
    }

    public function close(): void
    {
        if (is_resource($this->connection)) {
            imap_errors();
            @imap_close($this->connection);
        }
        $this->connection = null;
    }

    public function __destruct()
    {
        $this->close();
    }

    private function markAsRead(int $msgno): void
    {
        if ($this->readonly) {
            return;
        }
        imap_setflag_full($this->connection, (string) $msgno, '\\Seen');
    }

    private function formatDate(?string $headerDate): ?string
    {
        if ($headerDate === null || $headerDate === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($headerDate))->format('Y-m-d H:i:s');
        } catch (Exception) {
            return null;
        }
    }

    private function matchesExcludedFromName(string $senderName, ?string $fromNameExclude): bool
    {
        if ($fromNameExclude === null || $fromNameExclude === '') {
            return false;
        }

        return stripos($senderName, $fromNameExclude) !== false;
    }

    private function decodeHeaderValue(string $value): string
    {
        if ($value === '' || !function_exists('iconv_mime_decode')) {
            return $value;
        }

        $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return is_string($decoded) ? $decoded : $value;
    }
}
