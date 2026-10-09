<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Mail\ContactMessageMail;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ContactController extends Controller
{
    /**
     * Render the contact page shell.
     */
    public function show(): View
    {
        return view('contact');
    }

    /**
     * Store a contact message and email it to the site owner. The record is
     * persisted before the email is attempted, so a submission is never lost
     * if mail delivery fails — it's still visible in the admin inbox.
     */
    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $contactMessage = ContactMessage::create($request->validated());

        foreach ($request->file('attachments', []) as $file) {
            $contactMessage->attachments()->create([
                'path' => upload_file($file, 'contact', 'contact-attachments'),
                // Client-supplied values; clamp to the string(255) columns.
                'original_name' => Str::substr($file->getClientOriginalName(), 0, 255),
                'mime_type' => Str::substr($file->getClientMimeType(), 0, 255),
                'size' => $file->getSize(),
            ]);
        }

        try {
            Mail::to(config('contact.recipient'))->send(new ContactMessageMail($contactMessage->load('attachments')));
        } catch (Throwable $e) {
            Log::error('Failed to send contact message email.', ['exception' => $e]);
        }

        return response()->json(['message' => __('messages.contact_sent')], 201);
    }
}
