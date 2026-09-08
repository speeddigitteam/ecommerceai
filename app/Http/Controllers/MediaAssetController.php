<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MediaAssetController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, MediaAsset $mediaAsset): BinaryFileResponse
    {
        $disk = Storage::disk('public');
        abort_unless(str_starts_with($mediaAsset->mime_type ?? '', 'image/') && $disk->fileExists($mediaAsset->path), 404);

        return response()->file($disk->path($mediaAsset->path), [
            'Cache-Control' => 'public, max-age=0, must-revalidate',
            'Content-Type' => $mediaAsset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
