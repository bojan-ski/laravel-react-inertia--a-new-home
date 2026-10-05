<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListingImagesOnCreateTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Sunlit Loft',
            'listing_type' => 'sale',
            'property_type' => 'apartment',
            'price' => 250000,
            'currency' => 'EUR',
            'description' => 'A bright loft in the city centre.',
            'country' => 'Austria',
            'city' => 'Vienna',
        ], $overrides);
    }

    /**
     * @return array{User, Agency}
     */
    private function approvedAgencyOwner(): array
    {
        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        return [$user, $agency];
    }

    public function test_regular_user_can_attach_images_when_creating_a_listing(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload([
                'images' => [
                    UploadedFile::fake()->image('front.jpg', 1200, 900),
                    UploadedFile::fake()->image('kitchen.png', 1200, 900),
                ],
            ]))
            ->assertRedirect('/profile')
            ->assertSessionHasNoErrors();

        $listing = Listing::where('user_id', $user->id)->sole();
        $images = $listing->images()->orderBy('sort_order')->get();

        $this->assertCount(2, $images);
        $this->assertSame([1, 2], $images->pluck('sort_order')->all());
        $images->each(function (ListingImage $image) {
            Storage::disk('public')->assertExists([$image->small_path, $image->large_path]);
        });
    }

    public function test_agency_can_attach_images_when_creating_a_listing(): void
    {
        [$user, $agency] = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->post('/agency/listings', $this->validPayload([
                'images' => [UploadedFile::fake()->image('front.jpg', 1200, 900)],
            ]))
            ->assertRedirect('/agency')
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Listing::where('agency_id', $agency->id)->sole()->images()->count());
    }

    public function test_images_are_optional(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->post('/my-listings', $this->validPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame(0, ListingImage::count());
        $this->assertSame(1, Listing::count());
    }

    public function test_more_than_ten_images_are_rejected(): void
    {
        $images = array_map(fn (int $index) => UploadedFile::fake()->image("photo-{$index}.jpg", 200, 150), range(1, 11));

        $this->actingAs(User::factory()->regularUser()->create())
            ->post('/my-listings', $this->validPayload(['images' => $images]))
            ->assertSessionHasErrors('images');

        $this->assertSame(0, Listing::count());
    }

    public function test_images_over_the_pixel_cap_are_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->post('/my-listings', $this->validPayload([
                'images' => [UploadedFile::fake()->image('panorama.jpg', 6001, 10)],
            ]))
            ->assertSessionHasErrors(['images.0' => __('app.listing_images.validation.dimensions', ['max_width' => 6000, 'max_height' => 6000])]);

        [$agencyUser] = $this->approvedAgencyOwner();

        $this->actingAs($agencyUser)
            ->post('/agency/listings', $this->validPayload([
                'images' => [UploadedFile::fake()->image('tower.jpg', 10, 6001)],
            ]))
            ->assertSessionHasErrors(['images.0' => __('app.listing_images.validation.dimensions', ['max_width' => 6000, 'max_height' => 6000])]);

        $this->assertSame(0, Listing::count());
    }

    public function test_non_image_files_are_rejected(): void
    {
        [$user] = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->post('/agency/listings', $this->validPayload([
                'images' => [UploadedFile::fake()->create('brochure.pdf', 100, 'application/pdf')],
            ]))
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, Listing::count());
    }

    public function test_create_pages_receive_the_image_picker_copy(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/my-listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.new_images', __('app.create_listing.new_images')));

        [$user] = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->get('/agency/listings/create')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.new_images', __('app.create_agency_listing.new_images')));
    }

    public function test_create_pages_receive_the_max_images_limit(): void
    {
        $this->actingAs(User::factory()->regularUser()->create())
            ->get('/my-listings/create')
            ->assertInertia(fn (Assert $page) => $page->where('maxImages', 10));

        [$user] = $this->approvedAgencyOwner();

        $this->actingAs($user)
            ->get('/agency/listings/create')
            ->assertInertia(fn (Assert $page) => $page->where('maxImages', 10));
    }

    public function test_agency_can_upload_and_delete_images_on_its_listing(): void
    {
        [$user, $agency] = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($user)
            ->post("/agency/listings/{$listing->id}/images", ['image' => UploadedFile::fake()->image('front.jpg', 800, 600)])
            ->assertRedirect()
            ->assertSessionHas('success', __('app.listing_images.uploaded'));

        $image = $listing->images()->sole();

        $this->actingAs($user)
            ->delete("/agency/listings/{$listing->id}/images/{$image->id}")
            ->assertSessionHas('success', __('app.listing_images.deleted'));

        $this->assertModelMissing($image);
    }

    public function test_agency_edit_page_receives_image_manager_data(): void
    {
        [$user, $agency] = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($user)
            ->get("/agency/listings/{$listing->id}/edit")
            ->assertInertia(fn (Assert $page) => $page
                ->has('listing.images')
                ->where('translations.images', __('app.edit_agency_listing.images')));
    }

    public function test_other_agency_cannot_upload_to_a_foreign_listing(): void
    {
        [, $agency] = $this->approvedAgencyOwner();
        [$otherUser] = $this->approvedAgencyOwner();
        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        $this->actingAs($otherUser)
            ->post("/agency/listings/{$listing->id}/images", ['image' => UploadedFile::fake()->image('front.jpg', 800, 600)])
            ->assertForbidden();

        $this->assertSame(0, ListingImage::count());
    }
}
