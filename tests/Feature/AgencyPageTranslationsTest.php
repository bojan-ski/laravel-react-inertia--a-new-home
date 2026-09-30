<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AgencyPageTranslationsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PLACEHOLDERS = [
        'fields.name_placeholder',
        'fields.description_placeholder',
        'fields.email_placeholder',
        'fields.website_placeholder',
    ];

    public function test_dashboard_receives_the_new_keys_and_no_property_type_labels(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Agency/Show')
                ->where('translations', __('app.agency_show'))
                ->where('translations.status.rejected.reason', 'Rejection reason: :reason')
                ->where('translations.listings.edit', 'Edit')
                ->where('translations.listings.rejection_reason_label', 'Rejection reason:')
                ->missing('propertyTypeLabels')
            );
    }

    public function test_setup_receives_cancel_and_the_placeholders(): void
    {
        $user = User::factory()->agency()->create();

        $this->actingAs($user)
            ->get('/agency/setup')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('Agency/Setup')
                    ->where('translations', __('app.agency_setup'))
                    ->where('translations.cancel', 'Cancel')
                    ->where('translations.fields.website_placeholder', 'https://');

                foreach (self::PLACEHOLDERS as $key) {
                    $page->has("translations.{$key}");
                }
            });
    }

    public function test_edit_receives_the_placeholders_and_both_bar_notes(): void
    {
        $user = User::factory()->agency()->create();
        Agency::factory()->approved()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get('/agency/edit')
            ->assertOk()
            ->assertInertia(function (Assert $page) {
                $page->component('Agency/Edit')
                    ->where('translations', __('app.agency_edit'))
                    ->where('translations.bar_note_approved', 'Changes are reviewed before they go live.')
                    ->where('translations.bar_note_pending', 'Changes apply to your submission right away.');

                foreach (self::PLACEHOLDERS as $key) {
                    $page->has("translations.{$key}");
                }
            });
    }

    public function test_setup_and_edit_form_fields_share_one_key_set(): void
    {
        $setupKeys = array_keys(__('app.agency_setup.fields'));
        $editKeys = array_keys(__('app.agency_edit.fields'));

        sort($setupKeys);
        sort($editKeys);

        $this->assertSame($setupKeys, $editKeys);
        $this->assertContains('website_placeholder', $editKeys);
    }
}
