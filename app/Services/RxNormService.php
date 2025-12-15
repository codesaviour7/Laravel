<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class RxNormService
{
    private const BASE_URL = 'https://rxnav.nlm.nih.gov/REST';

    public function searchDrugs(string $drugName): array
    {
        $trimmed = trim($drugName);

        return Cache::remember("rxnorm:search:{$trimmed}", now()->addMinutes(10), function () use ($trimmed): array {
            $response = Http::get(self::BASE_URL.'/drugs.json', [
                'name' => $trimmed,
            ]);

            if (! $response->successful()) {
                return [];
            }

            $conceptGroups = $response->json('drugGroup.conceptGroup', []);
            $candidates = $this->extractStructuredDrugConcepts($conceptGroups);

            $limited = array_slice($candidates, 0, 5);

            return array_map(function (array $item): array {
                $details = $this->historyStatus($item['rxcui']);

                return [
                    'rxcui' => $item['rxcui'],
                    'name' => $item['name'],
                    'ingredient_base_names' => $details['ingredient_base_names'],
                    'dose_form_names' => $details['dose_form_names'],
                ];
            }, $limited);
        });
    }

    public function validateRxcui(string $rxcui): bool
    {
        $id = trim($rxcui);

        return Cache::remember("rxnorm:valid:{$id}", now()->addDay(), function () use ($id): bool {
            $response = Http::get(self::BASE_URL."/rxcui/{$id}.json");

            if (! $response->successful()) {
                return false;
            }

            $ids = $response->json('idGroup.rxnormId', []);

            return in_array($id, (array) $ids, true);
        });
    }

    public function historyStatus(string $rxcui): array
    {
        $id = trim($rxcui);

        return Cache::remember("rxnorm:history:{$id}", now()->addMinutes(30), function () use ($id): array {
            $response = Http::get(self::BASE_URL."/rxcui/{$id}/history/status.json");

            if (! $response->successful()) {
                return [
                    'ingredient_base_names' => [],
                    'dose_form_names' => [],
                ];
            }

            $history = (array) $response->json('rxcuiStatusHistory', []);

            $ingredientBaseNames = collect($history['ingredientAndStrength'] ?? [])
                ->pluck('baseName')
                ->filter()
                ->unique()
                ->values()
                ->all();

            $doseFormNames = collect($history['doseFormGroupConcept'] ?? [])
                ->pluck('doseFormGroupName')
                ->filter()
                ->unique()
                ->values()
                ->all();

            return [
                'ingredient_base_names' => $ingredientBaseNames,
                'dose_form_names' => $doseFormNames,
            ];
        });
    }

    public function fetchDrugName(string $rxcui): ?string
    {
        $id = trim($rxcui);

        return Cache::remember("rxnorm:name:{$id}", now()->addDay(), function () use ($id): ?string {
            $response = Http::get(self::BASE_URL."/rxcui/{$id}/properties.json");

            if (! $response->successful()) {
                return null;
            }

            return $response->json('properties.name');
        });
    }

    private function extractStructuredDrugConcepts(array $conceptGroups): array
    {
        $results = [];

        foreach ($conceptGroups as $group) {
            $tty = strtoupper($group['tty'] ?? '');

            if ($tty !== 'SBD') {
                continue;
            }

            foreach ($group['conceptProperties'] ?? [] as $property) {
                if (strtoupper($property['tty'] ?? '') !== 'SBD') {
                    continue;
                }

                $rxcui = $property['rxcui'] ?? null;
                $name = $property['name'] ?? null;

                if (! $rxcui || ! $name) {
                    continue;
                }

                $results[$rxcui] = [
                    'rxcui' => $rxcui,
                    'name' => $name,
                ];
            }
        }

        return array_values($results);
    }
}

