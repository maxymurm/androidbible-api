<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarkerLabelController extends Controller
{
    public function attach(Request $request, Marker $marker, Label $label): JsonResponse
    {
        $this->authorize('update', $marker);
        $marker->labels()->syncWithoutDetaching([$label->id]);
        return response()->json(['message' => 'Label attached.']);
    }

    public function detach(Request $request, Marker $marker, Label $label): JsonResponse
    {
        $this->authorize('update', $marker);
        $marker->labels()->detach($label->id);
        return response()->json(['message' => 'Label detached.']);
    }
}
