<?php

namespace App\Console\Commands;

use App\Models\Listing;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-joomla-listings')]
#[Description('Import listings from the legacy Joomla si0ij_immotickr_listings table')]
class ImportJoomlaListings extends Command
{
    private const NULLABLE_NUMERIC_FIELDS = [
        'price_cents',
        'price_m2_cents',
        'miete_pot_m2_cents',
        'miete_ist_m2_cents',
        'rendite_pot_num',
        'rendite_ist_num',
    ];

    private const NULLABLE_STRING_FIELDS = [
        'source_url',
        'detail_url',
        'title',
        'raw_address',
        'street',
        'zip',
        'city',
        'state',
        'country',
        'preisbewertung',
        'standortbewertung',
        'price',
        'price_m2',
        'flaeche',
        'zimmer',
        'rendite_pot',
        'rendite_ist',
        'miete_pot_m2',
        'miete_ist_m2',
        'baujahr',
        'erbbaurecht',
        'zv',
        'vermietet',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $total = DB::connection('joomla')->table('si0ij_immotickr_listings')->count();

        if ($total === 0) {
            $this->error('No rows found in the source table.');

            return self::FAILURE;
        }

        $this->info("Importing {$total} listings from Joomla...");
        $bar = $this->output->createProgressBar($total);

        DB::connection('joomla')
            ->table('si0ij_immotickr_listings')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($bar) {
                $now = now();

                $records = $rows->map(function ($row) use ($now) {
                    $record = [
                        'hash' => $row->hash,
                        'email_date' => $this->blankToNull($row->email_date),
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $now,
                    ];

                    foreach (self::NULLABLE_STRING_FIELDS as $field) {
                        $record[$field] = $this->blankToNull($row->{$field});
                    }

                    foreach (self::NULLABLE_NUMERIC_FIELDS as $field) {
                        $record[$field] = $this->blankToNull($row->{$field});
                    }

                    return $record;
                })->all();

                Listing::upsert($records, ['hash'], array_diff(array_keys($records[0]), ['hash', 'created_at']));

                $bar->advance(count($records));
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported ' . Listing::count() . ' listings.');

        return self::SUCCESS;
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }
}
