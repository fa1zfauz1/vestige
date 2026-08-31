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

    public function users()
    {
        return User::all();
    }
}
