<?php

namespace App\Livewire\SearchAgents;

use App\Models\Location;
use App\Models\SearchAgent;
use App\Support\SearchAgents\RecentMatchCounter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Validator;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

class Manager extends Component
{
    use WithPagination;

    /**
     * The radius choices offered by the slider, in kilometres.
     */
    public const RADIUS_STEPS = [0, 5, 10, 50, 100];

    /**
     * Enough to list every postcode of the largest city (Berlin has about 190).
     */
    public const LOCATION_RESULT_LIMIT = 200;

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $search = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public int $perPage = 10;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|digits:5')]
    public ?string $postcode = null;

    public string $locationSearch = '';

    #[Validate('required|in:0,5,10,50,100')]
    public string $radius = '0';

    #[Validate('nullable|integer|min:0')]
    public ?string $price_from = null;

    #[Validate('nullable|integer|min:0')]
    public ?string $price_to = null;

    #[Validate('nullable|integer|min:0')]
    public ?string $size_from = null;

    #[Validate('nullable|integer|min:0')]
    public ?string $size_to = null;

    #[Validate('nullable|integer|min:0|max:20')]
    public ?string $min_rooms = null;

    #[Validate('nullable|integer|min:0|max:20')]
    public ?string $max_rooms = null;

    #[Validate('nullable|numeric')]
    public ?string $pot_return_from = null;

    #[Validate('nullable|numeric')]
    public ?string $pot_return_to = null;

    /**
     * Field names as the form labels them, so error messages read like the form.
     *
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'title' => __('Title'),
            'postcode' => __('Ort'),
            'radius' => __('Radius (km)'),
            'price_from' => __('Price from (€)'),
            'price_to' => __('Price to (€)'),
            'size_from' => __('Size m² from'),
            'size_to' => __('Size m² to'),
            'min_rooms' => __('Min. rooms'),
            'max_rooms' => __('Max. rooms'),
            'pot_return_from' => __('Potential return % from'),
            'pot_return_to' => __('Potential return % to'),
        ];
    }

    public function mount(): void
    {
        if (request()->routeIs('search-agents.create')) {
            $this->showForm = true;

            return;
        }

        if (request()->routeIs('search-agents.edit')) {
            $this->loadForEditing((int) request()->route('searchAgent'));
        }
    }

    public function getSearchAgentsProperty(): LengthAwarePaginator
    {
        return auth()->user()->searchAgents()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('title', 'like', "%{$this->search}%")
                        ->orWhere('postcode', 'like', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    /**
     * Locations whose postcode or city name starts with the typed text.
     *
     * Holds one entry more than the limit when further matches exist.
     *
     * @return Collection<int, Location>
     */
    public function getLocationResultsProperty(): Collection
    {
        $term = trim($this->locationSearch);

        if (mb_strlen($term) < 2) {
            return new Collection;
        }

        $prefix = addcslashes($term, '%_\\').'%';

        return Location::query()
            ->where('postcode', 'like', $prefix)
            ->orWhere('city_name', 'like', $prefix)
            ->orderBy('city_name')
            ->orderBy('postcode')
            ->limit(self::LOCATION_RESULT_LIMIT + 1)
            ->get();
    }

    /**
     * Typing invalidates the previous choice; a complete, known postcode counts as a choice of its own.
     */
    public function updatedLocationSearch(): void
    {
        $term = trim($this->locationSearch);

        $this->postcode = Location::where('postcode', $term)->exists() ? $term : null;
        $this->resetErrorBag('postcode');
    }

    public function selectLocation(int $locationId): void
    {
        $location = Location::findOrFail($locationId);

        $this->postcode = $location->postcode;
        $this->locationSearch = "{$location->postcode} {$location->city_name}";
        $this->resetErrorBag('postcode');
    }

    public function save(): void
    {
        $this->withValidator(fn (Validator $validator) => $validator->after(function (Validator $validator): void {
            if ($this->postcode === null && trim($this->locationSearch) !== '') {
                $validator->errors()->add('postcode', __('Bitte wähle einen Ort aus der Liste aus.'));
            }
        }));

        // A field the user emptied arrives as '', which the numeric columns reject.
        $data = array_map(fn (mixed $value): mixed => $value === '' ? null : $value, $this->validate());

        if ($this->editingId) {
            $searchAgent = auth()->user()->searchAgents()->findOrFail($this->editingId);
            $searchAgent->update($data);

            $this->dispatch('search-agent-updated', details: $this->recentMatchSummary($searchAgent));

            return;
        }

        auth()->user()->searchAgents()->create($data);

        $this->redirectRoute('search-agents.index', navigate: true);
    }

    public function delete(int $id): void
    {
        auth()->user()->searchAgents()->findOrFail($id)->delete();
    }

    private function loadForEditing(int $id): void
    {
        $searchAgent = auth()->user()->searchAgents()->findOrFail($id);

        $this->editingId = $searchAgent->id;
        $this->title = $searchAgent->title;
        $this->postcode = $searchAgent->postcode;
        $this->locationSearch = trim($searchAgent->postcode.' '.Location::where('postcode', $searchAgent->postcode)->value('city_name'));
        $this->radius = $this->nearestRadiusStep($searchAgent->radius);
        $this->price_from = $this->withoutDecimals($searchAgent->price_from);
        $this->price_to = $this->withoutDecimals($searchAgent->price_to);
        $this->size_from = $searchAgent->size_from;
        $this->size_to = $searchAgent->size_to;
        $this->min_rooms = $searchAgent->min_rooms;
        $this->max_rooms = $searchAgent->max_rooms;
        $this->pot_return_from = $searchAgent->pot_return_from;
        $this->pot_return_to = $searchAgent->pot_return_to;

        $this->showForm = true;
    }

    /**
     * Search agents saved before the slider existed may hold any radius, or none.
     */
    private function nearestRadiusStep(?string $radius): string
    {
        if ($radius === null) {
            return $this->radius;
        }

        return (string) collect(self::RADIUS_STEPS)
            ->sortBy(fn (int $step): float => abs($step - (float) $radius))
            ->first();
    }

    private function recentMatchSummary(SearchAgent $searchAgent): string
    {
        $count = (new RecentMatchCounter)->count($searchAgent);

        return $count === 1
            ? __('In den letzten 7 Tagen passte 1 Inserat zu diesen Suchkriterien.')
            : __('In den letzten 7 Tagen passten :count Inserate zu diesen Suchkriterien.', ['count' => $count]);
    }

    private function withoutDecimals(?string $amount): ?string
    {
        return $amount === null ? null : (string) (int) round((float) $amount);
    }

    public function render()
    {
        return view('livewire.search-agents.manager', [
            'searchAgents' => $this->showForm ? collect() : $this->searchAgents,
        ]);
    }
}
