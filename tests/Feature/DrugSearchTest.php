<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DrugSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_search_returns_transformed_results(): void
    {
        Http::fake([
            'https://rxnav.nlm.nih.gov/REST/drugs.json*' => Http::response([
                'drugGroup' => [
                    'conceptGroup' => [
                        [
                            'tty' => 'SBD',
                            'conceptProperties' => [
                                ['rxcui' => '123', 'name' => 'Drug A', 'tty' => 'SBD'],
                                ['rxcui' => '456', 'name' => 'Drug B', 'tty' => 'SBD'],
                            ],
                        ],
                        [
                            'tty' => 'IN',
                            'conceptProperties' => [
                                ['rxcui' => '999', 'name' => 'Ignore', 'tty' => 'IN'],
                            ],
                        ],
                    ],
                ],
            ]),
            'https://rxnav.nlm.nih.gov/REST/rxcui/123/history/status.json' => Http::response([
                'rxcuiStatusHistory' => [
                    'ingredientAndStrength' => [
                        ['baseName' => 'Base One'],
                    ],
                    'doseFormGroupConcept' => [
                        ['doseFormGroupName' => 'Tablet'],
                    ],
                ],
            ]),
            'https://rxnav.nlm.nih.gov/REST/rxcui/456/history/status.json' => Http::response([
                'rxcuiStatusHistory' => [
                    'ingredientAndStrength' => [
                        ['baseName' => 'Base Two'],
                    ],
                    'doseFormGroupConcept' => [
                        ['doseFormGroupName' => 'Capsule'],
                    ],
                ],
            ]),
        ]);

        $response = $this->getJson('/api/search?drug_name=aspirin');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.rxcui', '123')
            ->assertJsonPath('data.0.ingredient_base_names.0', 'Base One')
            ->assertJsonPath('data.0.dose_form_names.0', 'Tablet');
    }
}

