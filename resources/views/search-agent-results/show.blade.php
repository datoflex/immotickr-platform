<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ __('Suchagent Treffer') }} – {{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            <header class="bg-white shadow">
                <div class="max-w-5xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <p class="text-xs font-semibold uppercase tracking-wider text-teal-700">Immotickr</p>
                    <h1 class="mt-1 font-semibold text-xl text-gray-800 leading-tight">
                        {{ __('Treffer für Suchagent ":title"', ['title' => $searchAgent->title]) }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ trans_choice(':count Treffer|:count Treffer', $listings->count(), ['count' => $listings->count()]) }}
                    </p>
                </div>
            </header>

            <main class="py-8">
                <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
                    @forelse ($listings as $listing)
                        <x-listing-card :listing="$listing" />
                    @empty
                        <div class="bg-white shadow sm:rounded-lg p-6 text-sm text-gray-500">
                            {{ __('Für diesen Lauf wurden keine Treffer gefunden.') }}
                        </div>
                    @endforelse
                </div>
            </main>
        </div>
    </body>
</html>
