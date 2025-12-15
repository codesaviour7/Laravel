<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RxNormService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_medications(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/medications');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_authenticated_user_can_store_valid_medication(): void
    {
        $this->mock(RxNormService::class, function ($mock): void {
            $mock->shouldReceive('checkRxcui')->once()->andReturn(true);
            $mock->shouldReceive('historyStatus')->once()->andReturn([
                'ingredient_base_names' => ['Test ingredient'],
                'dose_form_names' => ['Tablet'],
            ]);
            $mock->shouldReceive('fetchDrugName')->once()->andReturn('Test Drug');
        });

        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/medications', [
                'rxcui' => '12345',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.rxcui', '12345');
    }

    public function test_store_medication_fails_for_invalid_rxcui(): void
    {
        $this->mock(RxNormService::class, function ($mock): void {
            $mock->shouldReceive('checkRxcui')->once()->andReturn(false);
        });

        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/medications', [
                'rxcui' => 'invalid',
            ]);

        $response->assertStatus(422);
    }
}

