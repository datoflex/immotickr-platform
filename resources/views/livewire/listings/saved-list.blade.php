<?php

use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\On;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    /**
     * Re-rendering is enough; the listings are queried fresh on every render.
     */
    #[On('listing-saved-changed')]
    public function refreshListings(): void {}

    /**
     * @return array{listings: LengthAwarePaginator}
     */
    public function with(): array
    {
        $listings = $this->savedListings();

        // Unsaving the last listing of the last page would otherwise leave an empty page behind.
        if ($listings->isEmpty() && $listings->currentPage() > 1) {
            $this->setPage($listings->lastPage());
            $listings = $this->savedListings();
        }

        return ['listings' => $listings];
    }

    private function savedListings(): LengthAwarePaginator
    {
        return auth()->user()
            ->savedListings()
            ->orderByPivot('created_at', 'desc')
            ->paginate(20);
    }
}; ?>

<div class="space-y-8">
    <div class="space-y-3">
        @forelse ($listings as $listing)
            <x-listing-card :listing="$listing" wire:key="saved-listing-{{ $listing->id }}">
                <x-slot:actions>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-gray-500">
                            {{ __('Gemerkt am :date', ['date' => $listing->pivot->created_at->timezone('Europe/Berlin')->format('d.m.Y')]) }}
                        </span>
                        <livewire:listings.save-toggle
                            :listing-id="$listing->id"
                            :saved="true"
                            :key="'save-'.$listing->id"
                        />
                    </div>
                </x-slot:actions>
            </x-listing-card>
        @empty
            <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-500">
                {{ __('Deine Merkliste ist noch leer. Klicke bei einem Suchergebnis auf „Merken“, um es hier zu speichern.') }}
            </div>
        @endforelse
    </div>

    <div class="px-4 sm:px-0">
        {{ $listings->links() }}
    </div>
</div>
