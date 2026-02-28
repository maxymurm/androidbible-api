<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Devotional;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevotionalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devotionals = Devotional::published()
            ->when($request->has('language'), fn($q) => $q->where('language', $request->input('language')))
            ->orderByDesc('publish_date')
            ->paginate($request->input('per_page', 25));

        return response()->json($devotionals);
    }

    public function today(Request $request): JsonResponse
    {
        $devotional = Devotional::published()->today()->first();

        if (!$devotional) {
            return response()->json(['message' => 'No devotional for today.'], 404);
        }

        return response()->json(['data' => $devotional]);
    }

    public function show(Devotional $devotional): JsonResponse
    {
        return response()->json(['data' => $devotional]);
    }
}
