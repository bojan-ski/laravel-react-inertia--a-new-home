<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingImageBackendTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function ownedListing(User $owner): Listing
    {
        return Listing::factory()->approved()->for($owner, 'owner')->create();
    }

    public function test_regular_user_can_upload_to_own_editable_listing(): void
    {
        Storage::fake('public');
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)
            ->post("/my-listings/{$listing->id}/images", [
                'image' => UploadedFile::fake()->image('photo.jpg', 1200, 900),
                'alt_text' => 'Front view',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', __('app.listing_images.uploaded'));

        $image = $listing->images()->first();
        $this->assertNotNull($image);
        $this->assertSame('Front view', $image->alt_text);
        Storage::disk('public')->assertExists($image->small_path);
        Storage::disk('public')->assertExists($image->large_path);
    }

    public function test_upload_creates_listing_image_record(): void
    {
        Storage::fake('public');
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);

        $this->assertDatabaseHas('listing_images', [
            'listing_id' => $listing->id,
            'disk' => 'public',
            'sort_order' => 1,
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $listing = $this->ownedListing(User::factory()->regularUser()->create());

        $this->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertRedirect('/login');
    }

    public function test_regular_user_cannot_upload_to_another_users_listing(): void
    {
        Storage::fake('public');
        $listing = $this->ownedListing(User::factory()->regularUser()->create());
        $intruder = User::factory()->regularUser()->create();

        $this->actingAs($intruder)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertForbidden();

        $this->assertDatabaseCount('listing_images', 0);
    }

    public function test_agency_user_cannot_use_upload_route(): void
    {
        $listing = $this->ownedListing(User::factory()->regularUser()->create());
        $agency = User::factory()->agency()->create();

        $this->actingAs($agency)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertForbidden();
    }

    public function test_admin_cannot_use_upload_route(): void
    {
        $listing = $this->ownedListing(User::factory()->regularUser()->create());
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertForbidden();
    }

    public function test_cannot_upload_to_rejected_listing(): void
    {
        Storage::fake('public');
        $user = User::factory()->regularUser()->create();
        $listing = Listing::factory()->rejected()->for($user, 'owner')->create();

        $this->actingAs($user)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertForbidden();

        $this->assertDatabaseCount('listing_images', 0);
    }

    public function test_suspended_user_cannot_upload(): void
    {
        $suspended = User::factory()->regularUser()->suspended()->create();
        $listing = Listing::factory()->approved()->for($suspended, 'owner')->create();

        $this->actingAs($suspended)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ])->assertRedirect('/login');

        $this->assertGuest();
    }

    public function test_missing_image_fails_validation(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)
            ->post("/my-listings/{$listing->id}/images", [])
            ->assertSessionHasErrors('image');
    }

    public function test_non_image_upload_fails_validation(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)
            ->post("/my-listings/{$listing->id}/images", [
                'image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_oversized_image_fails_validation(): void
    {
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)
            ->post("/my-listings/{$listing->id}/images", [
                'image' => UploadedFile::fake()->image('huge.jpg', 800, 600)->size(11000),
            ])
            ->assertSessionHasErrors('image');
    }

    public function test_owner_can_delete_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->regularUser()->create();
        $listing = $this->ownedListing($user);

        $this->actingAs($user)->post("/my-listings/{$listing->id}/images", [
            'image' => UploadedFile::fake()->image('photo.jpg', 800, 600),
        ]);

        $image = $listing->images()->firstOrFail();

        $this->actingAs($user)
            ->delete("/my-listings/{$listing->id}/images/{$image->id}")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.listing_images.deleted'));

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
    }

    public function test_cross_listing_tampering_returns_404_and_does_not_delete(): void
    {
        Storage::fake('public');
        $user = User::factory()->regularUser()->create();
        $listingA = $this->ownedListing($user);
        $listingB = $this->ownedListing($user);
        $imageB = ListingImage::factory()->for($listingB)->create();

        $this->actingAs($user)
            ->delete("/my-listings/{$listingA->id}/images/{$imageB->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('listing_images', ['id' => $imageB->id]);
    }

    public function test_images_cascade_delete_when_listing_is_hard_deleted(): void
    {
        $listing = $this->ownedListing(User::factory()->regularUser()->create());
        $image = ListingImage::factory()->for($listing)->create();

        $listing->forceDelete();

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }
}
