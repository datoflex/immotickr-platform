<?php

namespace App\Console\Commands;

use App\Models\SearchAgent;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-joomla-search-agents')]
#[Description('Import search agents from the legacy Joomla si0ij_immotickr_searchagents table, dropping Joomla-only bookkeeping fields (state, created_by, modified_by)')]
class ImportJoomlaSearchAgents extends Command
{
    /**
     * Numeric/decimal columns that Joomla stored as varchar and may contain
     * empty strings instead of NULL.
     */
    private const NULLABLE_NUMERIC_FIELDS = [
        'radius',
        'price_from',
        'price_to',
        'size_from',
        'size_to',
        'pot_return_from',
        'pot_return_to',
        'min_rooms',
        'max_rooms',
        'last_processed_listing_id',
    ];

    private const NULLABLE_STRING_FIELDS = [
        'postcode',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $total = DB::connection('joomla')->table('si0ij_immotickr_searchagents')->count();

        if ($total === 0) {
            $this->error('No rows found in the source table.');

            return self::FAILURE;
        }

        $this->info("Importing {$total} search agents from Joomla...");
        $bar = $this->output->createProgressBar($total);

        DB::connection('joomla')
            ->table('si0ij_immotickr_searchagents')
            ->orderBy('id')
            ->chunk(500, function ($rows) use ($bar) {
                $now = now();

                $records = $rows->map(function ($row) use ($now) {
                    $record = [
                        'title' => $row->title,
                        'radius' => $this->blankToNull($row->radius),
                        'price_from' => $this->blankToNull($row->price_from),
                        'price_to' => $this->blankToNull($row->price_to),
                        'size_from' => $this->blankToNull($row->size_from),
                        'size_to' => $this->blankToNull($row->size_to),
                        'pot_return_from' => $this->blankToNull($row->pot_return_from),
                        'pot_return_to' => $this->blankToNull($row->pot_return_to),
                        'min_rooms' => $this->blankToNull($row->min_rooms),
                        'max_rooms' => $this->blankToNull($row->max_rooms),
                        'last_processed_listing_id' => $this->blankToNull($row->last_processed_listing_id),
                        'postcode' => $this->blankToNull($row->postcode),
                        'uuid' => $row->uuid !== '' ? $row->uuid : null,
                        'created_at' => $row->created_at ?? $now,
                        'updated_at' => $now,
                    ];

                    return $record;
                })->all();

                SearchAgent::upsert($records, ['uuid'], array_diff(array_keys($records[0]), ['uuid', 'created_at']));

                $bar->advance(count($records));
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported ' . SearchAgent::count() . ' search agents.');

        return self::SUCCESS;
    }

    private function blankToNull(mixed $value): mixed
    {
        return $value === '' || $value === null ? null : $value;
    }
}
