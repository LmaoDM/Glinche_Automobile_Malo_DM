<?php

namespace Tests\Feature\Services;

use App\Models\SyncRun;
use App\Models\Vehicle;
use App\Services\VehicleSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class VehicleSynchronizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.glinche.base_url' => 'https://glinche.test/api',
            'services.glinche.email' => 'partner@example.test',
            'services.glinche.password' => 'secret',
        ]);
    }

    public function test_it_authenticates_imports_vehicles_and_logs_out(): void
    {
        Http::fake([
            'https://glinche.test/api/partners/login' => Http::response(['token' => 'test-token']),
            'https://glinche.test/api/partners/vehicles' => Http::response([
                $this->advert('REF-1', 'Renault', 'Austral', 32_500, 'https://images.test/austral.jpg'),
            ]),
            'https://glinche.test/api/partners/logout' => Http::response(status: 204),
        ]);

        $result = app(VehicleSynchronizer::class)->synchronize();

        $this->assertSame([
            'received' => 1,
            'created' => 1,
            'updated' => 0,
            'deactivated' => 0,
        ], $result);

        $vehicle = Vehicle::query()->with(['vehicleModel.brand', 'pictures'])->sole();

        $this->assertSame('REF-1', $vehicle->external_reference);
        $this->assertSame('Renault', $vehicle->vehicleModel->brand->name);
        $this->assertSame('Austral', $vehicle->vehicleModel->name);
        $this->assertSame('32500.00', $vehicle->price);
        $this->assertSame('https://images.test/austral.jpg', $vehicle->pictures->sole()->url);
        $this->assertDatabaseHas('sync_runs', [
            'status' => 'SUCCESS',
            'vehicles_received' => 1,
            'vehicles_created' => 1,
        ]);

        Http::assertSent(fn ($request) => $request->url() === 'https://glinche.test/api/partners/login'
            && $request['email'] === 'partner@example.test');
        Http::assertSent(fn ($request) => $request->url() === 'https://glinche.test/api/partners/vehicles'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
        Http::assertSent(fn ($request) => $request->url() === 'https://glinche.test/api/partners/logout'
            && $request->hasHeader('Authorization', 'Bearer test-token'));
    }

    public function test_it_updates_existing_vehicles_and_deactivates_missing_ones(): void
    {
        Http::fake([
            'https://glinche.test/api/partners/login' => Http::response(['token' => 'test-token']),
            'https://glinche.test/api/partners/vehicles' => Http::sequence()
                ->push([
                    $this->advert('REF-1', 'Renault', 'Austral', 32_500),
                    $this->advert('REF-2', 'Peugeot', '308', 28_000),
                ])
                ->push([
                    $this->advert('REF-1', 'Renault', 'Austral', 30_900),
                ]),
            'https://glinche.test/api/partners/logout' => Http::response(status: 204),
        ]);

        $synchronizer = app(VehicleSynchronizer::class);
        $synchronizer->synchronize();
        $result = $synchronizer->synchronize();

        $this->assertSame(1, $result['received']);
        $this->assertSame(0, $result['created']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(1, $result['deactivated']);
        $this->assertDatabaseHas('vehicles', [
            'external_reference' => 'REF-1',
            'price' => 30_900,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('vehicles', [
            'external_reference' => 'REF-2',
            'is_active' => false,
        ]);
    }

    public function test_it_records_a_failed_run_when_authentication_fails(): void
    {
        Http::fake([
            'https://glinche.test/api/partners/login' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        try {
            app(VehicleSynchronizer::class)->synchronize();
            $this->fail('The synchronization should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('authentification', $exception->getMessage());
        }

        $run = SyncRun::query()->sole();

        $this->assertSame('FAILED', $run->status);
        $this->assertNotNull($run->finished_at);
        $this->assertNotNull($run->error_message);
        $this->assertDatabaseCount('vehicles', 0);
    }

    private function advert(
        string $reference,
        string $brand,
        string $model,
        int $price,
        ?string $picture = null,
    ): array {
        return [
            'reference' => $reference,
            'title' => "{$brand} {$model}",
            'availabilityDate' => '2026-10-08T10:00:00+00:00',
            'insertDate' => '2026-10-01T10:00:00+00:00',
            'editDate' => '2026-10-08T10:00:00+00:00',
            'vehicle' => [
                'manufacturer' => $brand,
                'model' => $model,
                'finish' => 'Techno',
                'year' => 2024,
                'mileage' => 12_500,
                'energy' => 'ES',
                'gearbox' => 'AUTO',
                'prices' => [
                    'price' => $price,
                    'reclaimableVAT' => false,
                ],
            ],
            'pictures' => $picture === null ? [] : [[
                'type' => 'MAIN',
                'url' => $picture,
            ]],
        ];
    }
}
