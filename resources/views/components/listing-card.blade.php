@props(['listing', 'actions' => null])

<div {{ $attributes->merge(['class' => 'bg-white shadow sm:rounded-lg p-4 sm:p-6']) }}>
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2">
        <div>
            <h2 class="text-base font-medium text-gray-900">{{ $listing->title ?? __('Ohne Titel') }}</h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $listing->raw_address ?? trim(($listing->zip ?? '') . ' ' . ($listing->city ?? '')) }}
            </p>
        </div>
        <div class="text-left sm:text-right shrink-0">
            <p class="text-lg font-semibold text-gray-900">{{ $listing->price ?? '—' }}</p>
            @if ($listing->price_m2)
                <p class="text-xs text-gray-500">{{ $listing->price_m2 }} / m²</p>
            @endif
        </div>
    </div>

    <dl class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
        @if ($listing->flaeche)
            <div>
                <dt class="text-gray-500">{{ __('Größe') }}</dt>
                <dd class="text-gray-900">{{ $listing->flaeche }}</dd>
            </div>
        @endif
        @if ($listing->zimmer)
            <div>
                <dt class="text-gray-500">{{ __('Zimmer') }}</dt>
                <dd class="text-gray-900">{{ $listing->zimmer }}</dd>
            </div>
        @endif
        @if ($listing->baujahr)
            <div>
                <dt class="text-gray-500">{{ __('Baujahr') }}</dt>
                <dd class="text-gray-900">{{ $listing->baujahr }}</dd>
            </div>
        @endif
        @if ($listing->rendite_pot)
            <div>
                <dt class="text-gray-500">{{ __('Rendite (pot.)') }}</dt>
                <dd class="text-gray-900">{{ $listing->rendite_pot }}</dd>
            </div>
        @endif
    </dl>

    @if ($listing->source_url || $listing->detail_url || $actions)
        <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
            @if ($listing->source_url || $listing->detail_url)
                <a
                    href="{{ $listing->source_url ?: $listing->detail_url }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-sm font-medium text-indigo-600 hover:text-indigo-900"
                >
                    {{ __('Inserat ansehen') }} &rarr;
                </a>
            @endif

            @if ($actions)
                <div class="ms-auto">{{ $actions }}</div>
            @endif
        </div>
    @endif
</div>
