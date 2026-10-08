<?php

return [

    'host' => env('IMAP_HOST', 'imap.strato.de'),
    'port' => env('IMAP_PORT', 993),
    'username' => env('IMAP_USERNAME', ''),
    'password' => env('IMAP_PASSWORD', ''),
    'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
    'readonly' => env('IMAP_READONLY', false),
    'mailbox' => env('IMAP_MAILBOX', 'INBOX'),
    'from_filter' => env('IMAP_FROM_FILTER', 'suchauftrag@thinkimmo.com'),
    'from_name_exclude' => env('IMAP_FROM_NAME_EXCLUDE', 'Dealradar'),
    'max_messages' => env('IMAP_MAX_MESSAGES', 1),

];
