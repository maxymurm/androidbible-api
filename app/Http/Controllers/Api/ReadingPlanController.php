<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingPlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $plans = ReadingPlan::active()
            ->when($request->has('language'), fn($q) => $q->where('language', $request->input('language')))
            ->when($request->has('category'), fn($q) => $q->where('category', $request->input('category')))
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'title', 'description', 'duration_days', 'language', 'category', 'thumbnail']);

        return response()->json(['data' => $plans]);
    }

    public function show(ReadingPlan $readingPlan): JsonResponse
    {
        $readingPlan->load('days');
        return response()->json(['data' => $readingPlan]);
    }
}
