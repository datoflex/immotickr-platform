<div class="space-y-6">
    @if ($showForm)
        <form wire:submit="save" novalidate class="space-y-6">
            <div class="p-4 sm:p-6 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('Basic data') }}</h4>
                <div>
                    <x-input-label for="title" :value="__('Title').' *'" />
                    <x-text-input wire:model="title" id="title" type="text" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false" class="relative">
                        <x-input-label for="location" :value="__('Ort')" />
                        <x-text-input
                            wire:model.live.debounce.300ms="locationSearch"
                            x-on:focus="open = true"
                            x-on:input="open = true"
                            id="location"
                            type="text"
                            autocomplete="off"
                            placeholder="{{ __('Postleitzahl oder Ort suchen') }}"
                            class="mt-1 block w-full"
                        />

                        @if ($this->locationResults->isNotEmpty())
                            <ul x-show="open" class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                                @foreach ($this->locationResults->take(\App\Livewire\SearchAgents\Manager::LOCATION_RESULT_LIMIT) as $location)
                                    <li wire:key="location-{{ $location->id }}">
                                        <button
                                            type="button"
                                            wire:click="selectLocation({{ $location->id }})"
                                            x-on:click="open = false"
                                            class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 focus:bg-gray-100 focus:outline-none"
                                        >
                                            <span class="font-medium text-gray-900">{{ $location->postcode }}</span>
                                            {{ $location->city_name }}
                                        </button>
                                    </li>
                                @endforeach
                                @if ($this->locationResults->count() > \App\Livewire\SearchAgents\Manager::LOCATION_RESULT_LIMIT)
                                    <li class="px-3 py-2 text-xs text-gray-500">{{ __('Weitere Treffer vorhanden – bitte genauer eingeben.') }}</li>
                                @endif
                            </ul>
                        @elseif ($postcode === null && mb_strlen(trim($locationSearch)) >= 2)
                            <p class="mt-2 text-sm text-gray-500">{{ __('Kein Ort gefunden.') }}</p>
                        @endif

                        <x-input-error :messages="$errors->get('postcode')" class="mt-2" />
                    </div>
                    <div
                        x-data="{ steps: @js(\App\Livewire\SearchAgents\Manager::RADIUS_STEPS), index: 0 }"
                        x-init="index = Math.max(0, steps.indexOf(Number($wire.radius)))"
                    >
                        <x-input-label for="radius">
                            {{ __('Radius') }}: <span x-text="steps[index] + ' km'">{{ $radius }} km</span>
                        </x-input-label>
                        <div wire:ignore>
                            <input
                                x-model.number="index"
                                x-on:input="$wire.radius = String(steps[index])"
                                x-bind:aria-valuetext="steps[index] + ' km'"
                                id="radius"
                                type="range"
                                min="0"
                                max="{{ count(\App\Livewire\SearchAgents\Manager::RADIUS_STEPS) - 1 }}"
                                step="1"
                                class="mt-3 block w-full accent-indigo-600"
                            />
                            <div class="mt-1 flex justify-between text-xs text-gray-500" aria-hidden="true">
                                @foreach (\App\Livewire\SearchAgents\Manager::RADIUS_STEPS as $step)
                                    <span>{{ $step }} km</span>
                                @endforeach
                            </div>
                        </div>
                        <x-input-error :messages="$errors->get('radius')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('Price and return') }}</h4>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="price_from" :value="__('Price from (€)')" />
                        <x-unit-input wire:model="price_from" id="price_from" step="1" unit="€" />
                        <x-input-error :messages="$errors->get('price_from')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="price_to" :value="__('Price to (€)')" />
                        <x-unit-input wire:model="price_to" id="price_to" step="1" unit="€" />
                        <x-input-error :messages="$errors->get('price_to')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="pot_return_from" :value="__('Potential return % from')" />
                        <x-unit-input wire:model="pot_return_from" id="pot_return_from" step="0.01" unit="%" />
                        <x-input-error :messages="$errors->get('pot_return_from')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="pot_return_to" :value="__('Potential return % to')" />
                        <x-unit-input wire:model="pot_return_to" id="pot_return_to" step="0.01" unit="%" />
                        <x-input-error :messages="$errors->get('pot_return_to')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="p-4 sm:p-6 bg-gray-50 border border-gray-200 rounded-lg space-y-4">
                <h4 class="text-sm font-semibold text-gray-700">{{ __('Objektmerkmale') }}</h4>
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <x-input-label for="size_from" :value="__('Size m² from')" />
                        <x-unit-input wire:model="size_from" id="size_from" unit="m²" />
                        <x-input-error :messages="$errors->get('size_from')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="size_to" :value="__('Size m² to')" />
                        <x-unit-input wire:model="size_to" id="size_to" unit="m²" />
                        <x-input-error :messages="$errors->get('size_to')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="min_rooms" :value="__('Min. rooms')" />
                        <x-text-input wire:model="min_rooms" id="min_rooms" type="number" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('min_rooms')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="max_rooms" :value="__('Max. rooms')" />
                        <x-text-input wire:model="max_rooms" id="max_rooms" type="number" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('max_rooms')" class="mt-2" />
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <x-primary-button type="submit">
                    {{ $editingId ? __('Update search agent') : __('Create search agent') }}
                </x-primary-button>
                <a
                    href="{{ route('search-agents.index') }}"
                    wire:navigate
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    {{ $editingId ? __('Schließen') : __('Cancel') }}
                </a>
            </div>

            <div
                x-data="{ shown: false, details: '', timeout: null }"
                x-on:search-agent-updated.window="clearTimeout(timeout); details = $event.detail.details; shown = true; timeout = setTimeout(() => shown = false, 8000)"
                x-show="shown"
                x-transition.opacity
                style="display: none;"
                role="status"
                class="fixed left-1/2 top-4 z-50 -translate-x-1/2 flex w-max max-w-[calc(100vw-2rem)] items-start gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 shadow-lg sm:max-w-md"
            >
                <x-heroicon-o-check-circle class="h-5 w-5 shrink-0 text-green-600" />
                <div>
                    <p class="font-medium">{{ __('Suchagent wurde gespeichert.') }}</p>
                    <p x-text="details" class="mt-0.5"></p>
                </div>
            </div>
        </form>
    @else
        @php
            $sortIcon = fn (string $field) => $sortField !== $field
                ? 'heroicon-m-chevron-up-down'
                : ($sortDirection === 'asc' ? 'heroicon-m-chevron-up' : 'heroicon-m-chevron-down');
            $sortIconClass = fn (string $field) => $sortField === $field ? 'text-indigo-600' : 'text-gray-400';
        @endphp

        <div class="flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">{{ __('Your search agents') }}</h3>

            <a
                href="{{ route('search-agents.create') }}"
                wire:navigate
                class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                <x-heroicon-m-plus class="h-4 w-4" />
                {{ __('New search agent') }}
            </a>
        </div>

        <div class="bg-white ring-1 ring-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 border-b border-gray-100 px-4 py-3">
                <div class="relative flex-1">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search by: Title, Postcode') }}"
                        class="block w-full rounded-lg border-gray-300 pl-9 text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500"
                    />
                </div>

                <div class="flex items-center gap-1.5 text-sm text-gray-500">
                    <x-heroicon-o-bars-3-bottom-left class="h-4 w-4 text-gray-400" />
                    <select wire:model.live="perPage" class="rounded-lg border-gray-300 py-1.5 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>

            @if ($searchAgents->isEmpty())
                <div class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <x-heroicon-o-magnifying-glass class="h-6 w-6 text-gray-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            {{ $search !== '' ? __('No search agents match your search.') : __("You don't have any search agents yet.") }}
                        </p>
                        @if ($search === '')
                            <p class="mt-1 text-sm text-gray-500">{{ __('Create one to get notified about matching listings.') }}</p>
                        @endif
                    </div>
                    @if ($search === '')
                        <a
                            href="{{ route('search-agents.create') }}"
                            wire:navigate
                            class="mt-2 inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-700 transition ease-in-out duration-150"
                        >
                            <x-heroicon-m-plus class="h-4 w-4" />
                            {{ __('New search agent') }}
                        </a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50/75">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                    <button wire:click="sortBy('title')" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                        {{ __('Title') }}
                                        <x-dynamic-component :component="$sortIcon('title')" class="h-3.5 w-3.5 {{ $sortIconClass('title') }}" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                    <button wire:click="sortBy('radius')" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                        {{ __('Radius') }}
                                        <x-dynamic-component :component="$sortIcon('radius')" class="h-3.5 w-3.5 {{ $sortIconClass('radius') }}" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider">
                                    <button wire:click="sortBy('price_from')" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-800">
                                        {{ __('Price') }}
                                        <x-dynamic-component :component="$sortIcon('price_from')" class="h-3.5 w-3.5 {{ $sortIconClass('price_from') }}" />
                                    </button>
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($searchAgents as $searchAgent)
                                <tr wire:key="search-agent-{{ $searchAgent->id }}" class="hover:bg-gray-50/75 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $searchAgent->title }}</div>
                                        @if ($searchAgent->postcode)
                                            <div class="mt-0.5 flex items-center gap-1 text-xs text-gray-500">
                                                <x-heroicon-o-map-pin class="h-3.5 w-3.5" />
                                                {{ $searchAgent->postcode }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        @if ($searchAgent->radius)
                                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700">
                                                {{ (float) $searchAgent->radius }} km
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-gray-500">
                                        @if ($searchAgent->price_from || $searchAgent->price_to)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                                                {{ $searchAgent->price_from ? number_format($searchAgent->price_from, 0, ',', '.') . ' €' : '…' }}
                                                –
                                                {{ $searchAgent->price_to ? number_format($searchAgent->price_to, 0, ',', '.') . ' €' : '…' }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-right text-sm">
                                        <div class="flex items-center justify-end gap-1">
                                            <a
                                                href="{{ route('search-agents.edit', $searchAgent) }}"
                                                wire:navigate
                                                title="{{ __('Edit') }}"
                                                class="rounded-md p-1.5 text-blue-600 hover:bg-blue-50 transition-colors"
                                            >
                                                <x-heroicon-o-pencil-square class="h-5 w-5" />
                                                <span class="sr-only">{{ __('Edit') }}</span>
                                            </a>
                                            <button
                                                wire:click="delete({{ $searchAgent->id }})"
                                                wire:confirm="{{ __('Delete this search agent?') }}"
                                                title="{{ __('Delete') }}"
                                                class="rounded-md p-1.5 text-red-600 hover:bg-red-50 transition-colors"
                                            >
                                                <x-heroicon-o-trash class="h-5 w-5" />
                                                <span class="sr-only">{{ __('Delete') }}</span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-t border-gray-100 px-4 py-3">
                    <p class="text-sm text-gray-500">
                        {{ __('Showing results :from to :to of :total', ['from' => $searchAgents->firstItem(), 'to' => $searchAgents->lastItem(), 'total' => $searchAgents->total()]) }}
                    </p>

                    @if ($searchAgents->lastPage() > 1)
                        <div class="flex items-center gap-1">
                            <button
                                wire:click="previousPage"
                                @disabled($searchAgents->onFirstPage())
                                class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-transparent"
                            >
                                <x-heroicon-m-chevron-left class="h-4 w-4" />
                            </button>

                            @for ($page = 1; $page <= $searchAgents->lastPage(); $page++)
                                <button
                                    wire:click="gotoPage({{ $page }})"
                                    class="min-w-[2rem] rounded-md px-2 py-1 text-sm font-medium transition-colors {{ $page === $searchAgents->currentPage() ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100' }}"
                                >
                                    {{ $page }}
                                </button>
                            @endfor

                            <button
                                wire:click="nextPage"
                                @disabled(! $searchAgents->hasMorePages())
                                class="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 disabled:opacity-40 disabled:hover:bg-transparent"
                            >
                                <x-heroicon-m-chevron-right class="h-4 w-4" />
                            </button>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
