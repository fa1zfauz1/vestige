<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        if (!$user) {
            $isAdmin = $socialUser->email === env('APP_ADMIN_EMAIL');

            $user = User::create([
                'name' => $socialUser->name,
                'email' => $socialUser->email,
                'google_id' => $socialUser->id,
                'status' => $isAdmin ? 'approved' : 'pending',
                'role' => $isAdmin ? 'admin' : 'family_member',
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $redirectUrl = rtrim(env('FRONTEND_URL', env('APP_URL', 'http://localhost')), '/') . '/auth/callback';

        return redirect()->away($redirectUrl . '#token=' . urlencode($token));
    }

    public function user(Request $request)
    {
        return $request->user();
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
