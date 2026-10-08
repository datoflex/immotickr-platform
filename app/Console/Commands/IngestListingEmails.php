<?php

namespace App\Console\Commands;

use App\Models\Listing;
use App\Models\ListingLog;
use App\Support\ListingIngest\EmailParser;
use App\Support\ListingIngest\ListingFieldParser;
use App\Support\ListingIngest\MailboxReader;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:ingest-listing-emails')]
#[Description('Fetch unseen listing emails via IMAP, parse them, and store new listings')]
class IngestListingEmails extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $config = config('imap');

        $reader = new MailboxReader($config);
        $emails = $reader->fetchEmails(
            $config['from_filter'] ?? null,
            $config['from_name_exclude'] ?? null,
            (int) ($config['max_messages'] ?? 1)
        );

        if (count($emails) === 0) {
            $this->info('No unseen emails matched the filter.');

            return self::SUCCESS;
        }

        $saved = 0;
        $duplicates = 0;
        $parsedItems = 0;

        foreach ($emails as $email) {
            $decodedSubject = ListingFieldParser::decodeSubject((string) ($email['subject'] ?? ''));
            $declaredCount = ListingFieldParser::extractDeclaredListingCount($decodedSubject);

            if ($declaredCount !== null && $declaredCount > 4) {
                ListingLog::create([
                    'level' => 'info',
                    'category' => 'email',
                    'message' => 'Email subject indicates more than 4 listings',
                    'context' => [
                        'subject' => $decodedSubject,
                        'declared_total' => $declaredCount,
                    ],
                ]);
            }

            $items = EmailParser::extractImmoItems((string) ($email['body'] ?? ''));
            if (count($items) === 0) {
                continue;
            }

            foreach ($items as $item) {
                $parsedItems++;
                $item['email_date'] = $email['date'] ?? null;
                $addr = ListingFieldParser::splitAddress((string) ($item['address'] ?? ''));

                if ($this->saveListing($item, $addr)) {
                    $saved++;
                } else {
                    $duplicates++;
                }
            }
        }

        $this->info('Emails processed: ' . count($emails));
        $this->info("Listings parsed: {$parsedItems}");
        $this->info("Listings inserted: {$saved}");
        $this->info("Listings skipped (duplicate): {$duplicates}");

        return self::SUCCESS;
    }

    private function saveListing(array $item, array $addr): bool
    {
        $hash = ListingFieldParser::computeHash($item);

        if (Listing::where('hash', $hash)->exists()) {
            ListingLog::create([
                'level' => 'info',
                'category' => 'listing',
                'message' => 'Duplicate listing skipped',
                'context' => [
                    'hash' => $hash,
                    'price' => $item['price'] ?? null,
                    'title' => $item['title'] ?? null,
                    'address' => $item['address'] ?? null,
                ],
            ]);

            return false;
        }

        $listing = Listing::firstOrCreate(
            ['hash' => $hash],
            [
                'source_url' => $item['source_url'] ?? null,
                'detail_url' => $item['detail_url'] ?? null,
                'title' => $item['title'] ?? null,
                'raw_address' => $item['address'] ?? null,
                'street' => $addr['street'] ?? null,
                'zip' => $addr['zip'] ?? null,
                'city' => $addr['city'] ?? null,
                'state' => $addr['state'] ?? null,
                'country' => $addr['country'] ?? null,
                'preisbewertung' => $item['preisbewertung'] ?? null,
                'standortbewertung' => $item['standortbewertung'] ?? null,
                'price' => $item['price'] ?? null,
                'price_m2' => $item['price_m2'] ?? null,
                'flaeche' => $item['flaeche'] ?? null,
                'zimmer' => $item['zimmer'] ?? null,
                'rendite_pot' => $item['rendite_pot'] ?? null,
                'rendite_ist' => $item['rendite_ist'] ?? null,
                'rendite_pot_num' => ListingFieldParser::percentToFloat($item['rendite_pot'] ?? null),
                'rendite_ist_num' => ListingFieldParser::percentToFloat($item['rendite_ist'] ?? null),
                'miete_pot_m2' => $item['miete_pot_m2'] ?? null,
                'miete_ist_m2' => $item['miete_ist_m2'] ?? null,
                'baujahr' => $item['baujahr'] ?? null,
                'erbbaurecht' => $item['erbbaurecht'] ?? null,
                'zv' => $item['zv'] ?? null,
                'vermietet' => $item['vermietet'] ?? null,
                'price_cents' => ListingFieldParser::priceToCents($item['price'] ?? null),
                'price_m2_cents' => ListingFieldParser::priceToCents($item['price_m2'] ?? null),
                'miete_pot_m2_cents' => ListingFieldParser::priceToCents($item['miete_pot_m2'] ?? null),
                'miete_ist_m2_cents' => ListingFieldParser::priceToCents($item['miete_ist_m2'] ?? null),
                'email_date' => $item['email_date'] ?? null,
            ]
        );

        return $listing->wasRecentlyCreated;
    }
}
