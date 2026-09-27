<?php

namespace App\Services;

use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ListingImageService
{
    private const DISK = 'public';

    private const SMALL_WIDTH = 640;

    private const SMALL_HEIGHT = 480;

    private const LARGE_WIDTH = 1600;

    private const LARGE_HEIGHT = 1200;

    private const WEBP_QUALITY = 80;

    /**
     * Process an uploaded image into small and large WebP variants, store them,
     * and record the resulting ListingImage.
     */
    /**
     * @param  array<int, UploadedFile>  $files
     */
    public function uploadMany(Listing $listing, array $files): void
    {
        foreach ($files as $file) {
            $this->upload($listing, $file);
        }
    }

    public function upload(Listing $listing, UploadedFile $file, ?string $altText = null): ListingImage
    {
        $token = Str::random(16);
        $manager = ImageManager::usingDriver(new GdDriver);

        $smallPath = "listings/{$listing->id}/small/{$token}.webp";
        $largePath = "listings/{$listing->id}/large/{$token}.webp";

        $small = $manager->decode($file->getRealPath())
            ->cover(self::SMALL_WIDTH, self::SMALL_HEIGHT)
            ->encode(new WebpEncoder(quality: self::WEBP_QUALITY));

        $large = $manager->decode($file->getRealPath())
            ->scaleDown(self::LARGE_WIDTH, self::LARGE_HEIGHT)
            ->encode(new WebpEncoder(quality: self::WEBP_QUALITY));

        $disk = Storage::disk(self::DISK);
        $disk->put($smallPath, (string) $small);
        $disk->put($largePath, (string) $large);

        $sortOrder = ($listing->images()->max('sort_order') ?? 0) + 1;

        return $listing->images()->create([
            'disk' => self::DISK,
            'small_path' => $smallPath,
            'large_path' => $largePath,
            'alt_text' => $altText,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * Remove both stored variants and the database record. File deletion is
     * defensive: a missing file does not prevent the record from being removed.
     */
    public function delete(ListingImage $image): void
    {
        $disk = Storage::disk($image->disk);

        foreach ([$image->small_path, $image->large_path] as $path) {
            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }

        $image->delete();
    }
}
