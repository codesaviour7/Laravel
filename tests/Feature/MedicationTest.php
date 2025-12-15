<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MedicationTest extends TestCase
{
    use RefreshDatabase;

    private function fakeRxNormEndpoints(): void
    {
        Http::fake([
            'https://rxnav.nlm.nih.gov/REST/rxcui/123.json' => Http::response([
                'idGroup' => [
                    'rxnormId' => ['123'],
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
            'https://rxnav.nlm.nih.gov/REST/rxcui/123/properties.json' => Http::response([
                'properties' => [
                    'name' => 'Drug A',
                ],
            ]),
        ]);
    }

    public function test_authenticated_user_can_add_and_list_medications(): void
    {
        $this->fakeRxNormEndpoints();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/medications', ['rxcui' => '123']);
        $createResponse->assertCreated()->assertJsonPath('data.name', 'Drug A');

        $listResponse = $this->getJson('/api/medications');
        $listResponse->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_user_can_delete_medication(): void
    {
        $this->fakeRxNormEndpoints();

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/medications', ['rxcui' => '123'])->assertCreated();

        $deleteResponse = $this->deleteJson('/api/medications/123');
        $deleteResponse->assertOk()->assertJsonPath('message', 'Medication removed.');
    }
}

