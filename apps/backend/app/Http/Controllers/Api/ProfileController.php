<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'nickname' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date',
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($request->has('name') && trim((string) $validated['name']) !== '') {
            $user->name = trim((string) $validated['name']);
        }
        if ($request->has('nickname')) {
            $user->nickname = ($validated['nickname'] ?? null) ?: null;
        }
        if ($request->has('birth_date')) {
            $user->birth_date = ($validated['birth_date'] ?? null) ?: null;
        }
        if ($request->hasFile('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('minio')->delete($user->avatar_path);
            }
            $user->avatar_path = $request->file('avatar')->store('avatars', 'minio');
        }

        $user->save();

        AuditLog::record('user.updated', $user, 'Profile updated', $user);

        return response()->json($user);
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('minio')->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();

            AuditLog::record('user.updated', $user, 'Profile picture removed', $user);
        }

        return response()->json($user);
    }
}