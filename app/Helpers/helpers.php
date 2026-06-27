<?php

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

if (! function_exists('file_url')) {
    /**
     * Build a public URL for a stored file path, passing through absolute URLs
     * (e.g. a Google avatar) unchanged.
     */
    function file_url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Already an absolute URL (external).
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}

if (! function_exists('upload_file')) {
    /**
     * Store an uploaded file under a unique name and return its relative path.
     */
    function upload_file($request_file, string $prefix, string $folder_name, string $disk = 'public'): string
    {
        $extension = $request_file->getClientOriginalExtension();
        $file_name = $prefix.'_'.(string) Str::uuid().'.'.$extension;

        return $request_file->storeAs($folder_name, $file_name, $disk);
    }
}

if (! function_exists('delete_file_if_exist')) {
    /**
     * Delete a stored file if it exists. Returns true when a file was deleted.
     */
    function delete_file_if_exist(?string $file, string $disk = 'public'): bool
    {
        return (! empty($file) && Storage::disk($disk)->exists($file))
            ? Storage::disk($disk)->delete($file)
            : false;
    }
}
