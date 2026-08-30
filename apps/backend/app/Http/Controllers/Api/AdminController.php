<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('can:admin');
    }

    public function pendingUsers()
    {
        return User::where('status', 'pending')->get();
    }

    public function approve(User $user)
    {
        $user->update(['status' => 'approved']);

        return response()->json($user);
    }

    public function reject(User $user)
    {
        $user->update(['status' => 'rejected']);

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
