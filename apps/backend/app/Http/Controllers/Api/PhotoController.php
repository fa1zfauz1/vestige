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
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'taken_year' => 'nullable|integer|min:1800|max:' . date('Y'),
            'taken_date' => 'nullable|date',
            'location' => 'nullable|string|max:255',
        ]);

        $photo->update($validated);

        return response()->json($photo);
    }

    public function destroy(Photo $photo)
    {
        Storage::disk('minio')->delete($photo->front_image_path);

        if ($photo->back_image_path) {
            Storage::disk('minio')->delete($photo->back_image_path);
        }

        $photo->delete();

        return response()->json(null, 204);
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
