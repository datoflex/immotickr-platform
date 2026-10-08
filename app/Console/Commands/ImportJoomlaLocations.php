<?php

namespace App\Console\Commands;

use App\Models\Location;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:import-joomla-locations')]
#[Description('Import German city locations from the legacy Joomla si0ij_immotickr_locations table')]
class ImportJoomlaLocations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $total = DB::connection('joomla')->table('si0ij_immotickr_locations')->count();

        if ($total === 0) {
            $this->error('No rows found in the source table.');

            return self::FAILURE;
        }

        $this->info("Importing {$total} locations from Joomla...");
        $bar = $this->output->createProgressBar($total);

        DB::connection('joomla')
            ->table('si0ij_immotickr_locations')
            ->orderBy('id')
            ->chunk(1000, function ($rows) use ($bar) {
                $now = now();

                $records = $rows->map(fn ($row) => [
                    'postcode' => $row->postcode,
                    'city_name' => $row->city_name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                Location::upsert($records, ['postcode', 'city_name'], ['city_name']);

                $bar->advance(count($records));
            });

        $bar->finish();
        $this->newLine(2);
        $this->info('Imported ' . Location::count() . ' locations.');

        return self::SUCCESS;
    }
}
