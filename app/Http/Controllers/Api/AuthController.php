<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Create default preferences
        UserPreference::create(['user_id' => $user->id]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'avatar', 'locale', 'timezone'),
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401);
        }

        $user->update(['last_login_at' => now()]);

        $tokenName = $validated['device_name'] ?? 'api';
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'avatar', 'locale', 'timezone'),
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('preferences');

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'avatar', 'locale', 'timezone', 'bio'),
            'preferences' => $user->preferences,
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'locale' => ['sometimes', 'string', 'max:10'],
            'timezone' => ['sometimes', 'string', 'max:50'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'user' => $request->user()->fresh()->only('id', 'name', 'email', 'avatar', 'locale', 'timezone', 'bio'),
        ]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:255'],
            'platform' => ['required', 'string', 'in:android,ios,web'],
            'push_token' => ['sometimes', 'nullable', 'string'],
            'app_version' => ['sometimes', 'nullable', 'string', 'max:20'],
            'os_version' => ['sometimes', 'nullable', 'string', 'max:20'],
            'device_name' => ['sometimes', 'nullable', 'string', 'max:100'],
        ]);

        $device = $request->user()->devices()->updateOrCreate(
            ['device_id' => $validated['device_id']],
            array_merge($validated, ['last_active_at' => now()])
        );

        return response()->json(['device' => $device], 201);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // In production, send a password reset email
        // For now, return success
        return response()->json(['message' => 'Password reset link sent.']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // In production, verify token and reset password
        return response()->json(['message' => 'Password has been reset.']);
    }
}
