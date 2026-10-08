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
        Schema::create('search_agent_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('search_agent_id')->constrained('search_agents')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->char('public_uuid', 32)->nullable()->unique();
            $table->string('status', 32)->default('completed');
            $table->unsignedBigInteger('from_listing_id')->nullable();
            $table->unsignedBigInteger('to_listing_id')->nullable();
            $table->unsignedInteger('new_match_count')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();

            $table->index('started_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_agent_runs');
    }
};
