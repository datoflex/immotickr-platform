<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ __('Suchaufträge') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ auth()->user()->searchAgents()->count() }}</p>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">{{ __('Gefundene Treffer') }}</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ auth()->user()->listingMatches()->whereNotNull('emailed_at')->sum('match_count') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
