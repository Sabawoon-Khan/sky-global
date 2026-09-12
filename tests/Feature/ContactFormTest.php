<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_requires_name_email_and_message(): void
    {
        $response = $this->from(route('website.contact'))
            ->post(route('website.contact.store'), []);

        $response->assertRedirect(route('website.contact'));
        $response->assertSessionHasErrors(['name', 'email', 'message']);
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_form_persists_a_valid_message(): void
    {
        Mail::fake();

        $response = $this->from(route('website.contact'))
            ->post(route('website.contact.store'), [
                'name' => 'Ahmad Khan',
                'email' => 'ahmad@example.com',
                'phone' => '+93700111222',
                'subject' => 'Security inquiry',
                'message' => 'We need static guarding for our Kabul compound.',
                'website' => '',
            ]);

        $response->assertRedirect(route('website.contact'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('contact_messages', [
            'name' => 'Ahmad Khan',
            'email' => 'ahmad@example.com',
            'phone' => '+93700111222',
            'subject' => 'Security inquiry',
            'message' => 'We need static guarding for our Kabul compound.',
        ]);

        $this->assertNotNull(ContactMessage::query()->first()?->ip_address);
    }

    public function test_honeypot_is_rejected_silently_without_persisting(): void
    {
        Mail::fake();

        $response = $this->from(route('website.contact'))
            ->post(route('website.contact.store'), [
                'name' => 'Bot',
                'email' => 'bot@example.com',
                'message' => 'Spam payload',
                'website' => 'https://spam.example',
            ]);

        $response->assertRedirect(route('website.contact'));
        $response->assertSessionHas('success');
        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingSent();
    }
}
