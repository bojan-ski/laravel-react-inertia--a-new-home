<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyDirectoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_view_agencies_page(): void
    {
        $this->get('/agencies')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agencies/Index'));
    }

    public function test_authenticated_user_can_view_agencies_page(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/agencies')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Agencies/Index'));
    }

    public function test_agencies_page_receives_listings_navbar_translations(): void
    {
        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listingsNav', __('app.listings.nav')));
    }

    public function test_only_approved_agencies_appear(): void
    {
        $visible = Agency::factory()->approved()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $visible->id)
            );
    }

    public function test_pending_agencies_do_not_appear(): void
    {
        $visible = Agency::factory()->approved()->create();
        Agency::factory()->pendingVerification()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $visible->id)
            );
    }

    public function test_rejected_agencies_do_not_appear(): void
    {
        $visible = Agency::factory()->approved()->create();
        Agency::factory()->rejected()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $visible->id)
            );
    }

    public function test_hidden_agencies_do_not_appear(): void
    {
        $visible = Agency::factory()->approved()->create();
        Agency::factory()->hidden()->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $visible->id)
            );
    }

    public function test_agencies_with_suspended_owner_do_not_appear(): void
    {
        $visible = Agency::factory()->approved()->create();
        Agency::factory()->approved()->for(User::factory()->agency()->suspended(), 'user')->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $visible->id)
            );
    }

    public function test_agencies_paginate_at_twelve_per_page(): void
    {
        Agency::factory()->approved()->count(13)->create();

        $this->get('/agencies')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 12)
                ->where('agencies.total', 13)
                ->where('agencies.last_page', 2)
            );

        $this->get('/agencies?page=2')
            ->assertInertia(fn (Assert $page) => $page->has('agencies.data', 1));
    }

    public function test_search_filters_by_name(): void
    {
        $match = Agency::factory()->approved()->create(['name' => 'Belgrade Premier Realty']);
        Agency::factory()->approved()->create(['name' => 'Coastal Seaside Homes']);

        $this->get('/agencies?search=belgrade')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $match->id)
            );
    }

    public function test_search_filters_by_description(): void
    {
        $match = Agency::factory()->approved()->create(['description' => 'Specialists in waterfront villas.']);
        Agency::factory()->approved()->create(['description' => 'Urban apartment experts.']);

        $this->get('/agencies?search=waterfront')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $match->id)
            );
    }

    public function test_search_filters_by_address(): void
    {
        $match = Agency::factory()->approved()->create(['address' => '12 King Street, Belgrade']);
        Agency::factory()->approved()->create(['address' => '5 Harbour Road, Split']);

        $this->get('/agencies?search=belgrade')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $match->id)
            );
    }

    public function test_search_is_case_insensitive(): void
    {
        $match = Agency::factory()->approved()->create(['name' => 'Belgrade Premier Realty']);

        $this->get('/agencies?search=BELGRADE')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 1)
                ->where('agencies.data.0.id', $match->id)
            );
    }

    public function test_empty_state_renders_when_no_agencies_match(): void
    {
        Agency::factory()->approved()->create(['name' => 'Coastal Homes']);

        $this->get('/agencies?search=nonexistent')
            ->assertInertia(fn (Assert $page) => $page
                ->has('agencies.data', 0)
                ->where('filters.search', 'nonexistent')
            );
    }

    public function test_filters_prop_reflects_active_search(): void
    {
        $this->get('/agencies?'.http_build_query(['search' => '  belgrade  ']))
            ->assertInertia(fn (Assert $page) => $page->where('filters.search', 'belgrade'));
    }

    public function test_invalid_search_input_fails_validation(): void
    {
        $this->get('/agencies?search='.str_repeat('a', 256))
            ->assertSessionHasErrors('search');
    }
}
