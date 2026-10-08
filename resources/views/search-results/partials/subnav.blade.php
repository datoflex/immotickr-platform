<nav class="sticky top-0 z-10 -mt-4 flex gap-6 border-b border-gray-200 bg-gray-100 px-4 pt-4 sm:px-0" aria-label="{{ __('Suchergebnisse') }}">
    @foreach ([
        'search-results.index' => __('Suchergebnisse'),
        'search-results.saved' => __('Merkliste'),
    ] as $routeName => $label)
        <a
            href="{{ route($routeName) }}"
            wire:navigate
            @class([
                '-mb-px border-b-2 px-1 pb-2 text-sm font-medium transition',
                'border-teal-600 text-teal-700' => request()->routeIs($routeName),
                'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700' => ! request()->routeIs($routeName),
            ])
            @if (request()->routeIs($routeName)) aria-current="page" @endif
        >
            {{ $label }}
            @if ($routeName === 'search-results.saved')
                <livewire:listings.saved-count />
            @endif
        </a>
    @endforeach
</nav>
