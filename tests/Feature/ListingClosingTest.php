<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Exceptions\InvalidListingStatusTransitionException;
use App\Models\Agency;
use App\Models\Conversation;
use App\Models\Listing;
use App\Models\User;
use App\Services\ListingService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingClosingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function agencyOwnerWithAgency(): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

    private function agencyListing(Agency $agency, string $state = 'approved', array $attributes = []): Listing
    {
        return Listing::factory()->{$state}()->create([
            'user_id' => null,
            'agency_id' => $agency->id,
            ...$attributes,
        ]);
    }

    public function test_owner_marks_approved_sale_listing_as_sold(): void
    {
        $this->freezeSecond();

        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['listing_type' => 'sale']);
        $approvedAt = $listing->approved_at->toIso8601String();

        $this->actingAs($owner)
            ->from('/profile')
            ->post("/my-listings/{$listing->id}/close")
            ->assertRedirect('/profile')
            ->assertSessionHas('success', __('app.profile.flash_closed_sold'));

        $listing->refresh();

        $this->assertSame(ListingStatus::Sold, $listing->status);
        $this->assertTrue($listing->closed_at->equalTo(now()));
        $this->assertSame($approvedAt, $listing->approved_at->toIso8601String());
    }

    public function test_owner_marks_approved_rent_listing_as_rented(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['listing_type' => 'rent']);

        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/close")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.profile.flash_closed_rented'));

        $listing->refresh();

        $this->assertSame(ListingStatus::Rented, $listing->status);
        $this->assertNotNull($listing->closed_at);
    }

    public function test_posted_status_is_ignored_and_listing_type_decides_the_target(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create(['listing_type' => 'rent']);

        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/close", ['status' => 'sold'])
            ->assertRedirect();

        $this->assertSame(ListingStatus::Rented, $listing->refresh()->status);
    }

    public function test_agency_owner_marks_agency_sale_listing_as_sold(): void
    {
        [$user, $agency] = $this->agencyOwnerWithAgency();
        $listing = $this->agencyListing($agency, 'approved', ['listing_type' => 'sale']);

        $this->actingAs($user)
            ->post("/agency/listings/{$listing->id}/close")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.agency_show.listings.flash_closed_sold'));

        $listing->refresh();

        $this->assertSame(ListingStatus::Sold, $listing->status);
        $this->assertNotNull($listing->closed_at);
    }

    public function test_agency_owner_reopens_rented_agency_listing(): void
    {
        [$user, $agency] = $this->agencyOwnerWithAgency();
        $listing = $this->agencyListing($agency, 'rented');

        $this->actingAs($user)
            ->post("/agency/listings/{$listing->id}/reopen")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.agency_show.listings.flash_reopened'));

        $listing->refresh();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNull($listing->closed_at);
    }

    public function test_owner_reopens_sold_listing_straight_to_approved_and_it_is_public_again(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->sold()->for($owner, 'owner')->create();
        $approvedAt = $listing->approved_at->toIso8601String();

        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/reopen")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.profile.flash_reopened'));

        $listing->refresh();

        $this->assertSame(ListingStatus::Approved, $listing->status);
        $this->assertNull($listing->closed_at);
        $this->assertSame($approvedAt, $listing->approved_at->toIso8601String());

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $listing->id)
            );
    }

    public function test_only_approved_listings_can_be_closed(): void
    {
        $owner = User::factory()->regularUser()->create();

        foreach (['pendingReview', 'hidden', 'sold', 'rented'] as $state) {
            $listing = Listing::factory()->{$state}()->for($owner, 'owner')->create();
            $status = $listing->status;

            $this->actingAs($owner)
                ->post("/my-listings/{$listing->id}/close")
                ->assertForbidden();

            $this->assertSame($status, $listing->refresh()->status, "Closing a {$state} listing changed its status.");
        }
    }

    public function test_only_closed_listings_can_be_reopened(): void
    {
        $owner = User::factory()->regularUser()->create();

        foreach (['pendingReview', 'approved', 'hidden'] as $state) {
            $listing = Listing::factory()->{$state}()->for($owner, 'owner')->create();
            $status = $listing->status;

            $this->actingAs($owner)
                ->post("/my-listings/{$listing->id}/reopen")
                ->assertForbidden();

            $this->assertSame($status, $listing->refresh()->status, "Reopening a {$state} listing changed its status.");
        }
    }

    public function test_service_throws_when_closing_a_pending_listing(): void
    {
        $listing = Listing::factory()->pendingReview()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->close($listing);
    }

    public function test_service_throws_when_reopening_an_approved_listing(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->expectException(InvalidListingStatusTransitionException::class);

        (new ListingService)->reopen($listing);
    }

    public function test_another_regular_user_cannot_close_or_reopen(): void
    {
        $approved = Listing::factory()->approved()->create();
        $sold = Listing::factory()->sold()->create();
        $other = User::factory()->regularUser()->create();

        $this->actingAs($other)->post("/my-listings/{$approved->id}/close")->assertForbidden();
        $this->actingAs($other)->post("/my-listings/{$sold->id}/reopen")->assertForbidden();

        $this->assertSame(ListingStatus::Approved, $approved->refresh()->status);
        $this->assertSame(ListingStatus::Sold, $sold->refresh()->status);
    }

    public function test_admin_cannot_close_listings(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/my-listings/{$listing->id}/close")
            ->assertForbidden();

        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
    }

    public function test_agency_owner_cannot_close_another_agencys_listing(): void
    {
        [$user] = $this->agencyOwnerWithAgency();
        [, $otherAgency] = $this->agencyOwnerWithAgency();
        $listing = $this->agencyListing($otherAgency);

        $this->actingAs($user)
            ->post("/agency/listings/{$listing->id}/close")
            ->assertForbidden();

        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
    }

    public function test_suspended_owner_is_logged_out_before_reaching_the_policy(): void
    {
        $owner = User::factory()->regularUser()->suspended()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->post("/my-listings/{$listing->id}/close")
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertSame(ListingStatus::Approved, $listing->refresh()->status);
    }

    public function test_closed_listing_cannot_be_edited(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->sold()->for($owner, 'owner')->create();

        $this->actingAs($owner)->get("/my-listings/{$listing->id}/edit")->assertForbidden();

        $this->actingAs($owner)
            ->put("/my-listings/{$listing->id}", [
                'title' => 'Changed Title',
                'listing_type' => 'sale',
                'property_type' => 'apartment',
                'price' => 100000,
                'description' => 'A detailed description of the property.',
                'country' => 'Serbia',
                'city' => 'Belgrade',
            ])
            ->assertForbidden();

        $this->assertSame(ListingStatus::Sold, $listing->refresh()->status);
        $this->assertNotSame('Changed Title', $listing->title);
    }

    public function test_closed_agency_listing_cannot_be_edited(): void
    {
        [$user, $agency] = $this->agencyOwnerWithAgency();
        $listing = $this->agencyListing($agency, 'rented');

        $this->actingAs($user)->get("/agency/listings/{$listing->id}/edit")->assertForbidden();
    }

    public function test_closed_listing_can_still_be_deleted_by_its_owner(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rented()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->delete("/my-listings/{$listing->id}")
            ->assertRedirect('/profile');

        $this->assertSoftDeleted($listing);
    }

    public function test_closed_listings_are_absent_from_public_surfaces(): void
    {
        [, $agency] = $this->agencyOwnerWithAgency();
        $visible = $this->agencyListing($agency);
        $sold = $this->agencyListing($agency, 'sold');
        $rented = Listing::factory()->rented()->create();

        $this->get('/listings')
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );

        $this->get("/agencies/{$agency->slug}")
            ->assertInertia(fn (Assert $page) => $page
                ->has('listings.data', 1)
                ->where('listings.data.0.id', $visible->id)
            );

        $viewer = User::factory()->regularUser()->create();

        $this->actingAs($viewer)->get("/listings/{$sold->slug}")->assertNotFound();
        $this->actingAs($viewer)->get("/listings/{$rented->slug}")->assertNotFound();
    }

    public function test_closed_listing_cannot_be_newly_bookmarked_or_chatted_on(): void
    {
        $listing = Listing::factory()->sold()->create();
        $viewer = User::factory()->regularUser()->create();

        $this->actingAs($viewer)
            ->post("/listings/{$listing->id}/bookmark")
            ->assertNotFound();

        $this->actingAs($viewer)
            ->post("/listings/{$listing->id}/conversations", ['message' => 'Is this still available?'])
            ->assertForbidden();

        $this->assertDatabaseMissing('bookmarks', ['listing_id' => $listing->id]);
        $this->assertDatabaseMissing('conversations', ['listing_id' => $listing->id]);
    }

    public function test_existing_conversation_stays_writable_after_close_and_carries_the_status(): void
    {
        $conversation = Conversation::factory()->create();
        $conversation->listing->update(['listing_type' => 'sale']);
        (new ListingService)->close($conversation->listing);

        $buyer = $conversation->buyer;

        $this->actingAs($buyer)
            ->get("/conversations/{$conversation->id}")
            ->assertInertia(fn (Assert $page) => $page
                ->where('canReply', true)
                ->where('conversation.listing.status', 'sold')
            );

        $this->actingAs($buyer)
            ->post("/conversations/{$conversation->id}/messages", ['body' => 'Congrats on the sale!'])
            ->assertRedirect("/conversations/{$conversation->id}");

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'body' => 'Congrats on the sale!',
        ]);

        $this->actingAs($buyer)
            ->get('/conversations')
            ->assertInertia(fn (Assert $page) => $page->where('conversations.0.listing.status', 'sold'));
    }

    public function test_owner_dashboards_expose_status_and_closed_at(): void
    {
        $this->freezeTime();

        $owner = User::factory()->regularUser()->create();
        Listing::factory()->sold()->for($owner, 'owner')->create(['closed_at' => now()]);

        $this->actingAs($owner)
            ->get('/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.status', 'sold')
                ->where('listings.0.closedAt', now()->toIso8601String())
            );

        [$user, $agency] = $this->agencyOwnerWithAgency();
        $this->agencyListing($agency, 'rented', ['closed_at' => now()]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertInertia(fn (Assert $page) => $page
                ->where('listings.0.status', 'rented')
                ->where('listings.0.closedAt', now()->toIso8601String())
                ->where('translations.listings.status.rented', __('app.agency_show.listings.status.rented'))
            );
    }

    public function test_admin_review_page_renders_closed_listing_without_moderation_actions(): void
    {
        $listing = Listing::factory()->sold()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get("/admin/listings/{$listing->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.status', 'sold')
                ->where('canModerate', false)
                ->where('translations.review.statuses.sold', __('app.admin.listings.review.statuses.sold'))
            );
    }
}
