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
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->text('source_url')->nullable();
            $table->text('detail_url')->nullable();
            $table->string('title')->nullable();
            $table->string('raw_address')->nullable();
            $table->string('street')->nullable();
            $table->string('zip', 16)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('state', 128)->nullable();
            $table->string('country', 128)->nullable();
            $table->string('preisbewertung', 64)->nullable();
            $table->string('standortbewertung', 64)->nullable();
            $table->string('price', 64)->nullable();
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('price_m2', 64)->nullable();
            $table->unsignedInteger('price_m2_cents')->nullable();
            $table->string('flaeche', 64)->nullable();
            $table->string('zimmer', 32)->nullable();
            $table->string('rendite_pot', 32)->nullable();
            $table->string('rendite_ist', 32)->nullable();
            $table->decimal('rendite_pot_num', 6, 2)->nullable();
            $table->decimal('rendite_ist_num', 6, 2)->nullable();
            $table->string('miete_pot_m2', 32)->nullable();
            $table->unsignedInteger('miete_pot_m2_cents')->nullable();
            $table->string('miete_ist_m2', 32)->nullable();
            $table->unsignedInteger('miete_ist_m2_cents')->nullable();
            $table->string('baujahr', 16)->nullable();
            $table->string('erbbaurecht', 16)->nullable();
            $table->string('zv', 16)->nullable();
            $table->string('vermietet', 16)->nullable();
            $table->char('hash', 64)->unique();
            $table->dateTime('email_date')->nullable();
            $table->timestamps();

            $table->index('zip');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
