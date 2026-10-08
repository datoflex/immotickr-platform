<?php

namespace App\Models;

use App\Support\SearchAgents\PostcodeArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = [
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
        'price_cents',
        'price_m2',
        'price_m2_cents',
        'flaeche',
        'zimmer',
        'rendite_pot',
        'rendite_ist',
        'rendite_pot_num',
        'rendite_ist_num',
        'miete_pot_m2',
        'miete_pot_m2_cents',
        'miete_ist_m2',
        'miete_ist_m2_cents',
        'baujahr',
        'erbbaurecht',
        'zv',
        'vermietet',
        'hash',
        'email_date',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'price_m2_cents' => 'integer',
        'rendite_pot_num' => 'decimal:2',
        'rendite_ist_num' => 'decimal:2',
        'miete_pot_m2_cents' => 'integer',
        'miete_ist_m2_cents' => 'integer',
        'email_date' => 'datetime',
    ];

    /**
     * Listings in the given postcode, or within the radius around it.
     *
     * This is the one place that decides whether a listing fits a search agent's location:
     * the email run, the match count after saving and the admin filter all go through it.
     */
    public function scopeNearPostcode(Builder $query, string $postcode, float $radiusKm = 0): void
    {
        $query->whereIn('zip', PostcodeArea::postcodesWithin($postcode, $radiusKm));
    }
}
