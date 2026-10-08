<x-app-layout>
    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-8">
            {{-- The subnav must be a direct child of the full-height container to stay sticky while scrolling. --}}
            @include('search-results.partials.subnav')
            <p class="!mt-4 px-4 sm:px-0 text-sm text-gray-500">{{ __('Inserate, die du dir gemerkt hast.') }}</p>

            <livewire:listings.saved-list />
        </div>
    </div>
</x-app-layout>
