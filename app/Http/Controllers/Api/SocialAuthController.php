<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Handle social login via token (for mobile apps).
     *
     * Mobile apps use their native SDKs to get the OAuth token,
     * then send it here for server-side validation.
     */
    public function loginWithToken(Request $request, string $provider): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ]);

        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['message' => 'Unsupported provider.'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($request->token);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid social token.',
                'error' => $e->getMessage(),
            ], 401);
        }

        $user = $this->findOrCreateUser($socialUser, $provider);

        $tokenName = $request->input('device_name', $provider);
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'avatar', 'locale', 'timezone'),
            'token' => $token,
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }

    /**
     * OAuth redirect URL (for web flow).
     */
    public function redirect(string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['message' => 'Unsupported provider.'], 422);
        }

        $url = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * OAuth callback handler (for web flow).
     */
    public function callback(Request $request, string $provider): JsonResponse
    {
        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['message' => 'Unsupported provider.'], 422);
        }

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->user();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Social authentication failed.',
                'error' => $e->getMessage(),
            ], 401);
        }

        $user = $this->findOrCreateUser($socialUser, $provider);
        $token = $user->createToken($provider)->plainTextToken;

        return response()->json([
            'user' => $user->only('id', 'name', 'email', 'avatar', 'locale', 'timezone'),
            'token' => $token,
            'is_new_user' => $user->wasRecentlyCreated,
        ]);
    }

    /**
     * Disconnect a social provider from the user's account.
     */
    public function disconnect(Request $request, string $provider): JsonResponse
    {
        $user = $request->user();

        $providerField = "social_{$provider}_id";
        if (!in_array($provider, ['google', 'apple']) || !isset($user->$providerField)) {
            return response()->json(['message' => 'Provider not connected.'], 422);
        }

        $user->update([
            $providerField => null,
        ]);

        return response()->json(['message' => "Disconnected from {$provider}."]);
    }

    /**
     * Find existing user by social provider ID or email, or create a new one.
     */
    private function findOrCreateUser($socialUser, string $provider): User
    {
        $providerIdField = "social_{$provider}_id";

        // 1. Try to find by provider ID
        $user = User::where($providerIdField, $socialUser->getId())->first();

        if ($user) {
            // Update avatar if changed
            if ($socialUser->getAvatar() && $user->avatar !== $socialUser->getAvatar()) {
                $user->update(['avatar' => $socialUser->getAvatar()]);
            }
            return $user;
        }

        // 2. Try to find by email and link provider
        if ($socialUser->getEmail()) {
            $user = User::where('email', $socialUser->getEmail())->first();
            if ($user) {
                $user->update([
                    $providerIdField => $socialUser->getId(),
                    'avatar' => $user->avatar ?? $socialUser->getAvatar(),
                ]);
                return $user;
            }
        }

        // 3. Create new user
        return User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password for social-only users
            $providerIdField => $socialUser->getId(),
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => now(), // Social login = verified email
        ]);
    }
}
