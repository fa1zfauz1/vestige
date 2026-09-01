<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function pendingUsers()
    {
        return User::where('status', 'pending')->get();
    }

    public function approve(User $user)
    {
        $user->update([
            'status' => 'approved',
            'rejection_reason' => null,
            'appeal_reason' => null,
        ]);

        return response()->json($user);
    }

    public function reject(Request $request, User $user)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['reason'],
            'appeal_reason' => null,
        ]);

        return response()->json($user);
    }

    public function suspend(User $user)
    {
        $user->update(['status' => 'suspended']);

        return response()->json($user);
    }

    public function updateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,family_member',
        ]);

        $isSelf = $request->user()->id === $user->id;

        if ($isSelf && $validated['role'] !== 'admin') {
            return response()->json(['message' => 'You cannot remove admin from your own account'], 422);
        }

        if ($validated['role'] === 'family_member' && $user->role === 'admin') {
            $adminCount = User::where('role', 'admin')->where('status', 'approved')->count();
            if ($adminCount <= 1) {
                return response()->json(['message' => 'At least one active admin is required'], 422);
            }
        }

        $user->update(['role' => $validated['role']]);

        return response()->json($user);
    }

    public function users()
    {
        return User::all();
    }
}
