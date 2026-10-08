<?php

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\SearchAgent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminUserListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login(): void
    {
        $this->get(UserResource::getUrl('index'))->assertRedirect('/admin/login');
    }

    public function test_the_admin_user_list_shows_every_user_and_sorts_by_search_agent_count(): void
    {
        $admin = User::factory()->create();
        $investor = User::factory()->create();
        SearchAgent::create(['user_id' => $investor->id, 'title' => 'Berlin']);
        SearchAgent::create(['user_id' => $investor->id, 'title' => 'Leipzig']);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $investor])
            ->sortTable('search_agents_count', 'desc')
            ->assertCanSeeTableRecords([$investor, $admin], inOrder: true)
            ->sortTable('search_agents_count', 'asc')
            ->assertCanSeeTableRecords([$admin, $investor], inOrder: true);
    }

    public function test_the_admin_user_list_can_be_limited_to_unverified_users(): void
    {
        $verified = User::factory()->create();
        $unverified = User::factory()->unverified()->create();

        $this->actingAs($verified);

        Livewire::test(ListUsers::class)
            ->filterTable('email_verified_at', false)
            ->assertCanSeeTableRecords([$unverified])
            ->assertCanNotSeeTableRecords([$verified]);
    }
}
