<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\ListingImage;
use App\Models\User;
use App\Services\ListingImageService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ListingImageServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function service(): ListingImageService
    {
        return app(ListingImageService::class);
    }

    private function listing(): Listing
    {
        $owner = User::factory()->regularUser()->create();

        return Listing::factory()->approved()->for($owner, 'owner')->create();
    }

    private function dimensions(string $path): array
    {
        $image = ImageManager::usingDriver(new GdDriver)->decode(Storage::disk('public')->get($path));

        return [$image->width(), $image->height()];
    }

    public function test_small_variant_is_cover_cropped_to_640x480(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 1200, 900));

        $this->assertSame([640, 480], $this->dimensions($image->small_path));
    }

    public function test_large_variant_is_not_upscaled_when_smaller_than_bounds(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));

        $this->assertSame([800, 600], $this->dimensions($image->large_path));
    }

    public function test_large_variant_is_scaled_down_within_bounds_preserving_aspect(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 3200, 2400));

        $this->assertSame([1600, 1200], $this->dimensions($image->large_path));
    }

    public function test_both_variants_are_valid_webp(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 1200, 900));

        foreach ([$image->small_path, $image->large_path] as $path) {
            $binary = Storage::disk('public')->get($path);
            $this->assertSame('RIFF', substr($binary, 0, 4));
            $this->assertSame('WEBP', substr($binary, 8, 4));
        }
    }

    public function test_paths_follow_the_expected_convention(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));

        $this->assertMatchesRegularExpression("#^listings/{$listing->id}/small/[A-Za-z0-9]{16}\.webp$#", $image->small_path);
        $this->assertMatchesRegularExpression("#^listings/{$listing->id}/large/[A-Za-z0-9]{16}\.webp$#", $image->large_path);
        $this->assertSame('public', $image->disk);
    }

    public function test_record_is_created_with_alt_text_and_disk(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600), 'Sunny living room');

        $this->assertDatabaseHas('listing_images', [
            'id' => $image->id,
            'listing_id' => $listing->id,
            'disk' => 'public',
            'small_path' => $image->small_path,
            'large_path' => $image->large_path,
            'alt_text' => 'Sunny living room',
            'sort_order' => 1,
        ]);
    }

    public function test_sort_order_increments_across_multiple_uploads(): void
    {
        Storage::fake('public');
        $listing = $this->listing();

        $first = $this->service()->upload($listing, UploadedFile::fake()->image('a.jpg', 800, 600));
        $second = $this->service()->upload($listing, UploadedFile::fake()->image('b.jpg', 800, 600));
        $third = $this->service()->upload($listing, UploadedFile::fake()->image('c.jpg', 800, 600));

        $this->assertSame(1, $first->sort_order);
        $this->assertSame(2, $second->sort_order);
        $this->assertSame(3, $third->sort_order);
    }

    public function test_delete_removes_both_files_and_the_record(): void
    {
        Storage::fake('public');
        $listing = $this->listing();
        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));

        Storage::disk('public')->assertExists($image->small_path);
        Storage::disk('public')->assertExists($image->large_path);

        $this->service()->delete($image);

        Storage::disk('public')->assertMissing($image->small_path);
        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_delete_succeeds_when_one_file_is_already_missing(): void
    {
        Storage::fake('public');
        $listing = $this->listing();
        $image = $this->service()->upload($listing, UploadedFile::fake()->image('photo.jpg', 800, 600));

        Storage::disk('public')->delete($image->small_path);

        $this->service()->delete($image);

        Storage::disk('public')->assertMissing($image->large_path);
        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }

    public function test_delete_succeeds_when_both_files_are_already_missing(): void
    {
        Storage::fake('public');
        $listing = $this->listing();
        $image = ListingImage::factory()->for($listing)->create();

        $this->service()->delete($image);

        $this->assertDatabaseMissing('listing_images', ['id' => $image->id]);
    }
}
