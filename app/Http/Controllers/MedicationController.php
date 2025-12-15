<?php

namespace App\Http\Controllers;

use App\Models\Medication;
use App\Services\RxNormService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicationController extends Controller
{
    public function __construct(private readonly RxNormService $rxNormService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $medications = $request->user()->medications()->latest()->get();

        return response()->json([
            'data' => $medications,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rxcui' => ['required', 'string', 'max:255'],
        ]);

        $rxcui = $validated['rxcui'];

        if (! $this->rxNormService->validateRxcui($rxcui)) {
            return response()->json([
                'message' => 'Invalid RXCUI provided.',
            ], 422);
        }

        $details = $this->rxNormService->historyStatus($rxcui);
        $name = $this->rxNormService->fetchDrugName($rxcui) ?? $rxcui;

        $medication = $request->user()->medications()->updateOrCreate(
            ['rxcui' => $rxcui],
            [
                'name' => $name,
                'ingredient_base_names' => $details['ingredient_base_names'],
                'dose_form_names' => $details['dose_form_names'],
            ]
        );

        return response()->json([
            'data' => $medication,
        ], 201);
    }

    public function destroy(Request $request, string $rxcui): JsonResponse
    {
        /** @var Medication|null $medication */
        $medication = $request->user()->medications()->where('rxcui', $rxcui)->first();

        if (! $medication) {
            return response()->json([
                'message' => 'Medication not found for this user.',
            ], 404);
        }

        $medication->delete();

        return response()->json([
            'message' => 'Medication removed.',
        ]);
    }
}

