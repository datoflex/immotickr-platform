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
        Schema::create('search_agents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('postcode', 5)->nullable();
            $table->decimal('radius', 8, 2)->nullable();
            $table->decimal('price_from', 12, 2)->nullable();
            $table->decimal('price_to', 12, 2)->nullable();
            $table->unsignedInteger('size_from')->nullable();
            $table->unsignedInteger('size_to')->nullable();
            $table->decimal('pot_return_from', 5, 2)->nullable();
            $table->decimal('pot_return_to', 5, 2)->nullable();
            $table->string('min_price_valuation')->nullable();
            $table->string('min_location_valuation')->nullable();
            $table->decimal('price_m2_from', 10, 2)->nullable();
            $table->decimal('price_m2_to', 10, 2)->nullable();
            $table->unsignedTinyInteger('min_rooms')->nullable();
            $table->unsignedTinyInteger('max_rooms')->nullable();
            $table->string('pot_rent_m2')->nullable();
            $table->unsignedSmallInteger('year_construction_from')->nullable();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('last_processed_listing_id')->nullable();
            $table->timestamps();

            $table->index('postcode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('search_agents');
    }
};
