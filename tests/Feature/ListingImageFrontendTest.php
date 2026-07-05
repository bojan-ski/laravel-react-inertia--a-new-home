<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingImageFrontendTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_listing_card_receives_first_image_small_url(): void
    {
        $listing = Listing::factory()->approved()->create();
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/first.webp',
            'large_path' => 'listings/large/first.webp',
            'sort_order' => 0,
        ]);
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/second.webp',
            'large_path' => 'listings/large/second.webp',
            'sort_order' => 1,
        ]);

        $expected = Storage::disk('public')->url('listings/small/first.webp');

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Index')
                ->where('listings.data.0.imageUrl', $expected)
            );
    }

    public function test_listing_card_image_url_is_null_when_no_images(): void
    {
        Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Index')
                ->where('listings.data.0.imageUrl', null)
            );
    }

    public function test_listing_card_uses_small_variant_not_large(): void
    {
        $listing = Listing::factory()->approved()->create();
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/only.webp',
            'large_path' => 'listings/large/only.webp',
            'sort_order' => 0,
        ]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/listings')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $url = $page->toArray()['props']['listings']['data'][0]['imageUrl'];

                $this->assertStringContainsString('listings/small/only.webp', $url);
                $this->assertStringNotContainsString('listings/large', $url);
            });
    }

    public function test_detail_page_receives_gallery_images_with_large_urls(): void
    {
        $listing = Listing::factory()->approved()->create();
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/a.webp',
            'large_path' => 'listings/large/a.webp',
            'alt_text' => 'A cosy room',
            'sort_order' => 0,
        ]);
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/b.webp',
            'large_path' => 'listings/large/b.webp',
            'sort_order' => 1,
        ]);

        $expectedFirst = Storage::disk('public')->url('listings/large/a.webp');

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Show')
                ->has('listing.images', 2)
                ->where('listing.images.0.url', $expectedFirst)
                ->where('listing.images.0.altText', 'A cosy room')
            );
    }

    public function test_detail_page_alt_text_falls_back_to_title(): void
    {
        $listing = Listing::factory()->approved()->create(['title' => 'Seaside Villa']);
        ListingImage::factory()->for($listing)->create(['alt_text' => null, 'sort_order' => 0]);

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('listing.images.0.altText', 'Seaside Villa')
            );
    }

    public function test_detail_page_handles_empty_gallery(): void
    {
        $listing = Listing::factory()->approved()->create();

        $this->actingAs(User::factory()->regularUser()->create())
            ->get("/listings/{$listing->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Show')
                ->has('listing.images', 0)
            );
    }

    public function test_edit_page_receives_existing_image_data_with_large_urls(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();
        ListingImage::factory()->for($listing)->create([
            'small_path' => 'listings/small/edit.webp',
            'large_path' => 'listings/large/edit.webp',
            'sort_order' => 0,
        ]);

        $expected = Storage::disk('public')->url('listings/large/edit.webp');

        $this->actingAs($owner)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Edit')
                ->has('listing.images', 1)
                ->where('listing.images.0.url', $expected)
            );
    }

    public function test_edit_page_images_is_empty_when_no_images(): void
    {
        $owner = User::factory()->regularUser()->create();
        $listing = Listing::factory()->approved()->for($owner, 'owner')->create();

        $this->actingAs($owner)
            ->get("/my-listings/{$listing->id}/edit")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Listings/Edit')
                ->has('listing.images', 0)
            );
    }
}
