<?php

namespace Tests\Feature;

use App\Filament\Widgets\KpiStatsOverview;
use App\Filament\Widgets\UserStatsOverview;
use App\Mail\SearchAgentMatchesFound;
use App\Models\Listing;
use App\Models\SearchAgent;
use App\Models\SearchAgentListingMatch;
use App\Models\User;
use App\Support\Metrics\KpiCalculator;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class KpiDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-01 12:00:00'));
    }

    public function test_opening_the_results_page_from_the_email_records_a_click(): void
    {
        $user = $this->createUser(now());
        $match = $this->createMatch($this->createAgent($user, now()), now(), emailedAt: now());

        $this->get($this->emailLink($match))->assertOk();

        $this->assertDatabaseHas('search_agent_match_clicks', [
            'search_agent_listing_match_id' => $match->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_opening_the_results_page_without_the_email_marker_records_no_click(): void
    {
        $match = $this->createMatch($this->createAgent($this->createUser(now()), now()), now(), emailedAt: now());

        $this->get(route('search-agent-results.show', $match->public_token))->assertOk();

        $this->assertDatabaseCount('search_agent_match_clicks', 0);
    }

    public function test_the_result_email_links_to_the_results_page_with_the_email_marker(): void
    {
        $agent = $this->createAgent($this->createUser(now()), now());
        $match = $this->createMatch($agent, now(), emailedAt: now());

        (new SearchAgentMatchesFound($agent, $match))->assertSeeInHtml($this->emailLink($match), false);
    }

    public function test_activation_counts_users_who_created_a_search_agent_on_their_first_day(): void
    {
        $registeredAt = now()->subDays(3);

        $this->createAgent($this->createUser($registeredAt), $registeredAt->copy()->addHours(2));
        $this->createAgent($this->createUser($registeredAt), $registeredAt->copy()->addHours(30));
        $this->createUser($registeredAt);

        $result = (new KpiCalculator)->activation();

        $this->assertSame([1, 3], [$result->hits, $result->total]);
    }

    public function test_activation_reports_users_in_their_first_day_as_pending_instead_of_rating_them(): void
    {
        $this->createAgent($this->createUser(now()->subMinutes(5)), now()->subMinutes(4));
        $this->createUser(now()->subMinutes(2));

        $result = (new KpiCalculator)->activation();

        $this->assertSame([0, 0, 2], [$result->hits, $result->total, $result->pending]);
    }

    public function test_first_hit_counts_search_agents_with_a_listing_within_48_hours(): void
    {
        $createdAt = now()->subDays(5);
        $user = $this->createUser($createdAt);

        $this->createMatch($this->createAgent($user, $createdAt), $createdAt->copy()->addHours(10));
        $this->createMatch($this->createAgent($user, $createdAt), $createdAt->copy()->addHours(60));
        $this->createMatch($this->createAgent($user, $createdAt), $createdAt->copy()->addHours(10), listingIds: []);
        $this->createAgent($user, now()->subHours(3));

        $result = (new KpiCalculator)->firstHit();

        $this->assertSame([1, 3, 1], [$result->hits, $result->total, $result->pending]);
    }

    public function test_click_rate_counts_sent_emails_with_at_least_one_click(): void
    {
        $user = $this->createUser(now()->subDays(3));
        $agent = $this->createAgent($user, now()->subDays(3));

        $clicked = $this->createMatch($agent, now()->subDay(), emailedAt: now()->subDay());
        $this->createMatch($agent, now()->subDay(), emailedAt: now()->subDay());
        $this->createMatch($agent, now()->subDay());

        $this->get($this->emailLink($clicked));
        $this->get($this->emailLink($clicked));

        $result = (new KpiCalculator)->clickRate();

        $this->assertSame([1, 2], [$result->hits, $result->total]);
    }

    public function test_retention_counts_users_who_still_click_in_their_fourth_week(): void
    {
        $registeredAt = now()->subDays(30);

        $retained = $this->createUser($registeredAt);
        $droppedOff = $this->createUser($registeredAt);
        $this->createUser(now()->subDays(10));

        $this->clickEmail($retained, $registeredAt->copy()->addDays(25));
        $this->clickEmail($droppedOff, $registeredAt->copy()->addDays(5));

        $result = (new KpiCalculator)->retention();

        $this->assertSame([1, 2], [$result->hits, $result->total]);
    }

    public function test_saved_listing_rate_counts_active_users_with_a_saved_listing(): void
    {
        $listing = new Listing(['title' => 'Merkenswert']);
        $listing->forceFill(['hash' => hash('sha256', 'Merkenswert')])->save();

        $activeAndSaving = $this->createUser(now()->subDays(3));
        $activeOnly = $this->createUser(now()->subDays(3));
        $savingOnly = $this->createUser(now()->subDays(3));

        $this->clickEmail($activeAndSaving, now()->subDay());
        $this->clickEmail($activeOnly, now()->subDay());
        $activeAndSaving->savedListings()->attach($listing->id);
        $savingOnly->savedListings()->attach($listing->id);

        $result = (new KpiCalculator)->savedListingRate();

        $this->assertSame([1, 2], [$result->hits, $result->total]);
    }

    public function test_the_period_limits_which_users_are_measured(): void
    {
        $this->createUser(now()->subDays(40));
        $this->createUser(now()->subDays(5));

        $result = (new KpiCalculator(now()->subDays(10), now()))->activation();

        $this->assertSame(1, $result->total);
    }

    public function test_a_kpi_without_data_has_no_percentage(): void
    {
        $this->assertNull((new KpiCalculator)->clickRate()->percentage());
    }

    public function test_the_dashboard_block_shows_each_kpi_against_its_target(): void
    {
        $registeredAt = now()->subDays(3);
        $this->createAgent($this->createUser($registeredAt), $registeredAt->copy()->addHour());

        $this->actingAs(User::factory()->create(['created_at' => now()]));

        Livewire::test(KpiStatsOverview::class)
            ->assertSeeInOrder(['Aktivierung', '100 %', '1 von 1', 'Ziel 50 %', '1 noch im ersten Tag'])
            ->assertSeeInOrder(['Klickrate', 'Noch keine Daten', 'Ziel 25 %']);
    }

    public function test_the_user_block_shows_how_many_users_have_a_search_agent(): void
    {
        $owner = $this->createUser(now()->subDays(3));
        $this->createAgent($owner, now()->subDays(3));
        $this->createAgent($owner, now()->subDays(2));
        $this->createUser(now()->subDays(3));
        $this->createUser(now()->subDays(3));

        $this->actingAs($this->createUser(now()));

        Livewire::test(UserStatsOverview::class)
            ->assertSeeInOrder(['Benutzer', '4', 'Benutzer mit Suchagent', '1', '25 % aller Benutzer']);
    }

    public function test_the_user_block_only_counts_users_registered_in_the_selected_period(): void
    {
        $this->createAgent($this->createUser(now()->subDays(40)), now()->subDays(40));
        $this->createAgent($this->createUser(now()->subDays(5)), now()->subDays(5));

        $this->actingAs($this->createUser(now()->subDays(2)));

        Livewire::test(UserStatsOverview::class, ['filters' => ['dateRange' => [
            'start' => now()->subDays(10)->toDateString(),
            'end' => now()->toDateString(),
        ]]])
            ->assertSeeInOrder(['Benutzer', '2', 'Benutzer mit Suchagent', '1', '50 % aller Benutzer']);
    }

    private function createUser(CarbonInterface $registeredAt): User
    {
        return User::factory()->create(['created_at' => $registeredAt]);
    }

    private function createAgent(User $user, CarbonInterface $createdAt): SearchAgent
    {
        $agent = new SearchAgent(['user_id' => $user->id, 'title' => 'Berlin']);
        $agent->forceFill(['created_at' => $createdAt])->save();

        return $agent;
    }

    /**
     * @param  list<int>  $listingIds
     */
    private function createMatch(SearchAgent $agent, CarbonInterface $matchedAt, array $listingIds = [1], ?CarbonInterface $emailedAt = null): SearchAgentListingMatch
    {
        return SearchAgentListingMatch::create([
            'search_agent_id' => $agent->id,
            'user_id' => $agent->user_id,
            'listing_ids_json' => $listingIds,
            'match_count' => count($listingIds),
            'public_token' => bin2hex(random_bytes(16)),
            'matched_at' => $matchedAt,
            'emailed_at' => $emailedAt,
        ]);
    }

    private function clickEmail(User $user, CarbonInterface $clickedAt): void
    {
        $match = $this->createMatch($this->createAgent($user, $user->created_at), $clickedAt, emailedAt: $clickedAt);

        $match->clicks()->create(['user_id' => $user->id, 'clicked_at' => $clickedAt]);
    }

    private function emailLink(SearchAgentListingMatch $match): string
    {
        return route('search-agent-results.show', [
            'token' => $match->public_token,
            'ref' => SearchAgentMatchesFound::LINK_REFERRER,
        ]);
    }
}
