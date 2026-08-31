<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Photo;
use Aws\S3\S3Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PhotoController extends Controller
{
    public function index()
    {
        return Photo::with('uploader')
            ->latest()
            ->paginate(20)
            ->through(function (Photo $photo) {
                $photo->front_image_url = $this->signedObjectUrl($photo->front_image_path);

                if ($photo->back_image_path) {
                    $photo->back_image_url = $this->signedObjectUrl($photo->back_image_path);
                }

                return $photo;
            });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'front_image' => 'required|file|mimes:jpg,jpeg,png,webp|max:20480',
            'back_image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:20480',
            'description' => 'nullable|string',
            'taken_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'taken_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $frontPath = $request->file('front_image')->store('photos', 'minio');
        $backPath = null;

        if ($request->hasFile('back_image')) {
            $backPath = $request->file('back_image')->store('photos', 'minio');
        }

        $photo = Photo::create([
            'title' => $validated['title'],
            'front_image_path' => $frontPath,
            'back_image_path' => $backPath,
            'description' => $validated['description'] ?? null,
            'taken_year' => $validated['taken_year'] ?? null,
            'taken_date' => $validated['taken_date'] ?? null,
            'location' => $validated['location'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'uploaded_by' => $request->user()->id,
        ]);

        return response()->json($photo, 201);
    }

    public function show(Photo $photo)
    {
        $photo->load('uploader');

        $photo->front_image_url = $this->signedObjectUrl($photo->front_image_path);

        if ($photo->back_image_path) {
            $photo->back_image_url = $this->signedObjectUrl($photo->back_image_path);
        }

        return response()->json($photo);
    }

    public function update(Request $request, Photo $photo)
    {
        $this->ensureUploader($request, $photo);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'taken_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'taken_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $photo->update($validated);

        $photo->load('uploader');
        $photo->front_image_url = $this->signedObjectUrl($photo->front_image_path);
        if ($photo->back_image_path) {
            $photo->back_image_url = $this->signedObjectUrl($photo->back_image_path);
        }

        return response()->json($photo);
    }

    public function rotate(Request $request, Photo $photo)
    {
        $this->ensureUploader($request, $photo);

        $validated = $request->validate([
            'direction' => 'required|in:left,right',
            'target' => 'sometimes|in:front,back',
        ]);

        $target = $validated['target'] ?? 'front';
        $path = $target === 'back' ? $photo->back_image_path : $photo->front_image_path;

        if ($path === null) {
            return response()->json(['message' => 'No ' . $target . ' image on this photo'], 422);
        }

        $bytes = Storage::disk('minio')->get($path);

        if ($bytes === null) {
            return response()->json(['message' => 'Image not found'], 404);
        }

        $image = imagecreatefromstring($bytes);

        if ($image === false) {
            return response()->json(['message' => 'Unsupported image format'], 422);
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $degrees = $validated['direction'] === 'left' ? 90 : -90;

        $transparent = in_array($ext, ['png', 'webp'], true)
            ? imagecolorallocatealpha($image, 0, 0, 0, 127)
            : 0;

        $rotated = imagerotate($image, $degrees, $transparent);

        if (in_array($ext, ['png', 'webp'], true)) {
            imagesavealpha($rotated, true);
        }

        ob_start();
        match ($ext) {
            'png' => imagepng($rotated),
            'webp' => imagewebp($rotated),
            'gif' => imagegif($rotated),
            default => imagejpeg($rotated, null, 90),
        };
        $output = ob_get_clean();

        imagedestroy($image);
        imagedestroy($rotated);

        Storage::disk('minio')->put($path, $output);

        $photo->load('uploader');
        $photo->front_image_url = $this->signedObjectUrl($photo->front_image_path);
        if ($photo->back_image_path) {
            $photo->back_image_url = $this->signedObjectUrl($photo->back_image_path);
        }

        return response()->json($photo);
    }

    public function destroy(Request $request, Photo $photo)
    {
        $this->ensureUploader($request, $photo);

        Storage::disk('minio')->delete($photo->front_image_path);

        if ($photo->back_image_path) {
            Storage::disk('minio')->delete($photo->back_image_path);
        }

        $photo->delete();

        return response()->json(null, 204);
    }

    private function ensureUploader(Request $request, Photo $photo)
    {
        if ($request->user()->id !== $photo->uploaded_by) {
            abort(403, 'Only the uploader can modify this photo');
        }
    }

    private function signedObjectUrl(string $path, int $minutes = 5): string
    {
        $disk = config('filesystems.disks.minio');

        $client = new S3Client([
            'version' => 'latest',
            'region' => $disk['region'],
            'endpoint' => env('MINIO_PUBLIC_URL', 'http://localhost:9000'),
            'use_path_style_endpoint' => true,
            'credentials' => [
                'key' => $disk['key'],
                'secret' => $disk['secret'],
            ],
        ]);

        $command = $client->getCommand('GetObject', [
            'Bucket' => $disk['bucket'],
            'Key' => $path,
        ]);

        return (string) $client->createPresignedRequest($command, now()->addMinutes($minutes))->getUri();
    }
}
