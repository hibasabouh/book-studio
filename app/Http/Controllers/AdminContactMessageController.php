<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactMessageRequest;
use App\Http\Requests\UpdateContactMessageRequest;
use App\Http\Resources\ContactMessageResource;
use App\Models\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

class AdminContactMessageController extends Controller
{
    /**
     * Render the contact messages shell (bug reports / feature suggestions
     * submitted through the public contact form).
     */
    public function index(IndexContactMessageRequest $request): JsonResponse|View
    {
        if (! $request->expectsJson()) {
            return view('admin.messages');
        }

        $validated = $request->validated();
        $perPage = (int) ($validated['per_page'] ?? 15);

        $messages = ContactMessage::query()
            ->with('attachments')
            ->when($validated['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->when(array_key_exists('unread', $validated), fn ($q) => $q->when(
                $validated['unread'], fn ($q) => $q->whereNull('read_at'), fn ($q) => $q->whereNotNull('read_at')
            ))
            ->latest()
            ->paginate($perPage);

        return ContactMessageResource::collection($messages)->response();
    }

    /**
     * Render the message details shell. The message is hydrated client-side from
     * the JSON endpoint below.
     */
    public function show(ContactMessage $message): View
    {
        return view('admin.message-details', ['messageId' => $message->id]);
    }

    /**
     * Single contact message payload (with its attachments) for the details page.
     */
    public function details(ContactMessage $message): JsonResponse
    {
        return (new ContactMessageResource($message->load('attachments')))->response();
    }

    /**
     * Mark a contact message read or unread.
     */
    public function update(UpdateContactMessageRequest $request, ContactMessage $message): JsonResponse
    {
        $message->read_at = $request->validated('read') ? now() : null;
        $message->save();

        return (new ContactMessageResource($message->load('attachments')))->response();
    }

    /**
     * Delete a contact message along with its attachments (files and rows).
     */
    public function destroy(ContactMessage $message): JsonResponse
    {
        foreach ($message->attachments as $attachment) {
            delete_file_if_exist($attachment->path);
            $attachment->delete();
        }

        $message->delete();

        return response()->json(['message' => __('messages.contact_message_deleted')]);
    }
}
