<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('search_agent_listing_matches', function (Blueprint $table) {
            $table->timestamp('emailed_at')->nullable()->after('matched_at');

            $table->index(['user_id', 'emailed_at']);
        });

        // Before this column existed, a notification was sent right after every run with matches.
        DB::table('search_agent_listing_matches')
            ->where('match_count', '>', 0)
            ->update(['emailed_at' => DB::raw('matched_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('search_agent_listing_matches', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'emailed_at']);
            $table->dropColumn('emailed_at');
        });
    }
};
