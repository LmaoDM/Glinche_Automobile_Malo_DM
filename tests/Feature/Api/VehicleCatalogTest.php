<?php

namespace Tests\Feature\Api;

use App\Models\Brand;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VehicleCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_active_vehicles(): void
    {
        $renault = $this->createVehicle('Renault', 'Austral', 'REF-1');
        $this->createVehicle('Peugeot', '308', 'REF-2', false);

        $renault->pictures()->create([
            'type' => 'MAIN',
            'url' => 'https://example.test/austral.jpg',
            'position' => 0,
        ]);

        $response = $this->getJson('/api/vehicles');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.reference', 'REF-1')
            ->assertJsonPath('data.0.brand', 'Renault')
            ->assertJsonPath('data.0.picture', 'https://example.test/austral.jpg');
    }

    public function test_it_filters_vehicles_by_brand(): void
    {
        $this->createVehicle('Renault', 'Austral', 'REF-1');
        $this->createVehicle('Peugeot', '308', 'REF-2');

        $response = $this->getJson('/api/vehicles?brand=Peugeot');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.brand', 'Peugeot');
    }

    public function test_it_returns_only_brands_with_active_vehicles(): void
    {
        $this->createVehicle('Renault', 'Austral', 'REF-1');
        $this->createVehicle('Peugeot', '308', 'REF-2', false);

        $response = $this->getJson('/api/brands');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Renault');
    }

    private function createVehicle(string $brandName, string $modelName, string $reference, bool $active = true): Vehicle
    {
        $brand = Brand::firstOrCreate(['name' => $brandName]);
        $model = VehicleModel::firstOrCreate([
            'brand_id' => $brand->id,
            'name' => $modelName,
        ]);

        return Vehicle::create([
            'vehicle_model_id' => $model->id,
            'external_reference' => $reference,
            'title' => "{$brandName} {$modelName}",
            'mileage' => 1000,
            'energy' => 'ES',
            'price' => 25000,
            'is_active' => $active,
            'raw_payload' => [],
        ]);
    }
}
