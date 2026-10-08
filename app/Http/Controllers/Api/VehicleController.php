<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class VehicleController extends Controller
{
    /** @throws ValidationException */
    public function __invoke(): AnonymousResourceCollection
    {
        $filters = Validator::validate(request()->query(), [
            'brand' => ['nullable', 'string', 'max:100'],
        ]);

        $vehicles = Vehicle::query()
            ->with(['vehicleModel.brand', 'pictures'])
            ->where('is_active', true)
            ->when(
                $filters['brand'] ?? null,
                fn ($query, string $brand) => $query->whereHas(
                    'vehicleModel.brand',
                    fn ($brandQuery) => $brandQuery->where('name', $brand),
                ),
            )
            ->orderBy('title')
            ->get();

        return VehicleResource::collection($vehicles);
    }
}
