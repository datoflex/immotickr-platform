<?php

namespace Tests\Feature;

use App\Livewire\SearchAgents\Manager;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchAgentRadiusSliderTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_a_new_search_agent_starts_with_a_radius_of_zero_kilometres(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(0, $this->user->searchAgents()->sole()->radius);
    }

    public function test_a_radius_chosen_on_the_slider_is_saved(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->set('radius', '50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertEquals(50, $this->user->searchAgents()->sole()->radius);
    }

    public function test_a_radius_that_is_not_on_the_slider_is_rejected(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->set('radius', '25')
            ->call('save')
            ->assertHasErrors('radius');

        $this->assertSame(0, $this->user->searchAgents()->count());
    }

    public function test_editing_an_older_search_agent_moves_its_radius_to_the_nearest_slider_step(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Alt', 'radius' => 35]);

        $this->get(route('search-agents.edit', $agent))
            ->assertOk()
            ->assertSeeInOrder(['Radius', '50 km', '5 km']);
    }

    public function test_editing_a_search_agent_without_a_radius_starts_at_zero_kilometres(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Ohne Radius']);

        $this->get(route('search-agents.edit', $agent))
            ->assertOk()
            ->assertSeeInOrder(['Radius', '0 km', '0 km', '5 km']);
    }
}
