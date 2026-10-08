<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\SyncRun;
use App\Models\Vehicle;
use App\Models\VehicleModel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class VehicleSynchronizer
{
    public function __construct(private readonly GlincheApiClient $client) {}

    public function synchronize(): array
    {
        $run = SyncRun::create();

        try {
            $adverts = $this->client->vehicles();
            $result = DB::transaction(fn (): array => $this->persist($adverts, $run));

            $run->update([
                'status' => 'SUCCESS',
                'finished_at' => now(),
                'vehicles_received' => $result['received'],
                'vehicles_created' => $result['created'],
                'vehicles_updated' => $result['updated'],
                'vehicles_deactivated' => $result['deactivated'],
            ]);

            return $result;
        } catch (Throwable $exception) {
            $run->update([
                'status' => 'FAILED',
                'finished_at' => now(),
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }
    }

    private function persist(array $adverts, SyncRun $run): array
    {
        $references = [];
        $created = 0;
        $updated = 0;

        foreach ($adverts as $advert) {
            if (! is_array($advert)) {
                throw new RuntimeException('Une annonce retournée par Glinche est invalide.');
            }

            $reference = trim((string) Arr::get($advert, 'reference'));
            $vehicleData = Arr::get($advert, 'vehicle');

            if ($reference === '' || ! is_array($vehicleData)) {
                throw new RuntimeException('Une annonce ne contient pas de référence ou de véhicule valide.');
            }

            $brandName = trim((string) Arr::get($vehicleData, 'manufacturer'));
            $modelName = trim((string) Arr::get($vehicleData, 'model'));

            if ($brandName === '' || $modelName === '') {
                throw new RuntimeException("Le véhicule {$reference} ne contient pas de marque ou de modèle.");
            }

            $brand = Brand::firstOrCreate(['name' => $brandName]);
            $model = VehicleModel::firstOrCreate([
                'brand_id' => $brand->id,
                'name' => $modelName,
            ]);

            $prices = Arr::get($vehicleData, 'prices', []);
            $attributes = $this->vehicleAttributes($advert, $vehicleData, is_array($prices) ? $prices : [], $model, $run);
            $vehicle = Vehicle::updateOrCreate(['external_reference' => $reference], $attributes);

            $vehicle->wasRecentlyCreated ? $created++ : $updated++;
            $references[] = $reference;

            $vehicle->pictures()->delete();

            foreach (Arr::get($advert, 'pictures', []) as $position => $picture) {
                if (! is_array($picture) || ! filter_var(Arr::get($picture, 'url'), FILTER_VALIDATE_URL)) {
                    continue;
                }

                $vehicle->pictures()->create([
                    'type' => (string) Arr::get($picture, 'type', 'OTHER'),
                    'url' => Arr::get($picture, 'url'),
                    'position' => $position,
                ]);
            }
        }

        $query = Vehicle::query()->where('is_active', true);

        if ($references !== []) {
            $query->whereNotIn('external_reference', $references);
        }

        $deactivated = $query->update(['is_active' => false]);

        return [
            'received' => count($adverts),
            'created' => $created,
            'updated' => $updated,
            'deactivated' => $deactivated,
        ];
    }

    private function vehicleAttributes(
        array $advert,
        array $vehicle,
        array $prices,
        VehicleModel $model,
        SyncRun $run,
    ): array {
        return [
            'vehicle_model_id' => $model->id,
            'last_sync_run_id' => $run->id,
            'title' => (string) Arr::get($advert, 'title', ''),
            'version' => Arr::get($vehicle, 'finish'),
            'source_url' => Arr::get($vehicle, 'url'),
            'vin' => Arr::get($vehicle, 'vin'),
            'registration' => Arr::get($vehicle, 'registration'),
            'vehicle_type' => Arr::get($vehicle, 'type'),
            'year' => $this->integer(Arr::get($vehicle, 'year')),
            'registration_date' => $this->date(Arr::get($vehicle, 'registrationDate')),
            'mileage' => $this->integer(Arr::get($vehicle, 'mileage')) ?? 0,
            'is_mileage_guaranteed' => Arr::get($vehicle, 'isMileageGuaranteed'),
            'is_imported' => Arr::get($vehicle, 'isImported'),
            'is_first_hand' => Arr::get($vehicle, 'isFirstHand'),
            'energy' => (string) Arr::get($vehicle, 'energy', 'UNKNOWN'),
            'gearbox' => Arr::get($vehicle, 'gearbox'),
            'transmission' => Arr::get($vehicle, 'transmission'),
            'power' => $this->integer(Arr::get($vehicle, 'power')),
            'fiscal_power' => $this->integer(Arr::get($vehicle, 'fiscalPower')),
            'emission_wltp' => $this->integer(Arr::get($vehicle, 'emissionWLTP')),
            'body' => Arr::get($vehicle, 'body'),
            'color' => Arr::get($vehicle, 'color'),
            'doors' => $this->integer(Arr::get($vehicle, 'doors')),
            'seats' => $this->integer(Arr::get($vehicle, 'seats')),
            'warranty' => Arr::get($vehicle, 'warranty'),
            'electric_range_wltp' => $this->number(Arr::get($vehicle, 'eAutonomyWltp')),
            'battery_capacity' => $this->number(Arr::get($vehicle, 'eBatteryCapacity')),
            'price' => $this->number(Arr::get($prices, 'price')),
            'merchant_price' => $this->number(Arr::get($prices, 'merchantPrice')),
            'partner_price' => $this->number(Arr::get($prices, 'partnerPrice')),
            'catalog_price' => $this->number(Arr::get($prices, 'catalogPrice')),
            'vat_reclaimable' => Arr::get($prices, 'reclaimableVAT'),
            'tax_code' => Arr::get($prices, 'taxCode'),
            'availability_date' => $this->date(Arr::get($advert, 'availabilityDate')),
            'source_inserted_at' => $this->date(Arr::get($advert, 'insertDate')),
            'source_updated_at' => $this->date(Arr::get($advert, 'editDate')),
            'source_deleted_at' => $this->date(Arr::get($advert, 'deleteDate')),
            'last_synced_at' => now(),
            'is_active' => true,
            'raw_payload' => $advert,
        ];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        return is_string($value) && $value !== '' ? CarbonImmutable::parse($value) : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
