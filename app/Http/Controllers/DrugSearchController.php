<?php

namespace App\Http\Controllers;

use App\Services\RxNormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DrugSearchController extends Controller
{
    public function __construct(private readonly RxNormService $rxNormService)
    {
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'drug_name' => ['required', 'string', 'max:255'],
        ]);

        $results = $this->rxNormService->searchDrugs($validated['drug_name']);

        return response()->json([
            'data' => $results,
        ]);
    }
}

