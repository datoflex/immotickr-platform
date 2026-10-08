<?php

namespace Tests\Feature;

use App\Livewire\SearchAgents\Manager;
use App\Models\Location;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchAgentLocationFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Location::create(['postcode' => '10115', 'city_name' => 'Berlin']);
        Location::create(['postcode' => '10117', 'city_name' => 'Berlin']);
        Location::create(['postcode' => '04109', 'city_name' => 'Leipzig']);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_typing_a_city_name_suggests_its_locations(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', 'Ber')
            ->assertSeeInOrder(['10115', 'Berlin', '10117', 'Berlin'])
            ->assertDontSee('Leipzig');
    }

    public function test_a_city_with_many_postcodes_lists_all_of_them(): void
    {
        foreach (range(20001, 20100) as $postcode) {
            Location::create(['postcode' => (string) $postcode, 'city_name' => 'Hamburg']);
        }

        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', 'Hamburg')
            ->assertSeeInOrder(['20001', '20050', '20100'])
            ->assertDontSee('Weitere Treffer vorhanden');
    }

    public function test_a_search_with_more_matches_than_can_be_listed_asks_for_a_more_precise_term(): void
    {
        foreach (range(30001, 30000 + Manager::LOCATION_RESULT_LIMIT + 1) as $postcode) {
            Location::create(['postcode' => (string) $postcode, 'city_name' => 'Hannover']);
        }

        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', 'Hannover')
            ->assertSee((string) (30000 + Manager::LOCATION_RESULT_LIMIT))
            ->assertDontSee((string) (30000 + Manager::LOCATION_RESULT_LIMIT + 1))
            ->assertSee('Weitere Treffer vorhanden');
    }

    public function test_typing_the_start_of_a_postcode_suggests_matching_locations(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', '041')
            ->assertSee('Leipzig')
            ->assertDontSee('Berlin');
    }

    public function test_typing_something_unknown_says_that_no_location_was_found(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', 'Atlantis')
            ->assertSee('Kein Ort gefunden.');
    }

    public function test_a_selected_location_is_saved_as_the_search_agents_postcode(): void
    {
        $location = Location::where('postcode', '10117')->sole();

        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin Mitte')
            ->set('locationSearch', 'Ber')
            ->call('selectLocation', $location->id)
            ->assertSet('locationSearch', '10117 Berlin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('10117', $this->user->searchAgents()->sole()->postcode);
    }

    public function test_a_fully_typed_known_postcode_is_accepted_without_picking_from_the_list(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Leipzig')
            ->set('locationSearch', '04109')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('04109', $this->user->searchAgents()->sole()->postcode);
    }

    public function test_typed_text_that_was_not_picked_from_the_list_is_rejected(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Irgendwo')
            ->set('locationSearch', 'Ber')
            ->call('save')
            ->assertHasErrors('postcode');

        $this->assertSame(0, $this->user->searchAgents()->count());
    }

    public function test_saving_an_invalid_form_shows_a_message_below_every_affected_field(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('locationSearch', 'Ber')
            ->set('price_from', '-5')
            ->set('min_rooms', '99')
            ->call('save')
            ->assertHasErrors(['title', 'postcode', 'price_from', 'min_rooms'])
            ->assertSee('Titel muss ausgefüllt werden.')
            ->assertSee('Bitte wähle einen Ort aus der Liste aus.')
            ->assertSee('Preis ab (€) muss mindestens 0 sein.')
            ->assertSee('Min. Zimmer darf maximal 20 sein.');
    }

    public function test_changing_the_text_after_a_selection_discards_the_selection(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->call('selectLocation', Location::where('postcode', '10115')->sole()->id)
            ->set('locationSearch', '10115 Berli')
            ->assertSet('postcode', null);
    }

    public function test_a_search_agent_can_still_be_saved_without_a_location(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Überall')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($this->user->searchAgents()->sole()->postcode);
    }

    public function test_editing_a_search_agent_shows_its_postcode_with_the_city_name(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Leipzig', 'postcode' => '04109']);

        $this->get(route('search-agents.edit', $agent))
            ->assertOk()
            ->assertSee('04109 Leipzig');
    }
}
