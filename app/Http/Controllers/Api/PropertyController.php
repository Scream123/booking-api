<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SearchPropertiesRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PropertyController extends Controller
{
    public function index(SearchPropertiesRequest $request): AnonymousResourceCollection
    {
        $properties = Property::query()
            ->searchAvailable(
                city: $request->string('city')->value() ?: null,
                checkIn: $request->string('check_in')->value(),
                checkOut: $request->string('check_out')->value(),
                guests: $request->integer('guests'),
            )
            ->simplePaginate($request->integer('per_page', 15));

        return PropertyResource::collection($properties);
    }
}
