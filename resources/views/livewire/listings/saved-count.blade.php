<?php

use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    #[Computed]
    public function count(): int
    {
        return auth()->user()->savedListings()->count();
    }
}; ?>

{{-- The count follows the save buttons in the browser, so a click does not cost a second request. --}}
<span
    x-data="{ count: {{ $this->count }} }"
    x-on:listing-saved-changed.window="count += $event.detail.saved ? 1 : -1"
    x-bind:class="{ 'hidden': count <= 0 }"
    x-text="count"
    @class([
        'ms-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600',
        'hidden' => $this->count === 0,
    ])
>{{ $this->count }}</span>
