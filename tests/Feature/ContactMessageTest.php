<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ContactMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'visitor@example.test',
            'subject' => 'Question about a listing',
            'message' => 'Hello, I would like to know more.',
        ], $overrides);
    }

    public function test_guest_can_view_contact_page_with_empty_email(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Contact/Show')
                ->where('defaultEmail', '')
                ->where('translations.title', __('app.contact.title')));
    }

    public function test_signed_in_user_gets_email_prefilled(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)
            ->get('/contact')
            ->assertInertia(fn (Assert $page) => $page->where('defaultEmail', $user->email));
    }

    public function test_contact_page_carries_field_placeholders(): void
    {
        $this->get('/contact')
            ->assertInertia(fn (Assert $page) => $page
                ->where('translations.placeholders.email', __('app.contact.placeholders.email'))
                ->where('translations.placeholders.subject', __('app.contact.placeholders.subject'))
                ->where('translations.placeholders.message', __('app.contact.placeholders.message')));
    }

    public function test_guest_can_submit_a_message(): void
    {
        $this->post('/contact', $this->validPayload())
            ->assertRedirect('/contact')
            ->assertSessionHas('success', __('app.contact.flash'));

        $this->assertDatabaseHas('contact_messages', [
            'email' => 'visitor@example.test',
            'subject' => 'Question about a listing',
            'user_id' => null,
            'handled_at' => null,
        ]);
    }

    public function test_signed_in_submission_is_linked_to_the_user(): void
    {
        $user = User::factory()->regularUser()->create();

        $this->actingAs($user)->post('/contact', $this->validPayload());

        $this->assertDatabaseHas('contact_messages', ['user_id' => $user->id]);
    }

    public function test_server_controlled_fields_cannot_be_submitted(): void
    {
        $other = User::factory()->regularUser()->create();

        $this->post('/contact', $this->validPayload([
            'user_id' => $other->id,
            'handled_at' => now()->toDateTimeString(),
            'handled_by_user_id' => $other->id,
        ]));

        $message = ContactMessage::sole();
        $this->assertNull($message->user_id);
        $this->assertNull($message->handled_at);
        $this->assertNull($message->handled_by_user_id);
    }

    public function test_all_fields_are_required(): void
    {
        $this->post('/contact', [])
            ->assertSessionHasErrors(['email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_email_must_be_valid_and_lengths_are_capped(): void
    {
        $this->post('/contact', $this->validPayload([
            'email' => 'not-an-email',
            'subject' => str_repeat('a', 151),
            'message' => str_repeat('a', 5001),
        ]))->assertSessionHasErrors(['email', 'subject', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_submissions_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->post('/contact', $this->validPayload())->assertRedirect('/contact');
        }

        $this->post('/contact', $this->validPayload())->assertTooManyRequests();

        $this->assertDatabaseCount('contact_messages', 3);
    }

    public function test_admin_sees_new_messages_by_default_newest_first(): void
    {
        $older = ContactMessage::factory()->create(['created_at' => now()->subDay()]);
        $newer = ContactMessage::factory()->create();
        ContactMessage::factory()->handled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/contact-messages')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/ContactMessages/Index')
                ->where('status', 'new')
                ->has('messages.data', 2)
                ->where('messages.data.0.id', $newer->id)
                ->where('messages.data.1.id', $older->id));
    }

    public function test_admin_can_filter_handled_messages(): void
    {
        ContactMessage::factory()->create();
        $handled = ContactMessage::factory()->handled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/contact-messages?status=handled')
            ->assertInertia(fn (Assert $page) => $page
                ->where('status', 'handled')
                ->has('messages.data', 1)
                ->where('messages.data.0.id', $handled->id)
                ->where('messages.data.0.isHandled', true));
    }

    public function test_invalid_status_filter_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/contact-messages?status=bogus')
            ->assertSessionHasErrors('status');
    }

    public function test_admin_payload_includes_sender_account_name(): void
    {
        $user = User::factory()->regularUser()->create(['name' => 'Jane Buyer']);
        ContactMessage::factory()->create(['user_id' => $user->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/contact-messages')
            ->assertInertia(fn (Assert $page) => $page->where('messages.data.0.senderName', 'Jane Buyer'));
    }

    public function test_admin_can_mark_a_message_handled(): void
    {
        $admin = User::factory()->admin()->create();
        $message = ContactMessage::factory()->create();

        $this->actingAs($admin)
            ->post("/admin/contact-messages/{$message->id}/handled")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.contact_messages.flash.handled'));

        $message->refresh();
        $this->assertNotNull($message->handled_at);
        $this->assertSame($admin->id, $message->handled_by_user_id);
    }

    public function test_already_handled_message_cannot_be_marked_again(): void
    {
        $message = ContactMessage::factory()->handled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post("/admin/contact-messages/{$message->id}/handled")
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_message(): void
    {
        $message = ContactMessage::factory()->handled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete("/admin/contact-messages/{$message->id}")
            ->assertRedirect()
            ->assertSessionHas('success', __('app.admin.contact_messages.flash.deleted'));

        $this->assertModelMissing($message);
    }

    public function test_guest_is_redirected_from_admin_pages(): void
    {
        $message = ContactMessage::factory()->create();

        $this->get('/admin/contact-messages')->assertRedirect('/login');
        $this->post("/admin/contact-messages/{$message->id}/handled")->assertRedirect('/login');
        $this->delete("/admin/contact-messages/{$message->id}")->assertRedirect('/login');
    }

    public function test_non_admins_are_forbidden_from_admin_pages(): void
    {
        $message = ContactMessage::factory()->create();

        foreach ([User::factory()->regularUser()->create(), User::factory()->agency()->create()] as $user) {
            $this->actingAs($user)->get('/admin/contact-messages')->assertForbidden();
            $this->actingAs($user)->post("/admin/contact-messages/{$message->id}/handled")->assertForbidden();
            $this->actingAs($user)->delete("/admin/contact-messages/{$message->id}")->assertForbidden();
        }

        $this->assertModelExists($message);
        $this->assertNull($message->fresh()->handled_at);
    }

    public function test_deleting_the_sender_account_keeps_the_message(): void
    {
        $user = User::factory()->regularUser()->create();
        $message = ContactMessage::factory()->create(['user_id' => $user->id]);

        $user->delete();

        $this->assertNull($message->fresh()->user_id);
    }

    public function test_new_tab_count_matches_the_shared_admin_count(): void
    {
        $admin = User::factory()->admin()->create();
        ContactMessage::factory()->count(3)->create();
        ContactMessage::factory()->handled()->create();

        $this->actingAs($admin)
            ->get('/admin/contact-messages')
            ->assertInertia(fn (Assert $page) => $page
                ->where('messages.total', 3)
                ->where('adminCounts.newContactMessages', 3)
                ->where('translations.tabs.new_count', __('app.admin.contact_messages.tabs.new_count'))
                ->where('translations.tabs.aria_label', __('app.admin.contact_messages.tabs.aria_label'))
            );

        $this->actingAs($admin)
            ->get('/admin/contact-messages?status=handled')
            ->assertInertia(fn (Assert $page) => $page
                ->where('messages.total', 1)
                ->where('adminCounts.newContactMessages', 3)
            );
    }
}
