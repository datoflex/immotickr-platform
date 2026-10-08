<?php

namespace Tests\Feature;

use App\Livewire\SearchAgents\Manager;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class SearchAgentUpdateStaysInFormTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_updating_a_search_agent_saves_and_stays_in_the_form(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Alt']);

        $this->editForm($agent)
            ->set('title', 'Neu')
            ->call('save')
            ->assertHasNoErrors()
            ->assertNoRedirect()
            ->assertSet('showForm', true)
            ->assertDispatched('search-agent-updated');

        $this->assertSame('Neu', $agent->refresh()->title);
    }

    public function test_emptying_optional_fields_saves_them_as_not_set(): void
    {
        $agent = SearchAgent::create([
            'user_id' => $this->user->id,
            'title' => 'Alt',
            'price_from' => 100000,
            'size_from' => 50,
            'min_rooms' => 2,
            'pot_return_from' => 4,
        ]);

        $this->editForm($agent)
            ->set('price_from', '')
            ->set('size_from', '')
            ->set('min_rooms', '')
            ->set('pot_return_from', '')
            ->set('size_to', '100')
            ->call('save')
            ->assertHasNoErrors();

        $agent->refresh();

        $this->assertSame(
            [null, null, null, null, 100],
            [$agent->price_from, $agent->size_from, $agent->min_rooms, $agent->pot_return_from, $agent->size_to],
        );
    }

    public function test_a_failed_update_shows_no_confirmation(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Alt']);

        $this->editForm($agent)
            ->set('title', '')
            ->call('save')
            ->assertHasErrors('title')
            ->assertNotDispatched('search-agent-updated');

        $this->assertSame('Alt', $agent->refresh()->title);
    }

    public function test_the_edit_form_offers_to_close_and_the_create_form_to_cancel(): void
    {
        $agent = SearchAgent::create(['user_id' => $this->user->id, 'title' => 'Alt']);

        $this->editForm($agent)
            ->assertSee('Schließen')
            ->assertDontSee('Abbrechen');

        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->assertSee('Abbrechen')
            ->assertDontSee('Schließen');
    }

    public function test_creating_a_search_agent_still_returns_to_the_list(): void
    {
        Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('title', 'Berlin')
            ->call('save')
            ->assertRedirect(route('search-agents.index'));
    }

    private function editForm(SearchAgent $agent): Testable
    {
        return Livewire::test(Manager::class)
            ->set('showForm', true)
            ->set('editingId', $agent->id)
            ->set('title', $agent->title);
    }
}
