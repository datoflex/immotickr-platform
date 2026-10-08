<?php

use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new class extends Component
{
    #[Locked]
    public int $listingId;

    public bool $saved = false;

    public function toggle(): void
    {
        $savedListings = auth()->user()->savedListings();

        if ($this->saved) {
            $savedListings->detach($this->listingId);
        } else {
            $savedListings->syncWithoutDetaching([$this->listingId]);
        }

        $this->saved = ! $this->saved;

        $this->dispatch('listing-saved-changed', listingId: $this->listingId, saved: $this->saved, source: $this->getId());
    }

    /**
     * The same listing can appear under several emails. The other buttons for it call this
     * from the browser when one of them was clicked, so only they ask the server, not every button.
     */
    public function refreshSaved(): void
    {
        $this->saved = auth()->user()->savedListings()->whereKey($this->listingId)->exists();
    }
}; ?>

<button
    type="button"
    wire:click="toggle"
    x-data
    x-on:listing-saved-changed.window="if ($event.detail.listingId === {{ $listingId }} && $event.detail.source !== $wire.$id) { $wire.refreshSaved() }"
    @class([
        'inline-flex items-center gap-1.5 rounded-md border px-3 py-1.5 text-sm font-medium transition',
        'border-teal-600 bg-teal-50 text-teal-700 hover:bg-teal-100' => $saved,
        'border-gray-300 bg-white text-gray-700 hover:bg-gray-50' => ! $saved,
    ])
    aria-pressed="{{ $saved ? 'true' : 'false' }}"
>
    <svg class="h-4 w-4" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" fill="{{ $saved ? 'currentColor' : 'none' }}" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
    </svg>
    {{ $saved ? __('Gemerkt') : __('Merken') }}
</button>
