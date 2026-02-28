<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $preferences = $request->user()->preferences
            ?? UserPreference::create(['user_id' => $request->user()->id]);

        return response()->json(['data' => $preferences]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'active_bible_version_slug' => ['sometimes', 'nullable', 'string'],
            'active_book_id' => ['sometimes', 'nullable', 'integer'],
            'active_chapter' => ['sometimes', 'nullable', 'integer'],
            'active_verse' => ['sometimes', 'nullable', 'integer'],
            'font_size' => ['sometimes', 'numeric', 'min:8', 'max:48'],
            'font_family' => ['sometimes', 'string', 'max:50'],
            'line_spacing' => ['sometimes', 'numeric', 'min:1', 'max:3'],
            'night_mode' => ['sometimes', 'boolean'],
            'theme' => ['sometimes', 'string', 'in:system,light,dark'],
            'continuous_scroll' => ['sometimes', 'boolean'],
            'show_verse_numbers' => ['sometimes', 'boolean'],
            'show_red_letters' => ['sometimes', 'boolean'],
            'extra' => ['sometimes', 'nullable', 'array'],
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json(['data' => $preferences]);
    }
}
