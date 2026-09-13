<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'string', 'max:255'],
        ]);

        if (filled($validated['website'] ?? null)) {
            return back()->with('success', 'Thank you. Your message has been sent.');
        }

        unset($validated['website']);

        $message = ContactMessage::query()->create([
            ...$validated,
            'ip_address' => $request->ip(),
        ]);

        try {
            $recipient = 'm.office@sunskyglobalsecurity.com' ?: (string) config('mail.from.address');

            $body = implode("\n", [
                'New contact form submission',
                '',
                'Name: '.$message->name,
                'Email: '.$message->email,
                'Phone: '.($message->phone ?: '—'),
                'Subject: '.($message->subject ?: '—'),
                '',
                $message->message,
            ]);

            Mail::raw($body, function ($mail) use ($recipient, $message): void {
                $mail->to($recipient)
                    ->subject('Website contact: '.($message->subject ?: $message->name))
                    ->replyTo($message->email, $message->name);
            });
        } catch (\Throwable $e) {
            Log::warning('Contact form mail failed.', [
                'contact_message_id' => $message->id,
                'error' => $e->getMessage(),
            ]);
        }

        $this->notifyMisCustom(
            'settings',
            __('New website message'),
            __('New message from :name', ['name' => $message->name]),
            null,
            'warning',
        );

        return back()->with('success', 'Thank you. Your message has been sent.');
    }
}
