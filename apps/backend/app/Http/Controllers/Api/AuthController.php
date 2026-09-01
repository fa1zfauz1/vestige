<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuditLog;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $socialUser = Socialite::driver('google')->stateless()->user();
        } catch (\Exception $e) {
            return response()->json(['error' => 'Authentication failed', 'message' => $e->getMessage()], 400);
        }

        $user = User::where('email', $socialUser->email)->first();
        $isNewUser = false;

        if (!$user) {
            $isNewUser = true;
            $isAdmin = $socialUser->email === env('APP_ADMIN_EMAIL');

            $user = User::create([
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'google_id' => $socialUser->id,
                'status' => $isAdmin ? 'approved' : 'pending',
                'role' => $isAdmin ? 'admin' : 'family_member',
            ]);

            AuditLog::record('user.created', $user, 'Registered with Google', $user);
        }

        AuditLog::record('login', $user, 'Signed in with Google', $user);

        $token = $user->createToken('auth-token')->plainTextToken;
        $redirectUrl = rtrim(env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/') . '/auth/callback';

        return redirect()->away(
            $redirectUrl . '#token=' . urlencode($token) . ($isNewUser ? '&new=1' : '')
        );
    }

    public function user(Request $request)
    {
        return $request->user();
    }

    public function approvedUsers(Request $request)
    {
        return \App\Models\User::where('status', 'approved')
            ->where('id', '!=', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function appeal(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        if ($user->status !== 'rejected') {
            return response()->json(['message' => 'Only accounts that were not approved can appeal'], 422);
        }

        $user->update([
            'status' => 'pending',
            'appeal_reason' => $validated['reason'],
        ]);

        AuditLog::record('user.appealed', $user, 'Appeal: ' . $validated['reason'], $user);

        return response()->json(['message' => 'Your appeal has been submitted for review.']);
    }

    public function logout(Request $request)
    {
        AuditLog::record('logout', $request->user(), 'Signed out', $request->user());

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
