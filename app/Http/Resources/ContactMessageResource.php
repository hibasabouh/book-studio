<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ContactMessage
 */
class ContactMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'category' => $this->category->value,
            'message' => $this->message,
            'attachments' => ContactMessageAttachmentResource::collection($this->attachments),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
