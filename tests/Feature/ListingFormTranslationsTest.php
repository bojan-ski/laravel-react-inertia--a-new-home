<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ListingFormTranslationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string, string, string, list<string>}>
     */
    public static function formPages(): array
    {
        $shared = ['sections_label', 'sections.images'];

        return [
            'user create' => ['user', 'create', 'app.create_listing', [...$shared, 'new_images.cover']],
            'user edit' => ['user', 'edit', 'app.edit_listing', [
                ...$shared, 'bar_note_approved', 'images.cover', 'images.no_alt', 'images.image_label',
            ]],
            'agency create' => ['agency', 'create', 'app.create_agency_listing', [...$shared, 'new_images.cover']],
            'agency edit' => ['agency', 'edit', 'app.edit_agency_listing', [
                ...$shared, 'bar_note_approved', 'images.cover', 'images.no_alt', 'images.image_label',
            ]],
        ];
    }

    /**
     * @param  list<string>  $newKeys
     */
    #[DataProvider('formPages')]
    public function test_form_page_receives_its_full_translations_block(string $owner, string $page, string $key, array $newKeys): void
    {
        [$user, $url, $component] = $this->formPage($owner, $page);

        $this->actingAs($user)
            ->get($url)
            ->assertOk()
            ->assertInertia(function (Assert $inertia) use ($component, $key, $newKeys) {
                $inertia->component($component)->where('translations', __($key));

                foreach ($newKeys as $newKey) {
                    $inertia->has("translations.{$newKey}");
                }
            });
    }

    public function test_edit_pages_share_the_neutral_submit_note(): void
    {
        $this->assertSame(__('app.edit_listing.submit_note'), __('app.edit_agency_listing.submit_note'));
        $this->assertSame(
            'Saving sends this approved listing back for review.',
            __('app.edit_agency_listing.bar_note_approved'),
        );
    }

    /**
     * @return array{User, string, string}
     */
    private function formPage(string $owner, string $page): array
    {
        if ($owner === 'user') {
            $user = User::factory()->regularUser()->create();

            if ($page === 'create') {
                return [$user, '/my-listings/create', 'Listings/Create'];
            }

            $listing = Listing::factory()->pendingReview()->for($user, 'owner')->create();

            return [$user, "/my-listings/{$listing->id}/edit", 'Listings/Edit'];
        }

        $user = User::factory()->agency()->create();
        $agency = Agency::factory()->approved()->create(['user_id' => $user->id]);

        if ($page === 'create') {
            return [$user, '/agency/listings/create', 'Agency/Listings/Create'];
        }

        $listing = Listing::factory()->pendingReview()->create(['user_id' => null, 'agency_id' => $agency->id]);

        return [$user, "/agency/listings/{$listing->id}/edit", 'Agency/Listings/Edit'];
    }
}
