<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('search_agent_listing_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('run_id')->nullable()->unique()->constrained('search_agent_runs')->nullOnDelete();
            $table->foreignId('search_agent_id')->constrained('search_agents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('listing_ids_json');
            $table->unsignedInteger('match_count')->default(0);
            $table->char('public_token', 32)->nullable()->unique();
            $table->boolean('is_public')->default(true);
            $table->timestamp('matched_at')->useCurrent();

            $table->index('matched_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_agent_listing_matches');
    }
};
