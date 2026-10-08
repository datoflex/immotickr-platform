<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-joomla-listing-logs')]
#[Description('Import historical listing logs from the legacy Joomla si0ij_immotickr_listings_logs table')]
class ImportJoomlaListingLogs extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $total = DB::connection('joomla')->table('si0ij_immotickr_listings_logs')->count();

        if ($total === 0) {
            $this->error('No rows found in the source table.');

            return self::FAILURE;
        }

        $this->info("Importing {$total} listing logs from Joomla...");
        $bar = $this->output->createProgressBar($total);

        DB::connection('joomla')
            ->table('si0ij_immotickr_listings_logs')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use ($bar) {
                $records = $rows->map(fn ($row) => [
                    'id' => $row->id,
                    'created_at' => $row->created_at,
                    'level' => $row->level,
                    'category' => $row->category,
                    'message' => $row->message,
                    'context' => $row->context,
                ])->all();

                DB::table('listing_logs')->insertOrIgnore($records);

                $bar->advance(count($records));
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported ' . DB::table('listing_logs')->count() . ' listing logs.');

        return self::SUCCESS;
    }
}
