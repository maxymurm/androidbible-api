<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReadingPlan;
use App\Models\ReadingPlanProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingPlanProgressController extends Controller
{
    public function start(Request $request, ReadingPlan $readingPlan): JsonResponse
    {
        $validated = $request->validate([
            'bible_version_slug' => ['sometimes', 'nullable', 'string'],
        ]);

        $progress = ReadingPlanProgress::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'reading_plan_id' => $readingPlan->id,
            ],
            [
                'start_date' => now(),
                'current_day' => 1,
                'completed_days' => [],
                'status' => 'active',
                'bible_version_slug' => $validated['bible_version_slug'] ?? null,
            ]
        );

        return response()->json(['data' => $progress], 201);
    }

    public function show(Request $request, ReadingPlan $readingPlan): JsonResponse
    {
        $progress = ReadingPlanProgress::where('user_id', $request->user()->id)
            ->where('reading_plan_id', $readingPlan->id)
            ->firstOrFail();

        return response()->json(['data' => $progress]);
    }

    public function update(Request $request, ReadingPlan $readingPlan): JsonResponse
    {
        $validated = $request->validate([
            'completed_day' => ['required', 'integer', 'min:1'],
        ]);

        $progress = ReadingPlanProgress::where('user_id', $request->user()->id)
            ->where('reading_plan_id', $readingPlan->id)
            ->firstOrFail();

        $progress->markDayComplete($validated['completed_day']);

        return response()->json(['data' => $progress->fresh()]);
    }
}
