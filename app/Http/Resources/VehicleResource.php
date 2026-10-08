<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VehicleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $mainPicture = $this->pictures->firstWhere('type', 'MAIN') ?? $this->pictures->first();

        return [
            'id' => $this->id,
            'reference' => $this->external_reference,
            'title' => $this->title,
            'brand' => $this->vehicleModel->brand->name,
            'model' => $this->vehicleModel->name,
            'version' => $this->version,
            'year' => $this->year,
            'mileage' => $this->mileage,
            'energy' => [
                'code' => $this->energy,
                'label' => $this->energyLabel(),
            ],
            'gearbox' => $this->gearbox,
            'price' => $this->price ?? $this->partner_price ?? $this->merchant_price,
            'vat_reclaimable' => $this->vat_reclaimable,
            'picture' => $mainPicture?->url,
            'availability_date' => $this->availability_date?->toIso8601String(),
        ];
    }

    private function energyLabel(): string
    {
        return match ($this->energy) {
            'GO' => 'Diesel',
            'EL' => 'Électrique',
            'ET' => 'Éthanol',
            'ES' => 'Essence',
            'GH' => 'Hybride diesel',
            'EH' => 'Hybride essence',
            'GL' => 'Hybride diesel rechargeable',
            'EE' => 'Hybride essence rechargeable',
            'H2' => 'Hydrogène',
            default => $this->energy,
        };
    }
}
