<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PatientReniecTest extends TestCase
{
    public function test_it_explains_that_a_dni_missing_from_the_provider_can_be_entered_manually(): void
    {
        config(['services.decolecta.token' => 'test-token']);
        Http::fake([
            'api.decolecta.com/*' => Http::response(['message' => 'Not found'], 404),
        ]);

        $response = $this->actingAs(User::factory()->make())
            ->getJson(route('patients.reniec', ['numero' => '12345678']));

        $response->assertNotFound()
            ->assertJson([
                'manual_entry' => true,
            ])
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'menores de edad'));

        Http::assertSent(fn ($request) => $request->url() === 'https://api.decolecta.com/v1/reniec/dni?numero=12345678'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_it_reports_provider_errors_as_a_temporary_service_failure(): void
    {
        config(['services.decolecta.token' => 'test-token']);
        Http::fake([
            'api.decolecta.com/*' => Http::response(['message' => 'Upstream error'], 500),
        ]);

        $this->actingAs(User::factory()->make())
            ->getJson(route('patients.reniec', ['numero' => '12345678']))
            ->assertServiceUnavailable()
            ->assertJsonMissingPath('details');
    }
}
