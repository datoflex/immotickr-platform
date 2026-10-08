<?php

namespace Tests\Feature;

use App\Livewire\SearchAgents\Manager;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SearchAgentPriceFieldTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_whole_euro_prices_are_saved(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->set('price_from', '100000')
            ->set('price_to', '250000')
            ->call('save')
            ->assertHasNoErrors();

        $agent = $this->user->searchAgents()->sole();

        $this->assertEquals([100000, 250000], [$agent->price_from, $agent->price_to]);
    }

    public function test_prices_with_decimals_are_rejected(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->set('price_from', '100000.50')
            ->call('save')
            ->assertHasErrors('price_from')
            ->assertSee('Preis ab (€) muss eine ganze Zahl sein.');

        $this->assertSame(0, $this->user->searchAgents()->count());
    }

    public function test_editing_a_search_agent_shows_its_prices_without_decimals(): void
    {
        $agent = SearchAgent::create([
            'user_id' => $this->user->id,
            'title' => 'Berlin',
            'price_from' => 100000,
            'price_to' => 250000.40,
        ]);

        $this->get(route('search-agents.edit', $agent))
            ->assertOk()
            ->assertSee('"price_from":"100000"')
            ->assertSee('"price_to":"250000"');
    }
}
