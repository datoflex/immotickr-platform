<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- The subnav must be a direct child of the full-height container to stay sticky while scrolling. --}}
            @include('search-results.partials.subnav')
            <p class="!mt-4 px-4 sm:px-0 text-sm text-gray-500">{{ __('Alle Treffer, die dir per E-Mail zugeschickt wurden.') }}</p>

            @forelse ($matches as $match)
                @php
                    $emailedAt = $match->emailed_at->timezone('Europe/Berlin');
                    $matchListings = collect($match->listing_ids_json)->map(fn ($id) => $listings->get($id))->filter();
                @endphp

                <section class="space-y-3">
                    <div class="px-4 sm:px-0 flex flex-col sm:flex-row sm:items-baseline sm:justify-between gap-1 border-b border-gray-200 pb-2">
                        <h2 class="font-semibold text-gray-800">
                            {{ __('Gesendet am :date um :time Uhr', ['date' => $emailedAt->format('d.m.Y'), 'time' => $emailedAt->format('H:i')]) }}
                        </h2>
                        <p class="text-sm text-gray-500">
                            {{ $match->searchAgent?->title ?: __('Suchagent') }}
                            &middot;
                            {{ trans_choice(':count Treffer|:count Treffer', $match->match_count, ['count' => $match->match_count]) }}
                        </p>
                    </div>

                    @forelse ($matchListings as $listing)
                        <x-listing-card :listing="$listing" wire:key="match-{{ $match->id }}-listing-{{ $listing->id }}">
                            <x-slot:actions>
                                <livewire:listings.save-toggle
                                    :listing-id="$listing->id"
                                    :saved="in_array($listing->id, $savedIds, true)"
                                    :key="'save-'.$match->id.'-'.$listing->id"
                                />
                            </x-slot:actions>
                        </x-listing-card>
                    @empty
                        <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-500">
                            {{ __('Die Inserate dieser E-Mail sind nicht mehr verfügbar.') }}
                        </div>
                    @endforelse
                </section>
            @empty
                <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-500">
                    {{ __('Es wurden dir noch keine Suchergebnisse per E-Mail zugeschickt.') }}
                </div>
            @endforelse

            <div class="px-4 sm:px-0">
                {{ $matches->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
